// public/js/financeiro_contas.js

let modalConta;

$(document).ready(function () {
    modalConta = new bootstrap.Modal(document.getElementById('modalConta'));
    carregarContas();
    $('#btnNovaConta').on('click', function () {
        abrirModalConta();
    });
    $('#tipoConta').on('change', function () {
        toggleBancoFields();
    });
    $('#formConta').on('submit', function (e) {
        e.preventDefault();
        salvarConta();
    });
});

function carregarContas() {
    $('#contasBody').html('<tr><td colspan="9" class="text-center">Carregando...</td></tr>');
    fetch('../api/get_contas.php')
        .then(r => r.json())
        .then(resp => {
            if (!resp.success) {
                $('#contasBody').html('<tr><td colspan="9" class="text-center">Erro ao carregar.</td></tr>');
                return;
            }
            renderContas(resp.data);
        })
        .catch(err => {
            console.error(err);
            $('#contasBody').html('<tr><td colspan="9" class="text-center">Erro ao carregar.</td></tr>');
        });
}

function renderContas(lista) {
    if (!Array.isArray(lista) || lista.length === 0) {
        $('#contasBody').html('<tr><td colspan="9" class="text-center">Nenhuma conta cadastrada.</td></tr>');
        return;
    }
    let html = '';
    lista.forEach(c => {
        html += `<tr data-id="${c.Id}">
            <td>${escapeHtml(c.Nome)}</td>
            <td>${badgeTipoConta(c.Tipo)}</td>
            <td>${escapeHtml(c.Banco || '-')}</td>
            <td>${escapeHtml(c.Agencia || '-')}</td>
            <td>${escapeHtml(c.Conta || '-')}</td>
            <td class="text-right">R$ ${numberFormat(c.SaldoInicial)}</td>
            <td class="text-right">R$ ${numberFormat(c.SaldoAtual)}</td>
            <td>${c.Ativo == 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>'}</td>
            <td>
                <button class="btn btn-icon btn-edit" onclick="editarConta(${c.Id})" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn btn-icon btn-toggle" onclick="toggleConta(${c.Id}, this)" title="${c.Ativo == 1 ? 'Inativar' : 'Ativar'}"><i class="fas fa-toggle-on"></i></button>
            </td>
        </tr>`;
    });
    $('#contasBody').html(html);
}

function abrirModalConta() {
    $('#formConta')[0].reset();
    $('#contaId').val('');
    $('#modalConta .modal-title').text('Nova Conta Bancária');
    toggleBancoFields();
    modalConta.show();
}

function fecharModalConta() {
    modalConta.hide();
}

function badgeTipoConta(tipo) {
    if (tipo === 'caixa')
        return '<span class="badge bg-dark">Caixa</span>';
    if (tipo === 'banco')
        return '<span class="badge bg-primary">Banco</span>';
    if (tipo === 'maquininha')
        return '<span class="badge bg-warning text-dark">Maquininha</span>';
    return '<span class="badge bg-secondary">Outros</span>';
}

function toggleBancoFields() {
    const tipo = $('#tipoConta').val();
    if (tipo === 'banco') {
        $('#bancoFields').show();
    } else {
        $('#bancoFields').hide();
        $('#banco').val('');
        $('#agencia').val('');
        $('#conta').val('');
    }
}

function salvarConta() {
    const payload = {
        Id: $('#contaId').val() || null,
        Nome: $('#nomeConta').val().trim(),
        Tipo: $('#tipoConta').val(),
        Banco: $('#banco').val().trim(),
        Agencia: $('#agencia').val().trim(),
        Conta: $('#conta').val().trim(),
        SaldoInicial: parseFloat($('#saldoInicial').val() || 0),
        Ativo: parseInt($('#ativoConta').val() || 1, 10)
    };

    if (!payload.Nome) {
        showToast('Informe o nome da conta.', 'error');
        return;
    }

    fetch('../api/save_conta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast(json.message || 'Salvo', 'success');
                fecharModalConta();
                carregarContas();
            } else {
                showToast(json.message || 'Erro ao salvar', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erro ao salvar conta', 'error');
        });
}

function editarConta(id) {
    // carrega dados da conta via get_contas (poderíamos criar endpoint get_conta, mas reuso listagem)
    fetch('../api/get_contas.php')
        .then(r => r.json())
        .then(json => {
            if (!json.success) { showToast('Erro ao buscar conta', 'error'); return; }
            const conta = (json.data || []).find(x => parseInt(x.Id) === parseInt(id));
            if (!conta) { showToast('Conta não encontrada', 'error'); return; }

            $('#contaId').val(conta.Id);
            $('#nomeConta').val(conta.Nome);
            $('#tipoConta').val(conta.Tipo);
            $('#banco').val(conta.Banco);
            $('#agencia').val(conta.Agencia);
            $('#conta').val(conta.Conta);
            $('#saldoInicial').val(parseFloat(conta.SaldoInicial).toFixed(2));
            $('#ativoConta').val(conta.Ativo == 1 ? '1' : '0');

            $('#modalConta .modal-title').text('Editar Conta Bancária');
            toggleBancoFields();
            modalConta.show();
        });
}

function toggleConta(id, btn) {
    if (!confirm('Deseja alterar o status desta conta?')) return;
    const form = new FormData();
    form.append('id', id);

    fetch('../api/toggle_conta.php', {
        method: 'POST',
        body: form
    })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast(json.message, 'success');
                carregarContas();
            } else {
                showToast(json.message || 'Erro ao alterar status', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Erro ao alterar status', 'error');
        });
}

/* util helpers */
function numberFormat(v) {
    return Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function escapeHtml(s) {
    if (!s && s !== 0) return '';
    return String(s).replace(/[&<>"']/g, function (m) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
    });
}

/* safe showToast: delega para implementação global se existir e for diferente desta função */
function showToast(msg, type = 'info') {
    // Se já existe outra implementação global de showToast (definida por outro módulo),
    // e ela NÃO for esta função atual, usamos a implementação externa.
    if (typeof window.showToast === 'function' && window.showToast !== showToast) {
        try {
            window.showToast(msg, type);
            return;
        } catch (e) {
            // se a implementação externa falhar, caímos para o fallback abaixo
            console.warn('showToast externo falhou, usando fallback local', e);
        }
    }

    // --- fallback local (autônomo) ---
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '99999';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast ' + (type || 'info');
    toast.style.padding = '10px 14px';
    toast.style.borderRadius = '8px';
    toast.style.color = '#fff';
    toast.style.marginBottom = '8px';
    toast.style.boxShadow = '0 3px 8px rgba(0,0,0,0.15)';
    toast.style.fontWeight = '600';

    if (type === 'success') toast.style.background = '#2ecc71';
    else if (type === 'error') toast.style.background = '#e74c3c';
    else if (type === 'info') toast.style.background = '#3498db';
    else toast.style.background = '#333';

    toast.innerText = msg;
    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
        // remove container se vazio (limpeza)
        if (container.children.length === 0) container.remove();
    }, 3500);
}

