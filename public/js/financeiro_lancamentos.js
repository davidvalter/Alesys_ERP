/* /public/js/financeiro_lancamentos.js */

let tabelaLanc;
let modalLancamento;
let modalBaixa;

$(document).ready(function () {

    // MODAL
    modalLancamento = new bootstrap.Modal(
        document.getElementById('modalLancamento')
    );

    modalBaixa = new bootstrap.Modal(
        document.getElementById('modalBaixa')
    );


    const hoje = new Date();
    const inicio = new Date();
    inicio.setDate(hoje.getDate() - 30);

    const format = d => d.toISOString().split('T')[0];

    $('#filtroDataInicio').val(format(inicio));
    $('#filtroDataFim').val(format(hoje));

    carregarContasFiltro();

    iniciarTabela();

    $('#btnFiltrarLanc').on('click', function () {
        tabelaLanc.ajax.reload();
    });

    $('#formBaixa').on('submit', async function (e) {
        e.preventDefault();
        await confirmarBaixa();
    });

    $('#formLancamento').on('submit', async function (e) {
        e.preventDefault();
        await salvarLancamento(e);
    });

    configurarMascaraDinheiro();

});


function iniciarTabela() {

    tabelaLanc = $('#tabelaLanc').DataTable({

        processing: true,
        serverSide: true,
        searching: true,
        paging: true,
        ordering: true,

        pageLength: 10,

        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
        },

        ajax: function (data, callback) {

            const conta = $('#filtroConta').val();
            const status = $('#filtroStatus').val();
            const dataInicio = $('#filtroDataInicio').val();
            const dataFim = $('#filtroDataFim').val();

            const params = new URLSearchParams();

            params.set('page', (data.start / data.length) + 1);
            params.set('limit', data.length);

            if (conta) params.set('conta', conta);
            if (status) params.set('status', status);
            if (dataInicio) params.set('start_date', dataInicio);
            if (dataFim) params.set('end_date', dataFim);

            fetch('../api/get_lancamentos.php?' + params.toString())
                .then(r => r.json())
                .then(json => {

                    const rows = json.data.map(r => {
                        return [
                            `${escapeHtml(r.DataBaixa || '-')}
                            <br>
                            <small>Lanç: ${escapeHtml(r.DataLancamento)}</small>`,
                            escapeHtml(r.Conta || '-'),
                            escapeHtml(r.Categoria || '-'),
                            escapeHtml(r.Descricao || ''),
                            escapeHtml(r.Tipo),
                            `R$ ${numberFormat(r.ValorBruto)}`,
                            `R$ ${numberFormat(r.Taxas)}`,
                            `R$ ${numberFormat(r.ValorLiquido)}`,
                            escapeHtml(r.Status),
                            `
                            <div class="acoes-tabela">

                                <button class="btn-action btn-baixa"
                                    onclick="abrirModalBaixa(${r.Id})"
                                    title="Registrar Baixa">
                                    <i class="fas fa-check-circle"></i>
                                </button>

                                <button class="btn-action btn-excluir"
                                    onclick="excluirLancamento(${r.Id})"
                                    title="Excluir Lançamento">
                                    <i class="fas fa-trash"></i>
                                </button>

                            </div>
                            `
                        ];
                    });

                    callback({
                        recordsTotal: json.total,
                        recordsFiltered: json.total,
                        data: rows
                    });
                });
        }
    });
}


async function carregarContasFiltro() {

    try {
        const res = await fetch('../api/get_contas.php');
        const json = await res.json();
        if (!json.success) return;
        const sel = document.getElementById('filtroConta');
        sel.innerHTML = '<option value="">Todas as contas</option>';
        json.data.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.Id;
            opt.textContent = c.Nome;
            sel.appendChild(opt);
        });

    } catch (err) {
        console.error(err);
    }
}


function numberFormat(v) {
    return Number(v || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2
    });
}


function escapeHtml(s) {
    if (!s && s !== 0) return '';
    return String(s)
        .replace(/[&<>"']/g, m => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m]));
}


function showToast(msg, type = 'info') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerText = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}


function abrirModalBaixa(id) {
    document.getElementById('baixalancId').value = id;
    modalBaixa.show();
}


async function confirmarBaixa() {
    const id = document.getElementById('baixalancId').value;
    const data = document.getElementById('dataBaixa').value;
    const obs = document.getElementById('obsBaixa').value;
    const res = await fetch('../api/baixar_lancamento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            lancId: id,
            dataBaixa: data,
            observacoes: obs
        })
    });

    const json = await res.json();

    if (json.success) {
        showToast('Baixa registrada', 'success');
        modalBaixa.hide();
        tabelaLanc.ajax.reload();
    }
}


async function excluirLancamento(id) {

    if (!confirm('Deseja excluir?')) return;
    const res = await fetch('../api/delete_lancamentofinanceiro.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ lancId: id })
    });

    const json = await res.json();

    if (json.success) {
        showToast('Excluído', 'success');
        modalBaixa.hide();
        tabelaLanc.ajax.reload();
    }

}


function configurarMascaraDinheiro() {
    const campo = document.getElementById("valorBruto");
    if (!campo) return;
    campo.addEventListener("input", function () {
        let value = this.value.replace(/\D/g, "");
        if (value === "") {
            this.value = "";
            return;
        }
        value = (value / 100).toFixed(2) + "";
        value = value.replace(".", ",");
        this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    });

}


async function abrirModalLancamento() {
    const form = document.getElementById('formLancamento');
    form.reset();
    document.getElementById('dataLancamento').value =
        new Date().toISOString().split('T')[0];

    await carregarSelectsModal();

    modalLancamento.show();
}


function fecharModalLancamento() {
    modalLancamento.hide();
}

function fecharModalBaixa() {
    modalBaixa.hide();
}


async function carregarSelectsModal() {
    const cat = document.getElementById('categoria');
    const conta = document.getElementById('contaModal');
    const forma = document.getElementById('formaPagamento');
    const res1 = await fetch('../api/get_categoriaslancamentos.php');
    const j1 = await res1.json();
    cat.innerHTML = '<option value="">Selecione</option>';
    j1.data.forEach(c => {
        cat.innerHTML += `<option value="${c.Id}">${c.Nome} (${c.Tipo})</option>`;
    });
    const res2 = await fetch('../api/get_contas_ativas.php');
    const j2 = await res2.json();
    conta.innerHTML = '<option value="">Selecione</option>';
    j2.data.forEach(c => {
        conta.innerHTML += `<option value="${c.Id}">${c.Nome}</option>`;
    });
    const res3 = await fetch('../api/get_formaspagamento.php');
    const j3 = await res3.json();
    forma.innerHTML = '<option value="">Selecione</option>';
    j3.data.forEach(f => {
        forma.innerHTML += `<option value="${f.Id}">${f.Nome}</option>`;
    });
}


async function salvarLancamento(e) {
    const formData = Object.fromEntries(new FormData(e.target).entries());
    const normalizar = v => {
        if (!v) return 0;
        v = v.replace(/\./g, '');
        v = v.replace(',', '.');
        return parseFloat(v);
    };

    formData.valorBruto = normalizar(formData.valorBruto);
    const res = await fetch('../api/save_lancamentofinanceiro.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    });

    const json = await res.json();

    if (json.success) {
        showToast('Salvo com sucesso', 'success');
        fecharModalLancamento();
        tabelaLanc.ajax.reload();
    }
}