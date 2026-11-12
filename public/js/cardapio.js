let tabela;

document.addEventListener('DOMContentLoaded', () => {
    carregarTabela();
    bindEventos();
});

// =============================
// EVENTOS
// =============================
function bindEventos() {

    document.getElementById('btnNovo')?.addEventListener('click', abrirModal);

    document.getElementById('TipoItem')?.addEventListener('change', (e) => {
        toggleTipo(e.target.value);
        atualizarPreviewImagem();
    });
    document.getElementById('ProdutoId')?.addEventListener('change', atualizarPreviewImagem);
    document.getElementById('PratoId')?.addEventListener('change', atualizarPreviewImagem);

    document.getElementById('formCardapio')?.addEventListener('submit', (e) => {
        e.preventDefault(); // 🚨 IMPEDE reload da página
        salvar();
    });

bela;

document.addEventListener('DOMContentLoaded', () => {
    carregarTabela();
    bindEventos();
});

// =============================
// EVENTOS
// =============================
function bindEventos() {

    document.getElementById('btnNovo')?.addEventListener('click', abrirModal);

    document.getElementById('TipoItem')?.addEventListener('change', (e) => {
        toggleTipo(e.target.value);
        atualizarPreviewImagem();
    });
    document.getElementById('ProdutoId')?.addEventListener('change', atualizarPreviewImagem);
    document.getElementById('PratoId')?.addEventListener('change', atualizarPreviewImagem);

    document.getElementById('formCardapio')?.addEventListener('submit', (e) => {
        e.preventDefault(); // 🚨 IMPEDE reload da página
        salvar();
    });

    document.getElementById('ImagemFile')?.addEventListener('change', (e) => {
        const file = e.target.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = (ev) => {
            const img = document.getElementById('previewImagem');
            img.src = ev.target.result;
            img.style.display = 'block';
        };

        reader.readAsDataURL(file);
    });

}

// =============================
// DATATABLE
// =============================
function carregarTabela() {

    tabela = $('#tabelaCardapio').DataTable({
        ajax: {
            url: '/api/list_cardapio.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'Id' },
            { data: 'Nome' },
            { data: 'TipoItem' },
            { data: 'Categoria' },
            {
                data: 'PrecoVenda',
                render: d => 'R$ ' + parseFloat(d).toFixed(2)
            },
            {
                data: 'Ativo',
                render: d => d == 1 ? 'Ativo' : 'Inativo'
            },
            {
                data: null,
                render: (d) => `
                    <button class="btn btn-sm btn-success" onclick="editar(${d.Id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="remover(${d.Id})">
                        <i class="fas fa-trash"></i>
                    </button>
                `
            }
        ]
    });
}

// =============================
// MODAL
// =============================
async function abrirModal(dados = null) {

    // Carrega os vínculos ativos antes de abrir.
    await carregarProdutos();
    await carregarPratos();

    document.getElementById('formCardapio').reset();
    document.getElementById('Id').value = dados?.Id || '';
    document.getElementById('Nome').value = dados?.Nome || '';
    document.getElementById('TipoItem').value = dados?.TipoItem || '';
    document.getElementById('Categoria').value = dados?.Categoria || '';
    document.getElementById('PrecoVenda').value = dados?.PrecoVenda || '';
    document.getElementById('Ativo').value = dados?.Ativo ?? 1;

    // 🔹 Preenche selects no modo edição
    if (dados) {
        document.getElementById('ProdutoId').value = dados?.ProdutoId || '';
        document.getElementById('PratoId').value = dados?.PratoId || '';
    }

    toggleTipo(dados?.TipoItem);
    atualizarPreviewImagem(dados?.Imagem || '');
    $('#modalTitle').text(dados ? 'Editar Item' : 'Novo Item');
    $('#modalCardapio').modal('show');
}

// =============================
// TOGGLE TIPO
// =============================
function toggleTipo(tipo) {
    document.getElementById('boxProduto').style.display = tipo === 'produto' ? 'block' : 'none';
    document.getElementById('boxPrato').style.display = tipo === 'prato' ? 'block' : 'none';
}

function atualizarPreviewImagem(fallback = '') {
    const tipo = document.getElementById('TipoItem').value;
    const selectId = tipo === 'produto' ? 'ProdutoId' : (tipo === 'prato' ? 'PratoId' : null);
    const select = selectId ? document.getElementById(selectId) : null;
    const optionImagem = select?.selectedOptions?.[0]?.dataset?.imagem || '';
    const imagem = fallback || optionImagem || '/images/no-image.png';
    document.getElementById('previewImagem').src = imagem;
}

// =============================
// CARREGAR PRODUTOS
// =============================
async function carregarProdutos() {
    try {
        const res = await fetch('/api/list_produtos.php');
        const json = await res.json();

        const select = document.getElementById('ProdutoId');
        select.innerHTML = '<option value="">Selecione</option>';

        if (json.success && json.data) {
            json.data.forEach(p => {
                const option = new Option(p.Nome, p.Codigo);
                option.dataset.imagem = p.Imagem || '';
                select.add(option);
            });
        }

    } catch (err) {
        console.error('Erro ao carregar produtos', err);
    }
}

// =============================
// CARREGAR PRATOS
// =============================
async function carregarPratos() {
    try {
        const res = await fetch('/api/list_pratos.php');
        const json = await res.json();

        const select = document.getElementById('PratoId');
        select.innerHTML = '<option value="">Selecione</option>';

        if (json.success && json.data) {
            json.data.forEach(p => {
                const option = new Option(p.Nome, p.Id);
                option.dataset.imagem = p.Imagem || '';
                select.add(option);
            });
        }

    } catch (err) {
        console.error('Erro ao carregar pratos', err);
    }
}

// =============================
// SALVAR
// =============================
async function salvar() {

    const formData = new FormData();

    formData.append('Id', document.getElementById('Id').value || '');
    formData.append('Nome', document.getElementById('Nome').value);
    formData.append('TipoItem', document.getElementById('TipoItem').value);
    formData.append('ProdutoId', document.getElementById('ProdutoId').value || '');
    formData.append('PratoId', document.getElementById('PratoId').value || '');
    formData.append('Categoria', document.getElementById('Categoria').value);
    formData.append('PrecoVenda', document.getElementById('PrecoVenda').value);
    formData.append('Ativo', document.getElementById('Ativo').value);

    try {

        const res = await fetch('/api/save_cardapio.php', {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (!res.ok) {
            throw new Error(json.message || 'Erro ao salvar');
        }

        showToast(json.message || 'Salvo com sucesso', 'success');

        $('#modalCardapio').modal('hide');
        tabela.ajax.reload(null, false);

    } catch (err) {
        console.error(err);
        showToast(err.message || 'Erro ao salvar', 'danger');
    }
}

// =============================
// EDITAR
// =============================
function editar(id) {
    const data = tabela.rows().data().toArray().find(x => x.Id == id);
    abrirModal(data);
}

// =============================
// REMOVER
// =============================
async function remover(id) {

    if (!confirm('Deseja remover este item?')) return;

    try {

        const res = await fetch('/api/delete_cardapio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ Id: id })
        });

        const json = await res.json();

        if (!res.ok) {
            throw new Error(json.message);
        }

        if (json.success) {
            showToast('Item removido com sucesso', 'success');
            tabela.ajax.reload(null, false);
        } else {
            showToast(json.message, 'danger');
        }

    } catch (err) {
        console.error(err);
        showToast('Erro ao remover', 'danger');
    }
}

// =============================
// AVISOS
// =============================
function showToast(mensagem, tipo = 'primary') {

    const toastEl = document.getElementById('toastGlobal');
    const toastMsg = document.getElementById('toastMensagem');

    toastEl.className = `toast align-items-center text-bg-${tipo} border-0`;
    toastMsg.innerText = mensagem;

    const toast = new bootstrap.Toast(toastEl, {
        delay: 3000,      // ⏱️ 3 segundos
        autohide: true
    });

    toast.show();
}