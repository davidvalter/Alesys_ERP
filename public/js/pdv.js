/**
 * Módulo de Gerenciamento do PDV
 * 
 * @file /js/pdv.js
 */

const PDV = {

    carrinho: [],
    produtos: [],
    formaPagamentoSelecionada: null,
    pagamentos: [],

    // =============================
    // INIT
    // =============================
    init() {
        this.bindEventos();
        this.carregarProdutos();
    },

    // =============================
    // EVENTOS
    // =============================
    bindEventos() {

        document.getElementById('buscaProduto')
            ?.addEventListener('input', (e) => {
                this.filtrarProdutos(e.target.value);
            });

        document.getElementById('btnFinalizar')
            ?.addEventListener('click', () => {
                this.finalizarVenda();
            });
    },

    // =============================
    // API
    // =============================
    async carregarProdutos() {

        try {

            const res = await fetch('/api/list_pdv.php');
            const json = await res.json();

            if (!json.success) {
                throw new Error(json.message);
            }

            this.produtos = json.data;

            this.renderProdutos(this.produtos);
            this.renderCategorias(this.produtos);

        } catch (err) {
            console.error('Erro ao carregar produtos:', err);
            alert('Erro ao carregar produtos do PDV');
        }
    },

    // =============================
    // RENDER PAGAMENTOS
    // =============================

    renderPagamentos() {

        const container = document.getElementById('listaPagamentos');
        container.innerHTML = '';

        this.pagamentos.forEach((p, index) => {
            container.innerHTML += `
            <div class="d-flex justify-content-between mb-1">
                <span>${p.tipo}</span>
                <span>R$ ${p.valor.toFixed(2)}</span>
            </div>
        `;
        });

        const restante = this.getTotalRestante();
        const resumo = document.getElementById('resumoPagamento');

        if (restante > 0) {
            resumo.innerHTML = `Falta pagar: R$ ${restante.toFixed(2)}`;
            resumo.className = 'text-danger';
        } else if (restante < 0) {
            resumo.innerHTML = `Troco: R$ ${Math.abs(restante).toFixed(2)}`;
            resumo.className = 'text-success';
        } else {
            resumo.innerHTML = `Pagamento completo`;
            resumo.className = 'text-success';
        }
    },

    // =============================
    // ABRIR MODAL DE PAGAMENTOS
    // =============================

    abrirModal() {
        const modal = new bootstrap.Modal(document.getElementById('modalPagamento'));
        modal.show();

        document.getElementById('totalModal').innerText = this.getTotal().toFixed(2);

        this.pagamentos = [];
        this.renderPagamentos();
    },

    // =============================
    // RENDER PRODUTOS
    // =============================
    renderProdutos(produtos) {

        const grid = document.getElementById('gridProdutos');
        grid.innerHTML = '';

        produtos.forEach(p => {

            const img = this.escapeHtml(p.Imagem || '/images/no-image.svg');
            const nome = this.escapeHtml(p.Nome);

            grid.innerHTML += `
                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="card produto-card h-100 cursor-pointer" onclick="PDV.addProduto(${p.Id})">

                        <div class="produto-img-wrapper">
                            <img src="${img}" class="produto-img" alt="${nome}" width="160" height="100" loading="lazy" decoding="async"
                                onerror="this.onerror=null;this.src='/images/no-image.svg'">
                        </div>

                        <div class="card-body text-center">
                            <div class="produto-nome">${nome}</div>
                            <div class="produto-preco">
                                R$ ${parseFloat(p.PrecoVenda).toFixed(2)}
                            </div>
                        </div>

                    </div>
                </div>
            `;
        });
    },

    escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    },

    // =============================
    // CATEGORIAS
    // =============================
    renderCategorias(produtos) {

        const container = document.getElementById('categorias');

        let categorias = [...new Set(
            produtos.map(p => (p.Categoria || 'Outros').toLowerCase())
        )];

        // Ordem fixa desejada
        const ordem = [
            'prato',
            'prato_principal',
            'entrada',
            'sobremesa',
            'bebida',
            'outros'
        ];

        categorias.sort((a, b) => {
            const indexA = ordem.indexOf(a);
            const indexB = ordem.indexOf(b);

            // Se não existir na lista, joga pro final
            return (indexA === -1 ? 999 : indexA) - (indexB === -1 ? 999 : indexB);
        });

        container.innerHTML = '';

        categorias.forEach((cat, index) => {

            const label = cat.charAt(0).toUpperCase() + cat.slice(1);

            container.innerHTML += `
            <button 
                class="btn ${index === 0 ? 'btn-primary' : 'btn-outline-secondary'} btn-sm categoria-btn"
                onclick="PDV.filtrarCategoria('${cat}')"
            >
                ${label}
            </button>
        `;
        });

        // Sempre inicia com a primeira (Pratos)
        if (categorias.length > 0) {
            this.filtrarCategoria(categorias[0]);
        }
    },

    filtrarCategoria(cat) {

        if (!cat) {
            this.renderProdutos(this.produtos);
            return;
        }

        const filtrados = this.produtos.filter(p =>
            (p.Categoria || '').toLowerCase() === cat
        );

        this.renderProdutos(filtrados);
    },

    // =============================
    // CARRINHO
    // =============================
    addProduto(id) {

        const produto = this.produtos.find(p => p.Id == id);

        if (!produto) return;

        const item = this.carrinho.find(i => i.id == id);

        if (item) {
            item.qtd++;
        } else {
            this.carrinho.push({
                id: produto.Id,
                nome: produto.Nome,
                preco: parseFloat(produto.PrecoVenda),
                tipo: produto.TipoItem,
                qtd: 1
            });
        }

        this.renderCarrinho();
    },

    aumentar(index) {
        this.carrinho[index].qtd++;
        this.renderCarrinho();
    },

    diminuir(index) {

        if (this.carrinho[index].qtd > 1) {
            this.carrinho[index].qtd--;
        } else {
            this.carrinho.splice(index, 1);
        }

        this.renderCarrinho();
    },

    removerItem(index) {
        this.carrinho.splice(index, 1);
        this.renderCarrinho();
    },

    renderCarrinho() {

        const container = document.getElementById('carrinhoItens');
        container.innerHTML = '';

        let total = 0;

        this.carrinho.forEach((item, index) => {

            const subtotal = item.preco * item.qtd;
            total += subtotal;

            container.innerHTML += `
                <div class="border-bottom pb-2 mb-2">

                    <div class="d-flex justify-content-between">
                        <strong>${item.nome}</strong>
                        <span>R$ ${subtotal.toFixed(2)}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-1 gap-2">

                        <div class="input-group input-group-sm" style="width: 110px;">

                            <button class="btn btn-outline-secondary" onclick="PDV.diminuir(${index})">
                                -
                            </button>

                            <input type="text" 
                                class="form-control text-center" 
                                value="${item.qtd}" 
                                readonly>

                            <button class="btn btn-outline-secondary" onclick="PDV.aumentar(${index})">
                                +
                            </button>

                        </div>

                        <button class="btn btn-sm btn-outline-danger" onclick="PDV.removerItem(${index})">
                            <i class="fas fa-trash"></i>
                        </button>

                    </div>

                </div>
            `;
        });

        document.getElementById('totalVenda').innerText =
            'R$ ' + total.toFixed(2);
    },

    // =============================
    // FILTRO TEXTO
    // =============================
    filtrarProdutos(termo) {

        termo = termo.toLowerCase();

        const filtrados = this.produtos.filter(p =>
            p.Nome.toLowerCase().includes(termo)
        );

        this.renderProdutos(filtrados);
    },

    // =============================
    // TOTAL
    // =============================
    getTotal() {
        return this.carrinho.reduce((t, i) => t + (i.preco * i.qtd), 0);
    },

    toggleDocumento(mostrar) {
        const input = document.getElementById('clienteDocumento');

        if (mostrar) {
            input.classList.remove('d-none');
            input.focus();
        } else {
            input.classList.add('d-none');
            input.value = '';
        }
    },

    selecionarPagamento(tipo) {

        this.formaPagamentoSelecionada = tipo;

        document.querySelectorAll('.pagamento-card')
            .forEach(el => el.classList.remove('active'));

        event.currentTarget.classList.add('active');

        const areaValor = document.getElementById('areaValor');

        if (tipo === 'dinheiro') {
            areaValor.classList.remove('d-none');
        } else {
            areaValor.classList.add('d-none');
        }
    },

    // =============================
    // CALCULO DOS TOTAIS
    // =============================

    getTotalPago() {
        return this.pagamentos.reduce((t, p) => t + p.valor, 0);
    },

    getTotalRestante() {
        return this.getTotal() - this.getTotalPago();
    },

    // =============================
    // ADICIONAR PAGAMENTO
    // =============================

    adicionarPagamento() {

        if (!this.formaPagamentoSelecionada) {
            alert('Selecione a forma de pagamento');
            return;
        }

        let valor = this.getTotalRestante();

        if (this.formaPagamentoSelecionada === 'dinheiro') {
            valor = parseFloat(document.getElementById('valorPagamento').value || 0);

            if (valor <= 0) {
                alert('Informe o valor recebido');
                return;
            }
        }

        this.pagamentos.push({
            tipo: this.formaPagamentoSelecionada,
            valor: valor
        });

        document.getElementById('valorPagamento').value = '';

        this.renderPagamentos();
    },

    // =============================
    // FLUXO VENDA
    // =============================
    finalizarVenda() {

        if (this.carrinho.length === 0) {
            alert('Carrinho vazio');
            return;
        }

        this.abrirModal();
    },

    async confirmarVenda() {
        const cliente = document.getElementById('clienteDocumento').value;
        if (!this.pagamentos || this.pagamentos.length === 0) {
            alert('Adicione pelo menos um pagamento');
            return;
        }
        if (this.getTotalRestante() > 0) {
            alert('Pagamento incompleto');
            return;
        }
        const payload = {
            itens: this.carrinho.map(i => ({
                id: i.id,
                preco: i.preco,
                qtd: i.qtd,
                tipo: i.tipo
            })),
            pagamentos: this.pagamentos,
            cliente: cliente,
            tipo: 'balcao'
        };
        try {
            const res = await fetch('/api/save_venda.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (!res.ok) {
                throw new Error(json.message);
            }
            // ===== NOVO: integração ALENFE =====
            // save_venda.php retorna json.pedidoId
            const pedidoId = json.pedidoId ?? null;
            this.reset(); // libera o caixa imediatamente
            if (pedidoId && typeof PDVNFCE !== 'undefined' && PDVNFCE.emitir) {
                PDVNFCE.emitir(pedidoId); // abre "aguardando" e depois a nota automaticamente
            } else {
                alert('Venda finalizada com sucesso!');
            }
            // ===== FIM NOVO =====
        } catch (err) {
            console.error(err);
            alert(err.message || 'Erro ao finalizar venda');
        }
    },

    // =============================
    // RESET PDV
    // =============================
    reset() {

        this.carrinho = [];
        this.renderCarrinho();

        this.formaPagamentoSelecionada = null;

        document.querySelectorAll('.pagamento-card')
            .forEach(el => el.classList.remove('active'));

        document.getElementById('clienteDocumento').value = '';

        const modalEl = document.getElementById('modalPagamento');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    }
};



// =============================
// INIT GLOBAL
// =============================
document.addEventListener('DOMContentLoaded', () => PDV.init());