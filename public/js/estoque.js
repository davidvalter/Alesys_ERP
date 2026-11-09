/* /public/js/estoque.js */

function carregarEstoque() {
    fetch("../api/get_estoque.php")
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById("estoque-lista");
            tbody.innerHTML = "";
            if (res.success) {
                res.data.forEach(item => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${item.Nome}</td>
                        <td>${item.Categoria || "-"}</td>
                        <td>${item.UnidadeMedida || "-"}</td>
                        <td>${formatarNumero(item.EstoqueAtual)}</td>
                        <td>R$ ${item.PrecoMedio ? formatarNumero(item.PrecoMedio) : "-"}</td>
                        <td>${item.UltimaMovimentacao || "-"}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5">${res.error}</td></tr>`;
            }
        })
        .catch(err => {
            showToast("Erro ao carregar estoque: " + err.message, "error");
        });
}

const formatarNumero = (valor) => {
    return new Intl.NumberFormat("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 3
    }).format(valor);
};

// Filtrar produtos na tabela
document.getElementById("searchProduto").addEventListener("input", function() {
    const filtro = this.value.toLowerCase();
    const linhas = document.querySelectorAll("#estoque-lista tr");

    linhas.forEach(linha => {
        const textoLinha = linha.innerText.toLowerCase();
        linha.style.display = textoLinha.includes(filtro) ? "" : "none";
    });
});


// Abrir modal de baixa
function abrirModalBaixa() {
    // Carregar produtos no select
    fetch("../api/get_produtos_dashboard.php")
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById("produtoBaixa");
            select.innerHTML = '<option value="">Selecione</option>';
            data.data.forEach(prod => {
                select.innerHTML += `<option value="${prod.Codigo}">${prod.Nome}</option>`;
            });
        });
    document.getElementById("modalBaixa").style.display = "flex";
}

function fecharModalBaixa() {
    document.getElementById("modalBaixa").style.display = "none";
}

// Salvar baixa manual
function salvarBaixaManual(event) {
    event.preventDefault();
    const form = document.getElementById("formBaixaManual");
    const formData = new FormData(form);

    fetch("../api/baixa_manual_estoque.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Baixa registrada com sucesso!");
            location.reload();
        } else {
            alert("Erro: " + data.error);
        }
    });
}



document.addEventListener("DOMContentLoaded", carregarEstoque);
