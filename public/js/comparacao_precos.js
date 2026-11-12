/* /public/js/comparacao_precos.js */
$(function () {
    const form = document.getElementById('formFiltros');
    const button = document.getElementById('btnGerarComparacao');
    let chart = null;
    let table = null;
    let currentRows = [];
    const colors = ['#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#0dcaf0', '#d63384', '#6c757d', '#20c997'];

    function picker(root, inputName, placeholder, optional) {
        const search = root.querySelector('.multi-picker-search');
        const menu = root.querySelector('.multi-picker-menu');
        const optionsBox = root.querySelector('.multi-picker-options');
        const selectedBox = root.querySelector('.multi-picker-selected');
        const valuesBox = root.querySelector('.multi-picker-values');
        const selected = new Map();
        let options = [];
        let loaded = false;

        search.placeholder = placeholder;

        function renderOptions() {
            const query = search.value.trim().toLocaleLowerCase('pt-BR');
            const visible = options.filter(item => item.label.toLocaleLowerCase('pt-BR').includes(query));
            optionsBox.replaceChildren();
            if (!visible.length) {
                const empty = document.createElement('div');
                empty.className = 'multi-picker-empty';
                empty.textContent = loaded ? 'Nenhum item encontrado.' : 'Carregando opções...';
                optionsBox.appendChild(empty);
                return;
            }

            visible.forEach(item => {
                const label = document.createElement('label');
                label.className = 'multi-picker-option';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = selected.has(String(item.id));
                checkbox.addEventListener('change', () => {
                    const key = String(item.id);
                    if (checkbox.checked) selected.set(key, item.label);
                    else selected.delete(key);
                    renderSelected();
                    renderOptions();
                    search.focus();
                });
                const text = document.createElement('span');
                text.textContent = item.label;
                label.append(checkbox, text);
                optionsBox.appendChild(label);
            });
        }

        function renderSelected() {
            selectedBox.replaceChildren();
            valuesBox.replaceChildren();
            selected.forEach((labelText, id) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = inputName;
                hidden.value = id;
                valuesBox.appendChild(hidden);

                const chip = document.createElement('span');
                chip.className = 'multi-picker-chip';
                const text = document.createElement('span');
                text.textContent = labelText;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', 'Remover ' + labelText);
                remove.innerHTML = '&times;';
                remove.addEventListener('click', () => {
                    selected.delete(id);
                    renderSelected();
                    renderOptions();
                });
                chip.append(text, remove);
                selectedBox.appendChild(chip);
            });
            if (!selected.size && !optional) root.dataset.required = 'true';
            else delete root.dataset.required;
        }

        search.addEventListener('focus', () => {
            menu.hidden = false;
            search.setAttribute('aria-expanded', 'true');
            renderOptions();
        });
        search.addEventListener('input', () => {
            menu.hidden = false;
            search.setAttribute('aria-expanded', 'true');
            renderOptions();
        });
        search.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                menu.hidden = true;
                search.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('click', event => {
            if (!root.contains(event.target)) {
                menu.hidden = true;
                search.setAttribute('aria-expanded', 'false');
            }
        });

        return {
            setOptions(items) {
                options = items.map(item => ({ id: item.Codigo, label: item.Nome }));
                loaded = true;
                renderOptions();
            },
            getValues() {
                return [...selected.keys()];
            },
            focus() {
                search.focus();
            }
        };
    }

    const productPicker = picker(document.querySelector('[data-picker="produto"]'), 'produto[]', 'Buscar produtos...', false);
    const supplierPicker = picker(document.querySelector('[data-picker="fornecedor"]'), 'fornecedores[]', 'Buscar fornecedores...', true);

    function localDate(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }

    function money(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
    }

    function showError(message) {
        const error = document.getElementById('erroFiltros');
        error.textContent = message;
        error.classList.remove('d-none');
    }

    function clearError() {
        const error = document.getElementById('erroFiltros');
        error.textContent = '';
        error.classList.add('d-none');
    }

    function showResultsState(message) {
        document.getElementById('containerTabela').hidden = true;
        const state = document.getElementById('estadoResultados');
        state.textContent = message;
        state.classList.remove('d-none');
        document.getElementById('cardsResumo').hidden = true;
        document.getElementById('btnExportar').disabled = true;
    }

    function showChartState(message) {
        if (chart) {
            chart.destroy();
            chart = null;
        }
        document.getElementById('containerGrafico').hidden = true;
        const state = document.getElementById('estadoGrafico');
        state.textContent = message;
        state.classList.remove('d-none');
    }

    function loadFilterOptions() {
        $.getJSON('/api/get_produtos_comparacao.php')
            .done(response => {
                if (!response.success) {
                    showError(response.message || 'Não foi possível carregar os produtos.');
                    return;
                }
                productPicker.setOptions(response.data || []);
                if (!response.data?.length) showError('Não há produtos ativos disponíveis para comparação.');
            })
            .fail(() => showError('Erro ao carregar a lista de produtos.'));

        $.getJSON('/api/get_fornecedores_comparacao.php')
            .done(response => {
                if (!response.success) {
                    showError(response.message || 'Não foi possível carregar os fornecedores.');
                    return;
                }
                supplierPicker.setOptions(response.data || []);
            })
            .fail(() => showError('Erro ao carregar a lista de fornecedores.'));
    }

    const today = new Date();
    const startDate = new Date();
    startDate.setMonth(startDate.getMonth() - 3);
    document.getElementById('dataFim').value = localDate(today);
    document.getElementById('dataInicio').value = localDate(startDate);
    loadFilterOptions();

    form.addEventListener('submit', event => {
        event.preventDefault();
        generateComparison();
    });
    document.getElementById('btnExportar').addEventListener('click', exportCsv);

    function generateComparison() {
        clearError();
        const products = productPicker.getValues();
        const dateStart = document.getElementById('dataInicio').value;
        const dateEnd = document.getElementById('dataFim').value;

        if (!products.length) {
            showError('Selecione pelo menos um produto.');
            productPicker.focus();
            return;
        }
        if (!dateStart || !dateEnd || dateEnd < dateStart) {
            showError('Informe um período válido. A data final deve ser igual ou posterior à inicial.');
            return;
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Consultando';
        document.getElementById('estadoResultados').textContent = 'Buscando notas e calculando os preços...';
        document.getElementById('estadoResultados').classList.remove('d-none');
        document.getElementById('containerTabela').hidden = true;
        document.getElementById('estadoGrafico').textContent = 'Montando o comparativo...';
        document.getElementById('estadoGrafico').classList.remove('d-none');
        document.getElementById('containerGrafico').hidden = true;

        $.getJSON('/api/get_comparacao_precos.php', $(form).serialize())
            .done(response => {
                if (!response.success) {
                    showError(response.message || 'Não foi possível gerar a comparação.');
                    showResultsState('Não foi possível carregar os resultados.');
                    showChartState('Não foi possível carregar o gráfico.');
                    return;
                }

                currentRows = response.data || [];
                if (!currentRows.length) {
                    showResultsState('Nenhuma nota de compra ativa encontrada para os produtos, fornecedores e período selecionados.');
                    showChartState('Não há dados para montar o gráfico com estes filtros.');
                    document.getElementById('infoComparacao').textContent = 'Nenhum registro encontrado para o período selecionado.';
                    return;
                }

                renderTable(currentRows);
                renderSummary(currentRows, response);
                renderChart(currentRows);
                document.getElementById('estadoResultados').classList.add('d-none');
                document.getElementById('containerTabela').hidden = false;
                document.getElementById('btnExportar').disabled = false;
            })
            .fail(xhr => {
                showError(xhr.responseJSON?.message || 'Falha ao consultar os preços. Verifique os filtros e tente novamente.');
                showResultsState('Não foi possível carregar os resultados.');
                showChartState('Não foi possível carregar o gráfico.');
            })
            .always(() => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-chart-line me-1"></i>Comparar preços';
            });
    }

    function renderSummary(rows, response) {
        const products = new Set(rows.map(row => String(row.produto_codigo)));
        const suppliers = new Set(rows.map(row => String(row.fornecedor_codigo)));
        const lowest = rows.reduce((best, row) =>
            !best || Number(row.preco_medio) < Number(best.preco_medio) ? row : best, null);

        document.getElementById('resumoProdutos').textContent = products.size;
        document.getElementById('resumoFornecedores').textContent = suppliers.size;
        document.getElementById('resumoMenorPreco').textContent = lowest ? money(lowest.preco_medio) : '—';
        document.getElementById('resumoMenorPrecoDetalhe').textContent = lowest ? lowest.produto_nome + ' · ' + lowest.fornecedor_nome : '';
        document.getElementById('cardsResumo').hidden = false;

        const period = response.periodo || {};
        const start = (period.inicio || '').split('-').reverse().join('/');
        const end = (period.fim || '').split('-').reverse().join('/');
        document.getElementById('infoComparacao').textContent =
            products.size + ' produto(s) · ' + suppliers.size + ' fornecedor(es) · ' + start + ' a ' + end;
    }

    function renderTable(rows) {
        const lowestByProduct = {};
        rows.forEach(row => {
            const id = String(row.produto_codigo);
            if (lowestByProduct[id] === undefined || Number(row.preco_medio) < lowestByProduct[id]) {
                lowestByProduct[id] = Number(row.preco_medio);
            }
        });

        if (table) {
            table.destroy();
            table = null;
        }
        $('#tabelaResultados').empty();

        table = $('#tabelaResultados').DataTable({
            data: rows,
            columns: [
                {
                    title: 'Produto',
                    data: 'produto_nome',
                    render: (value, type, row) => type !== 'display'
                        ? value
                        : '<div class="fw-semibold">' + escapeHtml(value) + '</div><small class="text-muted">' + escapeHtml(row.unidade_medida || '') + '</small>'
                },
                {
                    title: 'Fornecedor',
                    data: 'fornecedor_nome',
                    render: (value, type, row) => type !== 'display'
                        ? value
                        : '<div class="fw-semibold">' + escapeHtml(value) + '</div><small class="text-muted">Cód. ' + escapeHtml(row.fornecedor_codigo) + '</small>'
                },
                { title: 'Preço médio', data: 'preco_medio', className: 'text-end fw-semibold', render: value => money(value) },
                { title: 'Faixa de preço', data: null, className: 'text-end', render: row => money(row.preco_minimo) + ' – ' + money(row.preco_maximo) },
                { title: 'Notas', data: 'qtd_notas', className: 'text-center' },
                {
                    title: 'Quantidade',
                    data: 'quantidade_total',
                    className: 'text-end',
                    render: (value, type, row) => Number(value || 0).toLocaleString('pt-BR', { maximumFractionDigits: 3 }) +
                        (row.unidade_medida ? ' ' + escapeHtml(row.unidade_medida) : '')
                },
                {
                    title: '',
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (row, type) => type !== 'display' ? '' :
                        (Number(row.preco_medio) === lowestByProduct[String(row.produto_codigo)]
                            ? '<span class="badge text-bg-success"><i class="fas fa-check me-1"></i>Menor médio</span>'
                            : '')
                }
            ],
            order: [[0, 'asc'], [2, 'asc']],
            pageLength: 10,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json' }
        });
    }

    function renderChart(rows) {
        if (chart) chart.destroy();

        const plotRows = rows
            .map(row => ({ ...row, price: Number(row.preco_medio) }))
            .filter(row => Number.isFinite(row.price) && row.price > 0)
            .sort((a, b) => a.produto_nome.localeCompare(b.produto_nome, 'pt-BR') || a.price - b.price);

        if (!plotRows.length) {
            showChartState('Os registros encontrados não têm preço unitário maior que zero. Confira os preços dos itens nas notas de compra.');
            return;
        }

        const productColor = new Map();
        let nextColor = 0;
        const labels = plotRows.map(row => {
            const id = String(row.produto_codigo);
            if (!productColor.has(id)) {
                productColor.set(id, colors[nextColor % colors.length]);
                nextColor++;
            }
            return row.produto_nome + ' · ' + row.fornecedor_nome;
        });

        document.getElementById('estadoGrafico').classList.add('d-none');
        document.getElementById('containerGrafico').hidden = false;
        document.getElementById('areaGrafico').style.height = Math.max(380, plotRows.length * 34) + 'px';

        chart = new Chart(document.getElementById('graficoComparacao'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Preço médio',
                    data: plotRows.map(row => row.price),
                    backgroundColor: plotRows.map(row => productColor.get(String(row.produto_codigo))),
                    borderRadius: 4,
                    barThickness: 18
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: items => items[0]?.label || '',
                            label: context => 'Preço médio: ' + money(context.raw)
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Preço médio' },
                        ticks: { callback: value => money(value) }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { autoSkip: false }
                    }
                }
            }
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    }

    function exportCsv() {
        if (!currentRows.length) return;
        const rows = [['Produto', 'Unidade', 'Fornecedor', 'Preço médio', 'Menor preço', 'Maior preço', 'Notas', 'Quantidade']];
        currentRows.forEach(row => rows.push([
            row.produto_nome, row.unidade_medida, row.fornecedor_nome, row.preco_medio,
            row.preco_minimo, row.preco_maximo, row.qtd_notas, row.quantidade_total
        ]));
        const csv = '\uFEFF' + rows.map(row =>
            row.map(value => '"' + String(value ?? '').replace(/"/g, '""') + '"').join(';')
        ).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = 'comparacao-precos-' + localDate(new Date()) + '.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }
});
