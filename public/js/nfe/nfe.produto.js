/**
 * Autocomplete de Produtos com navegação por teclado
 * 
 * @file /js/nfe/nfe.produto.js
 */

const NFEProduto = {

    debounceTimer: null,

    init() {
        this.bindAutocomplete();
    },

    bindAutocomplete() {

        // INPUT DIGITAÇÃO
        $(document).on('input', '.produto-search-input', (e) => {

            const input = $(e.currentTarget);
            const termo = input.val();

            clearTimeout(this.debounceTimer);

            this.debounceTimer = setTimeout(() => {

                if (termo.length < 2) {
                    this.removeLista(input);
                    return;
                }

                $.get('/api/search_produtos.php', {
                    search: termo,
                    page: 1
                }, (res) => {

                    this.removeLista(input);

                    if (!res.success || !res.results.length) return;

                    const lista = $(`
                        <div class="autocomplete-list list-group position-absolute w-100 shadow-sm"></div>
                    `);

                    res.results.forEach((prod, index) => {

                        const item = $(`
                            <button type="button" class="list-group-item list-group-item-action">
                                ${prod.text}
                            </button>
                        `);

                        // seleção por clique
                        item.on('click', () => {
                            this.selecionarProduto(input, prod);
                        });

                        lista.append(item);
                    });

                    input.after(lista);

                });

            }, 300);
        });

        // TECLADO
        $(document).on('keydown', '.produto-search-input', (e) => {

            const input = $(e.currentTarget);
            const lista = input.next('.autocomplete-list');

            if (!lista.length) return;

            const items = lista.find('.list-group-item');
            let active = items.filter('.active');

            switch (e.key) {

                case 'ArrowDown':
                    e.preventDefault();

                    if (!active.length) {
                        items.first().addClass('active');
                    } else {
                        const next = active.removeClass('active').next();
                        (next.length ? next : items.first()).addClass('active');
                    }
                    break;

                case 'ArrowUp':
                    e.preventDefault();

                    if (!active.length) {
                        items.last().addClass('active');
                    } else {
                        const prev = active.removeClass('active').prev();
                        (prev.length ? prev : items.last()).addClass('active');
                    }
                    break;

                case 'Enter':
                    e.preventDefault();

                    if (active.length) {
                        active.click();
                    }
                    break;

                case 'Escape':
                    this.removeLista(input);
                    break;
            }
        });

        // HOVER COM MOUSE
        $(document).on('mouseenter', '.autocomplete-list .list-group-item', function () {
            $(this).addClass('active').siblings().removeClass('active');
        });

        // CLICK FORA
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.produto-search-input').length) {
                $('.autocomplete-list').remove();
            }
        });
    },

    selecionarProduto(input, prod) {

        const container = input.closest('.item-nota');

        input.val(prod.text);
        container.find('.produto-id').val(prod.id);
        container.find('.unidade-medida').val(prod.UnidadeMedida || 'UN');

        this.removeLista(input);

        // 👉 foco automático no próximo campo (UX top)
        container.find('.quantidade').focus();
    },

    removeLista(input) {
        input.next('.autocomplete-list').remove();
    }

};