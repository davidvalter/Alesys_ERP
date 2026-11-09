/* public/js/fechamento_caixa.js */

$(function () {

    $('.money').maskMoney({
        prefix: 'R$ ',
        allowNegative: false,
        thousands: '.',
        decimal: ',',
        affixesStay: true
    });

    $('.money').on('keyup change', calcularTotal);

});

function parseMoney(v) {
    if (!v) return 0;
    return parseFloat(v.replace('R$', '').replace(/\./g, '').replace(',', '.')) || 0;
}

function calcularTotal() {

    let totalGeral = 0;
    let subtotalOperadoras = {};

    document.querySelectorAll('.money').forEach(el => {

        const valor = parseMoney(el.value);
        if (valor <= 0) return;

        const operadora = el.dataset.operadora || 'OUTROS';

        subtotalOperadoras[operadora] = (subtotalOperadoras[operadora] || 0) + valor;
        totalGeral += valor;
    });

    // Atualiza subtotais
    document.querySelectorAll('.subtotal').forEach(el => {
        const op = el.dataset.operadora;
        const valor = subtotalOperadoras[op] || 0;

        el.innerText = valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 2
        });
    });

    // Atualiza total geral
    document.getElementById('totalGeral').innerText =
        totalGeral.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

async function salvarFechamento() {

    const itens = [];

    document.querySelectorAll('.money').forEach(el => {

        const valor = parseMoney(el.value);
        if (valor <= 0) return;

        itens.push({
            operadora: el.dataset.operadora || null,
            meio: el.dataset.tipo || null,
            valor
        });
    });

    if (!itens.length) {
        alert('Informe ao menos um valor.');
        return;
    }

    const payload = {
        data: document.getElementById('data').value,
        observacoes: document.getElementById('observacoes').value,
        itens
    };

    const res = await fetch('../api/save_fechamento_caixa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const json = await res.json();

    if (json.success) {
        alert('Fechamento salvo com sucesso!');
        location.reload();
    } else {
        alert(json.error || 'Erro ao salvar fechamento.');
    }
}
