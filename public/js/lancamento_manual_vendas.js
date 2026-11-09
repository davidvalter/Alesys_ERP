// public/js/lancamento_manual_vendas.php

let itens = [];

function adicionarItem() {

    const produtoSelect = document.getElementById('produto');
    const produtoId = produtoSelect.value;
    const produtoNome = produtoSelect.options[produtoSelect.selectedIndex]?.text || '';
    const quantidade = parseFloat(document.getElementById('quantidade').value);
    const valorUnitario = parseFloat(document.getElementById('valorUnitario').value);

    if (!produtoId || quantidade <= 0 || valorUnitario <= 0) {
        alert('Preencha corretamente os dados do item.');
        return;
    }

    itens.push({
        produtoId,
        produtoNome,
        quantidade,
        valorUnitario
    });

    atualizarTabela();
}

function removerItem(index) {
    itens.splice(index, 1);
    atualizarTabela();
}

function atualizarTabela() {

    const tbody = document.querySelector('#tabelaItens tbody');
    tbody.innerHTML = '';

    let total = 0;

    itens.forEach((item, index) => {

        const subtotal = item.quantidade * item.valorUnitario;
        total += subtotal;

        tbody.innerHTML += `
            <tr>
                <td>${item.produtoNome}</td>
                <td>${item.quantidade}</td>
                <td>R$ ${item.valorUnitario.toFixed(2)}</td>
                <td>R$ ${subtotal.toFixed(2)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger" onclick="removerItem(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    document.getElementById('totalVenda').innerText = total.toFixed(2);
}

async function salvarVenda() {

    if (!itens.length) {
        alert('Adicione ao menos um item.');
        return;
    }

    const formaPagamento = document.getElementById('formaPagamento').value;
    if (!formaPagamento) {
        alert('Selecione a forma de pagamento.');
        return;
    }

    const payload = {
        formaPagamento,
        dataVenda: document.getElementById('dataVenda').value,
        observacoes: document.getElementById('observacoes').value,
        itens
    };

    const res = await fetch('../api/save_lancamento_manual_vendas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const json = await res.json();

    if (json.success) {
        alert('Venda registrada com sucesso!');
        window.location.reload();
    } else {
        alert(json.error || 'Erro ao registrar venda.');
    }
}