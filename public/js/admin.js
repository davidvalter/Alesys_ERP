/**
 * Arquivo de funções JavaScript para a área administrativa
 * Local: public/js/admin.js
 */

document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       1. Confirmação de exclusões
    ========================================================= */

    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Tem certeza que deseja excluir este item?')) {
                e.preventDefault();
            }
        });
    });


    /* =========================================================
       2. Toggle de status de usuário
    ========================================================= */

    document.querySelectorAll('.btn-toggle-status').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();

            const userId = this.dataset.userId;
            const currentStatus = this.dataset.status === '1';
            const newStatus = !currentStatus;

            if (!confirm(`Deseja realmente ${newStatus ? 'ativar' : 'desativar'} este usuário?`)) {
                return;
            }

            try {
                const response = await fetch(`${adminUrlBase}usuarios/toggle-status.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },

                    body: JSON.stringify({
                        id: userId,
                        status: newStatus ? 1 : 0
                    })

                });

                const result = await response.json();

                if (result.success) {
                    this.dataset.status = newStatus ? '1' : '0';
                    this.classList.remove(currentStatus ? 'btn-danger' : 'btn-success');
                    this.classList.add(newStatus ? 'btn-danger' : 'btn-success');
                    this.innerHTML = `<i class="fas fa-power-off"></i> ${newStatus ? 'Desativar' : 'Ativar'} Usuário`;
                    const statusBadge = document.querySelector(`.user-status-badge[data-user-id="${userId}"]`);
                    if (statusBadge) {
                        statusBadge.className = `user-status-badge badge ${newStatus ? 'bg-success' : 'bg-danger'}`;
                        statusBadge.textContent = newStatus ? 'Ativo' : 'Inativo';

                    }

                    showAlert('Status atualizado com sucesso!', 'success');

                } else {
                    throw new Error(result.message || 'Erro ao atualizar status');
                }

            } catch (error) {
                showAlert(error.message, 'danger');
                console.error('Erro:', error);
            }

        });

    });


    /* =========================================================
       3. Redefinição de senha
    ========================================================= */

    document.querySelectorAll('.btn-reset-password').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();

            const userId = this.dataset.userId;

            if (!confirm('Deseja realmente redefinir a senha deste usuário para a senha padrão?')) {
                return;
            }

            try {
                const response = await fetch(`${adminUrlBase}usuarios/reset-password.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: userId })

                });

                const result = await response.json();
                if (result.success) {
                    showAlert(
                        'Senha redefinida com sucesso! A nova senha é: ' + result.password,
                        'success'
                    );

                } else {
                    throw new Error(result.message || 'Erro ao redefinir senha');
                }

            } catch (error) {
                showAlert(error.message, 'danger');
                console.error('Erro:', error);
            }

        });

    });


    /* =========================================================
       4. Sistema de Alertas
    ========================================================= */

    function showAlert(message, type) {

        const alertDiv = document.createElement('div');

        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.admin-header') || document.body;

        container.prepend(alertDiv);
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);

    }


    /* =========================================================
       5. Fechamento automático de alertas do PHP
    ========================================================= */

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.classList.remove('show');
            setTimeout(() => {
                el.remove();
            }, 300);
        });
    }, 4000);


    /* =========================================================
       6. Máscaras de formulário
    ========================================================= */

    if (typeof IMask !== 'undefined') {

        document.querySelectorAll('input[data-mask="telefone"]').forEach(input => {
            IMask(input, {
                mask: '(00) 00000-0000'
            });
        });

        document.querySelectorAll('input[data-mask="cpf"]').forEach(input => {
            IMask(input, {
                mask: '000.000.000-00'
            });
        });

    }


    /* =========================================================
       7. Validação de formulários
    ========================================================= */

    document.querySelectorAll('form.needs-validation').forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });


    /* =========================================================
       8. Ordenação de tabelas
    ========================================================= */

    document.querySelectorAll('table.sortable').forEach(table => {
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.column;
                const direction = header.classList.contains('asc') ? 'desc' : 'asc';
                headers.forEach(h => {
                    h.classList.remove('asc', 'desc');
                });
                header.classList.add(direction);
                sortTable(table, column, direction);
            });
        });
    });


    function sortTable(table, column, direction) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const aValue = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
            const bValue = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
            return direction === 'asc'
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        });
        rows.forEach(row => tbody.removeChild(row));
        rows.forEach(row => tbody.appendChild(row));
    }


    /* =========================================================
       9. Modal de Usuário
    ========================================================= */

    const modalUsuario = document.getElementById("modalUsuario");

    if (modalUsuario) {
        modalUsuario.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            const id = button?.dataset?.id || "";
            const titulo = id ? "Editar Usuário" : "Novo Usuário";
            document.getElementById("modalTituloUsuario").innerText = titulo;
            fetch(`modal_form.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById("modalBodyUsuario").innerHTML = html;
                });
        });
    }


    /* =========================================================
       10. Toggle simples de status (fallback)
    ========================================================= */

    document.querySelectorAll(".btn-status").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            const status = btn.dataset.status == 1 ? 0 : 1;
            if (!confirm("Alterar status do usuário?")) {
                return;
            }
            fetch(`${adminUrlBase}usuarios/toggle-status.php`, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id, status })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert("Erro ao alterar status");
                    }
                });
        });
    });
});


/* =========================================================
   Variável global com a URL base do admin
========================================================= */

const adminUrlBase = '/admin/';