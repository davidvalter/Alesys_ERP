let tabela;
const IMAGE_PLACEHOLDER = '/images/no-image.svg';

document.addEventListener('DOMContentLoaded', () => {
    // Registra os eventos primeiro para que uma falha na tabela não bloqueie o modal.
    bindEventos();
    try {
        carregarTabela();
    } catch (error) {
        console.error('Erro ao inicializar a tabela do cardápio:', error);
    }
});

function bindEventos() {
    document.getElementById('btnNovo')?.addEventListener('click', async event => {
        event.preventDefault();
        try {
            await abrirModal();
        } catch (error) {
            console.error('Erro ao abrir o modal do cardápio:', error);
            showToast(error.message || 'Não foi possível abrir o formulário.', 'danger');
        }
    });

    document.getElementById('TipoItem')?.addEventListener('change', event => {
        toggleTipo(event.target.value);
        atualizarPreviewImagem();
    });
    document.getElementById('ProdutoId')?.addEventListener('change', () => atualizarPreviewImagem());
    document.getElementById('PratoId')?.addEventListener('change', () => atualizarPreviewImagem());

    document.getElementById('formCardapio')?.addEventListener('submit', event => {
        event.preventDefault();
        salvar();
    });
}

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
            { data: 'Categoria', defaultContent: '' },
            {
                data: 'PrecoVenda',
                render: valor => 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })
            },
            {
                data: 'Ativo',
                render: valor => Number(valor) === 1 ? 'Ativo' : 'Inativo'
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: item => `
                    <button class="btn btn-sm btn-success" type="button" onclick="editar(${Number(item.Id)})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" type="button" onclick="remover(${Number(item.Id)})" title="Remover">
                        <i class="fas fa-trash"></i>
                    </button>
                `
            }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json' }
    });
}

async function abrirModal(dados = null) {
    const form = document.getElementById('formCardapio');
    const tipo = dados?.TipoItem || '';
    form.reset();
    document.getElementById('Id').value = dados?.Id || '';
    document.getElementById('Nome').value = dados?.Nome || '';
    document.getElementById('TipoItem').value = tipo;
    document.getElementById('Categoria').value = dados?.Categoria || '';
    document.getElementById('PrecoVenda').value = dados?.PrecoVenda ?? '';
    document.getElementById('Ativo').value = dados?.Ativo ?? 1;
    document.getElementById('ProdutoId').replaceChildren(new Option('Carregando...', ''));
    document.getElementById('PratoId').replaceChildren(new Option('Carregando...', ''));
    toggleTipo(tipo);
    atualizarPreviewImagem(dados?.Imagem || '');
    document.getElementById('modalTitle').textContent = dados ? 'Editar Item' : 'Novo Item';

    // Usa a API explícita do Bootstrap 5, já adotada pelo módulo de Produtos.
    obterModalCardapio().show();
    await Promise.all([carregarProdutos(), carregarPratos()]);

    if (dados) {
        document.getElementById('ProdutoId').value = dados.ProdutoId || '';
        document.getElementById('PratoId').value = dados.PratoId || '';
    }

    toggleTipo(tipo);
    atualizarPreviewImagem(dados?.Imagem || '');
}

function obterModalCardapio() {
    const modalElement = document.getElementById('modalCardapio');
    const Modal = window.bootstrap && window.bootstrap.Modal;
    if (!Modal) {
        throw new Error('O componente Modal do Bootstrap não foi carregado.');
    }
    return Modal.getInstance(modalElement) || new Modal(modalElement);
}

function toggleTipo(tipo) {
    document.getElementById('boxProduto').style.display = tipo === 'produto' ? 'block' : 'none';
    document.getElementById('boxPrato').style.display = tipo === 'prato' ? 'block' : 'none';
}

function atualizarPreviewImagem(fallback = '') {
    const tipo = document.getElementById('TipoItem').value;
    const idSelect = tipo === 'produto' ? 'ProdutoId' : (tipo === 'prato' ? 'PratoId' : null);
    const imagemAssociada = idSelect
        ? (document.getElementById(idSelect).selectedOptions[0]?.dataset.imagem || '')
        : '';
    const preview = document.getElementById('previewImagem');
    preview.onerror = () => {
        preview.onerror = null;
        preview.src = IMAGE_PLACEHOLDER;
    };
    preview.src = fallback || imagemAssociada || IMAGE_PLACEHOLDER;
}

async function carregarProdutos() {
    const select = document.getElementById('ProdutoId');
    select.replaceChildren(new Option('Selecione', ''));
    try {
        const response = await fetch('/api/list_produtos.php', { cache: 'no-store' });
        const json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.message || 'Erro ao carregar produtos.');
        (json.data || []).forEach(produto => {
            const option = new Option(produto.Nome, produto.Codigo);
            option.dataset.imagem = produto.Imagem || '';
            select.add(option);
        });
    } catch (error) {
        console.error('Erro ao carregar produtos do cardápio:', error);
        showToast(error.message || 'Erro ao carregar produtos.', 'danger');
    }
}

async function carregarPratos() {
    const select = document.getElementById('PratoId');
    select.replaceChildren(new Option('Selecione', ''));
    try {
        const response = await fetch('/api/list_pratos.php', { cache: 'no-store' });
        const json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.message || 'Erro ao carregar pratos.');
        (json.data || []).forEach(prato => {
            const option = new Option(prato.Nome, prato.Id);
            option.dataset.imagem = prato.Imagem || '';
            select.add(option);
        });
    } catch (error) {
        console.error('Erro ao carregar pratos do cardápio:', error);
        showToast(error.message || 'Erro ao carregar pratos.', 'danger');
    }
}

async function salvar() {
    const form = document.getElementById('formCardapio');
    const submit = document.querySelector('button[form="formCardapio"][type="submit"]');
    const formData = new FormData();
    formData.append('Id', document.getElementById('Id').value || '');
    formData.append('Nome', document.getElementById('Nome').value);
    formData.append('TipoItem', document.getElementById('TipoItem').value);
    formData.append('ProdutoId', document.getElementById('ProdutoId').value || '');
    formData.append('PratoId', document.getElementById('PratoId').value || '');
    formData.append('Categoria', document.getElementById('Categoria').value);
    formData.append('PrecoVenda', document.getElementById('PrecoVenda').value);
    formData.append('Ativo', document.getElementById('Ativo').value);

    if (submit) {
        submit.disabled = true;
        submit.dataset.labelOriginal = submit.innerHTML;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando';
    }

    try {
        const response = await fetch('/api/save_cardapio.php', { method: 'POST', body: formData });
        const json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.message || 'Erro ao salvar o item.');

        obterModalCardapio().hide();
        showToast(json.message || 'Item salvo com sucesso.', 'success');
        tabela.ajax.reload(null, false);
    } catch (error) {
        console.error(error);
        showToast(error.message || 'Erro ao salvar.', 'danger');
    } finally {
        if (submit) {
            submit.disabled = false;
            submit.innerHTML = submit.dataset.labelOriginal || '<i class="fas fa-save me-1"></i>Salvar';
        }
    }
}

function editar(id) {
    const item = tabela?.rows().data().toArray().find(row => Number(row.Id) === Number(id));
    if (!item) return;
    abrirModal(item).catch(error => {
        console.error('Erro ao abrir a edição do item do cardápio:', error);
        showToast(error.message || 'Não foi possível abrir o formulário.', 'danger');
    });
}

async function remover(id) {
    if (!confirm('Deseja remover este item?')) return;
    try {
        const response = await fetch('/api/delete_cardapio.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ Id: id })
        });
        const json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.message || 'Erro ao remover.');
        showToast('Item removido com sucesso.', 'success');
        tabela.ajax.reload(null, false);
    } catch (error) {
        console.error(error);
        showToast(error.message || 'Erro ao remover.', 'danger');
    }
}

function showToast(mensagem, tipo = 'primary') {
    const toastEl = document.getElementById('toastGlobal');
    const toastMsg = document.getElementById('toastMensagem');
    toastEl.className = `toast align-items-center text-bg-${tipo} border-0`;
    toastMsg.textContent = mensagem;
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3000, autohide: true }).show();
}
