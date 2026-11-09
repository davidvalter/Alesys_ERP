// /public/js/financeiro_formaspagamento.js

let modalForma;
let tabela;

document.addEventListener('DOMContentLoaded', () => {
    modalForma = new bootstrap.Modal(document.getElementById('modalForma'));
    document.getElementById('btnNovaForma').addEventListener('click', abrirModalForma);
    document.getElementById('formFormaPagamento').addEventListener('submit', salvarForma);
    carregarFormasPagamento();

});


// ==============================
// CARREGAR FORMAS
// ==============================

async function carregarFormasPagamento() {
    const tbody = document.querySelector('#tabelaFormasPagamento tbody');

    if ($.fn.DataTable.isDataTable('#tabelaFormasPagamento')) {
        $('#tabelaFormasPagamento').DataTable().destroy();
    }

    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3">Carregando...</td></tr>`;

    try {
        const res = await fetch('../api/get_formaspagamento.php');
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        if (!json.data || json.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3">Nenhuma forma encontrada.</td></tr>`;
            return;
        }

        tbody.innerHTML = '';
        json.data.forEach(f => {
            const tipoBadge = f.Tipo === 'entrada'
                ? `<span class="badge bg-success">Entrada</span>`
                : `<span class="badge bg-danger">Saída</span>`;

            const statusBadge = f.Ativo == 1
                ? `<span class="badge rounded-pill bg-success">Ativo</span>`
                : `<span class="badge rounded-pill bg-secondary">Inativo</span>`;

            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${f.Nome}</td>
                <td>${tipoBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick='editarForma(${JSON.stringify(f)})'>
                        <i class="fas fa-edit"></i>
                    </button>

                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleForma(${f.Id}, ${f.Ativo})">
                        <i class="fas fa-power-off"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);

        });

        tabela = $('#tabelaFormasPagamento').DataTable({
            pageLength: 20,
            ordering: true,
            responsive: true,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
            }
        });

    } catch (err) {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3">Erro ao carregar dados.</td></tr>`;

    }

}


// ==============================
// MODAL
// ==============================

function abrirModalForma() {
    document.getElementById('formFormaPagamento').reset();
    document.getElementById('formaId').value = '';
    document.querySelector('#modalForma .modal-title').innerText = 'Nova Forma de Pagamento';
    modalForma.show();

}

function editarForma(f) {
    document.getElementById('formaId').value = f.Id;
    document.getElementById('nome').value = f.Nome;
    document.getElementById('tipo').value = f.Tipo;
    document.querySelector('#modalForma .modal-title').innerText = 'Editar Forma de Pagamento';
    modalForma.show();

}


// ==============================
// SALVAR
// ==============================

async function salvarForma(e) {
    e.preventDefault();

    const payload = {
        Id: document.getElementById('formaId').value,
        Nome: document.getElementById('nome').value.trim(),
        Tipo: document.getElementById('tipo').value
    };

    try {
        const res = await fetch('../api/save_formapagamento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        showToast('Forma de pagamento salva com sucesso!', 'success');
        modalForma.hide();
        carregarFormasPagamento();
    } catch (err) {
        console.error(err);
        showToast('Erro ao salvar forma de pagamento.', 'error');
    }
}


// ==============================
// TOGGLE STATUS
// ==============================

async function toggleForma(id, ativo) {
    if (!confirm('Deseja alterar o status desta forma de pagamento?')) return;
    try {
        const res = await fetch('../api/toggle_formapagamento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, ativo: ativo ? 0 : 1 })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message);
        showToast('Status atualizado!', 'success');
        carregarFormasPagamento();

    } catch (err) {
        console.error(err);
        showToast('Erro ao atualizar status.', 'error');
    }
}