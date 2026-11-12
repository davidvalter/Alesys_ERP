// public/js/cadastro_pratos.js

/* ==================== ABRIR MODAL ==================== */

function abrirModalNovo() {
    document.getElementById('formProduto').reset();
    document.getElementById('fotoPreview').src = '/images/no-image.svg';
    document.getElementById('produtoId').value = '';
    document.querySelector('#modalExemplo .modal-title').innerText = 'Cadastrar Produto';
    carregarIngredientes();
    document.getElementById("receita-lista").innerHTML = "";
    limparAbaFiscal();
    abrirAba('dados');
    abrirModal('modalExemplo');
}

function abrirModalEditar(id) {
    // Correção: o modal real é 'modalExemplo' (antes usava 'modalProduto', que não existe)
    abrirModal('modalExemplo');
    document.querySelector('#modalExemplo .modal-title').innerText = 'Editar Produto #' + id;
    document.getElementById('formProduto').reset();
    document.getElementById('produtoId').value = id;

    fetch("../api/get_prato.php?id=" + id)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const prato = res.data;
                document.getElementById("nome").value = prato.Nome;
                document.getElementById("categoria").value = prato.Categoria;
                document.getElementById("preco").value = prato.Preco;
                document.getElementById("fotoPreview").src = prato.Foto || '/images/no-image.svg';
                preencherAbaFiscal(prato);
            } else {
                showToast("Erro ao carregar dados do prato.", "error");
            }
        })
        .catch(err => showToast("Erro ao buscar prato: " + err.message, "error"));

    carregarIngredientes();
    carregarReceita(id);
    abrirAba('dados');
}

/* ==================== ABA FISCAL ==================== */

function limparAbaFiscal() {
    ['ncm', 'cfop', 'unidade_medida'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const csosn = document.getElementById('csosn');
    if (csosn) csosn.value = '';
    const ncmDesc = document.getElementById('ncmDescricao');
    if (ncmDesc) ncmDesc.textContent = '';
    const cestDesc = document.getElementById('cestDescricao');
    if (cestDesc) cestDesc.textContent = '';
    const cest = document.getElementById('cest');
    if (cest) {
        cest.innerHTML = '<option value="">— Selecione o NCM primeiro —</option>';
        cest.disabled = true;
    }
}

function preencherAbaFiscal(p) {
    document.getElementById('ncm').value = p.NCM || '';
    document.getElementById('cfop').value = p.CFOP || '';
    document.getElementById('csosn').value = p.CSOSN || '';
    document.getElementById('unidade_medida').value = p.UnidadeMedida || 'UN';
    document.getElementById('ncmDescricao').textContent = '';
    if (p.NCM) {
        // carrega o CEST e seleciona o valor já salvo
        carregarCest(p.NCM).then(() => {
            if (p.CEST) document.getElementById('cest').value = p.CEST;
        });
    } else {
        const cest = document.getElementById('cest');
        cest.innerHTML = '<option value="">— Selecione o NCM primeiro —</option>';
        cest.disabled = true;
    }
}

/* ==================== AÇÕES DO PRATO ==================== */

function excluirPrato(id) {
    if (!confirm("Tem certeza que deseja excluir permanentemente este prato?")) return;
    fetch("../api/delete_prato.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, "success");
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(res.error || "Erro ao excluir prato.", "error");
            }
        })
        .catch(err => showToast("Erro na requisição: " + err.message, "error"));
}

function inativarPrato(id) {
    fetch("../api/inativar_prato.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, "warning");
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(res.error || "Erro ao inativar prato.", "error");
            }
        })
        .catch(err => showToast("Erro na requisição: " + err.message, "error"));
}

function restorePrato(id) {
    fetch("../api/restore_prato.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, "success");
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(res.error || "Erro ao restaurar prato.", "error");
            }
        })
        .catch(err => showToast("Erro na requisição: " + err.message, "error"));
}

/* ==================== TOAST ==================== */

function showToast(message, type = "success") {
    const container = document.getElementById("toastContainer");
    const toast = document.createElement("div");
    toast.classList.add("toast", `toast-${type}`);
    toast.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 4000);
}

/* ==================== SALVAR PRATO ==================== */

document.getElementById('foto').addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = event => document.getElementById('fotoPreview').src = event.target.result;
    reader.readAsDataURL(file);
});

document.getElementById('formProduto').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch("../api/save_prato.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast("Prato salvo com sucesso!", "success");
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(res.error || "Erro ao salvar prato.", "error");
            }
        })
        .catch(err => showToast("Erro na requisição: " + err.message, "error"));
});

/* ==================== ABAS ==================== */

function abrirAba(aba) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelector(`.tab-btn[onclick="abrirAba('${aba}')"]`).classList.add('active');
    document.getElementById('aba-' + aba).classList.add('active');
}

/* ==================== RECEITA ==================== */

function carregarIngredientes() {
    fetch("../api/get_produtos_dashboard.php")
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const select = document.getElementById("ingredienteId");
                select.innerHTML = "";
                res.data.forEach(prod => {
                    const opt = document.createElement("option");
                    opt.value = prod.Codigo;
                    opt.textContent = prod.Nome;
                    select.appendChild(opt);
                });
            }
        });
}

function carregarReceita(pratoId) {
    fetch("../api/get_receita.php?pratoId=" + pratoId)
        .then(res => res.json())
        .then(res => {
            const tbody = document.getElementById("receita-lista");
            tbody.innerHTML = "";
            if (res.success) {
                res.data.forEach(item => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                    <td>${item.IngredienteNome}</td>
                    <td>${item.Quantidade}</td>
                    <td>${item.UnidadeMedida}</td>
                    <td>${item.PercentualPerda}%</td>
                    <td>${item.MetodoBaixa}</td>
                    <td><button type="button" class="btn btn-danger" onclick="removerIngrediente(${item.Id})">X</button></td>
                `;
                    tbody.appendChild(tr);
                });
            }
        });
}

function adicionarIngrediente() {
    const pratoId = document.getElementById("produtoId").value;
    const ingredienteId = document.getElementById("ingredienteId").value;
    const quantidade = document.getElementById("quantidade").value;
    const unidade = document.getElementById("unidade").value;
    const perda = document.getElementById("perda").value;
    const metodo = document.getElementById("metodo").value;
    fetch("../api/save_receita.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            pratoId, ingredienteId, quantidade, unidade, perda, metodo
        })
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast("Ingrediente adicionado!", "success");
                carregarReceita(pratoId);
            } else {
                showToast(res.error, "error");
            }
        });
}

function removerIngrediente(id) {
    fetch("../api/delete_receita.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, "warning");
                const pratoId = document.getElementById("produtoId").value;
                carregarReceita(pratoId);
            } else {
                showToast(res.error, "error");
            }
        });
}

/* ==================== BUSCA DE NCM / CEST ==================== */
const NCM_BUSCA_ENDPOINT = "../api/get_ncm.php";
const CEST_BUSCA_ENDPOINT = "../api/get_cest.php";

let ncmPaginaAtual = 1;
let ncmTotalPaginas = 1;
let cestRequestSeq = 0;

// Helpers de modal (só define se ainda não existirem no arquivo)
if (typeof abrirModal === 'undefined') {
    window.abrirModal = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        } else {
            el.style.display = 'block';
            el.classList.add('show');
        }
    };
    window.fecharModal = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        if (window.bootstrap && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        } else {
            el.style.display = 'none';
        }
    };
}

function abrirBuscaNcm() {
    // Esconde o modal do prato para o seletor de NCM ficar sozinho na tela
    fecharModal('modalExemplo');

    const campo = document.getElementById('ncmBusca');
    campo.value = '';
    document.getElementById('ncmResultados').innerHTML =
        '<tr><td colspan="3" class="text-center text-muted">Digite um termo e clique em Pesquisar</td></tr>';
    document.getElementById('ncmContador').textContent = '';
    document.getElementById('ncmAnterior').disabled = true;
    document.getElementById('ncmProximo').disabled = true;
    ncmPaginaAtual = 1;
    ncmTotalPaginas = 1;
    abrirModal('modalBuscaNcm');
    setTimeout(() => campo.focus(), 150);
}

function pesquisarNcm(pagina = 1) {
    const termo = document.getElementById('ncmBusca').value.trim();
    if (!termo) {
        showToast('Digite um código ou descrição para pesquisar.', 'error');
        return;
    }
    fetch(NCM_BUSCA_ENDPOINT + '?termo=' + encodeURIComponent(termo) + '&pagina=' + pagina)
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                showToast(res.message || 'Erro ao buscar NCM.', 'error');
                return;
            }
            ncmPaginaAtual = res.pagina || pagina;
            ncmTotalPaginas = res.totalPaginas || 1;
            renderNcmResultados(res.dados || []);
            document.getElementById('ncmContador').textContent =
                'Página ' + ncmPaginaAtual + ' de ' + ncmTotalPaginas + ' (' + (res.total || 0) + ' NCMs)';
            document.getElementById('ncmAnterior').disabled = ncmPaginaAtual <= 1;
            document.getElementById('ncmProximo').disabled = ncmPaginaAtual >= ncmTotalPaginas;
        })
        .catch(err => showToast('Erro na requisição: ' + err.message, 'error'));
}

function renderNcmResultados(lista) {
    const tbody = document.getElementById('ncmResultados');
    if (!lista.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Nenhum NCM encontrado</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(item => {
        const codigo = item.Codigo || '';
        const descricao = (item.Descricao || '').replace(/'/g, "\'");
        return '<tr>' +
            '<td class="font-monospace">' + codigo + '</td>' +
            '<td>' + (item.Descricao || '') + '</td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-primary" ' +
            'onclick="selecionarNcm(\'' + codigo + '\', \'' + descricao + '\')">Selecionar</button></td>' +
            '</tr>';
    }).join('');
}

function selecionarNcm(codigo, descricao) {
    document.getElementById('ncm').value = codigo;
    document.getElementById('ncmDescricao').textContent = descricao || '';
    carregarCest(codigo);
    fecharModal('modalBuscaNcm');
    abrirModal('modalExemplo'); // devolve o modal do prato com os campos preenchidos
    showToast('NCM selecionado: ' + codigo, 'success');
}

function carregarCest(ncm) {
    const cestSelect = document.getElementById('cest');
    const seq = ++cestRequestSeq;
    if (!ncm) {
        cestSelect.innerHTML = '<option value="">— Selecione o NCM primeiro —</option>';
        cestSelect.disabled = true;
        return Promise.resolve();
    }
    cestSelect.innerHTML = '<option value="">Carregando CEST...</option>';
    cestSelect.disabled = true;
    return fetch(CEST_BUSCA_ENDPOINT + '?ncm=' + encodeURIComponent(ncm))
        .then(res => res.json())
        .then(res => {
            if (seq !== cestRequestSeq) return;
            if (!res.success) {
                cestSelect.innerHTML = '<option value="">Nenhum CEST para este NCM</option>';
                cestSelect.disabled = true;
                return;
            }
            const lista = res.dados || [];
            if (!lista.length) {
                cestSelect.innerHTML = '<option value="">Nenhum CEST para este NCM</option>';
                cestSelect.disabled = true;
                return;
            }
            cestSelect.innerHTML = '<option value="">— Selecione —</option>' + lista.map(item => {
                return '<option value="' + item.Codigo + '">' + item.Codigo + ' - ' + item.Descricao + '</option>';
            }).join('');
            cestSelect.disabled = false;
        })
        .catch(() => {
            if (seq !== cestRequestSeq) return;
            cestSelect.innerHTML = '<option value="">Erro ao carregar CEST</option>';
            cestSelect.disabled = true;
        });
}

/* ==================== INICIALIZAÇÃO BUSCA NCM ==================== */
(function initBuscaNcm() {
    const btnPesquisar = document.getElementById('btnPesquisarNcm');
    const btnAnterior = document.getElementById('ncmAnterior');
    const btnProximo = document.getElementById('ncmProximo');
    const campoBusca = document.getElementById('ncmBusca');
    if (btnPesquisar) btnPesquisar.addEventListener('click', () => pesquisarNcm(1));
    if (btnAnterior) btnAnterior.addEventListener('click', () => pesquisarNcm(ncmPaginaAtual - 1));
    if (btnProximo) btnProximo.addEventListener('click', () => pesquisarNcm(ncmPaginaAtual + 1));
    if (campoBusca) campoBusca.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); pesquisarNcm(1); }
    });

})();

/* ==================== RESTAURAR MODAL DO PRATO AO FECHAR BUSCA NCM ==================== */
(function restaurarModalPrato() {
    const ncmModal = document.getElementById('modalBuscaNcm');
    if (!ncmModal) return;
    ncmModal.addEventListener('hidden.bs.modal', function () {
        const pratoModal = document.getElementById('modalExemplo');
        if (pratoModal && !pratoModal.classList.contains('show')) {
            abrirModal('modalExemplo');
        }
    });
})();