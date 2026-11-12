/* /public/js/comparacao_precos.js */
$(function () {
    const $produto = $('#produto');
    const $fornecedores = $('#fornecedores');
    const $form = $('#formFiltros');
    const $botaoGerar = $('#btnGerarComparacao');
    let tabela = null;
    let grafico = null;
    let resultadosAtuais = [];

    const cores = ['#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#0dcaf0', '#d63384', '#6c757d', '#20c997'];

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[char]);
    }

    function dinheiro(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
    }

    function dataLocal(date) {
        const ano = date.getFullYear();
        const mes = String(date.getMonth() + 1).padStart(2, '0');
        const dia = String(date.getDate()).padStart(2, '0');
        return ano + '-' + mes + '-' + dia;
    }

    function mostrarErro(mensagem) {
        $('#erroFiltros').text(mensagem).removeClass('d-none');
    }

    function limparErro() {
        $('#erroFiltros').addClass('d-none').text('');
    }

    function mostrarEstadoResultados(mensagem) {
        $('#containerTabela').prop('hidden', true);
        $('#estadoResultados').html('<i class="fas fa-circle-info fs-3 d-block mb-2"></i>').append(document.createTextNode(mensagem)).removeClass('d-none');
        $('#cardsResumo').prop('hidden', true);
        $('#btnExportar').prop('disabled', true);
    }

    function carregarFiltros() {
        $.getJSON('/api/get_produtos_comparacao.php')
            .done(response => {
                if (!response.success) {
                    mostrarErro(response.message || 'Não foi possível carregar os produtos.');
                    return;
                }
                $produto.empty();
                (response.data || []).forEach(item => {
                    $produto.append(new Option(item.Nome, item.Codigo));
                });
                $produto.select2({
                    placeholder: 'Selecione um ou mais produtos',
                    width: '100%',
                    closeOnSelect: false
                });
                if ((response.data || []).length === 0) {
                    mostrarErro('Não há produtos ativos disponíveis para comparação.');
                }
            })
            .fail(() => mostrarErro('Erro ao carregar a lista de produtos.'));

        $.getJSON('/api/get_fornecedores_comparacao.php')
            .done(response => {
                if (!response.success) {
                    mostrarErro(response.message || 'Não foi possível carregar os fornecedores.');
                    return;
                }
                $fornecedores.empty();
                (response.data || []).forEach(item => {
                    $fornecedores.append(new Option(item.Nome, item.Codigo));
                });
                $fornecedores.select2({
                    placeholder: 'Todos os fornecedores',
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });
            })
            .fail(() => mostrarErro('Erro ao carregar a lista de fornecedores.'));
    }

    const hoje = new Date();
    const inicio = new Date();
    inicio.setMonth(inicio.getMonth() - 3);
    $('#dataFim').val(dataLocal(hoje));
    $('#dataInicio').val(dataLocal(inicio));

    carregarFiltros();

    $form.on('submit', function (event) {
        event.preventDefault();
        gerarComparacao();
    });

    $('#btnExportar').on('click', exportarCsv);

    function gerarComparacao() {
        limparErro();
        const produtos = $produto.val() || [];
        const dataInicio = $('#dataInicio').val();
        const dataFim = $('#dataFim').val();

        if (produtos.length === 0) {
            mostrarErro('Selecione pelo menos um produto.');
            return;
        }
        if (!dataInicio || !dataFim || dataFim < dataInicio) {
            mostrarErro('Informe um período válido. A data final deve ser igual ou posterior à inicial.');
            return;
        }

        $botaoGerar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Consultando');
        $('#estadoResultados').html('<span class="spinner-border spinner-border-sm me-2"></span>Buscando notas e calculando os preços...').removeClass('d-none');
        $('#containerTabela').prop('hidden', true);
        $('#estadoGrafico').html('<span class="spinner-border spinner-border-sm me-2"></span>Montando o comparativo...').removeClass('d-none');
        $('#containerGrafico').prop('hidden', true);

        $.getJSON('/api/get_comparacao_precos.php', $form.serialize())
            .done(response => {
                if (!response.success) {
                    throw new Error(response.message || 'Não foi possível gerar a comparação.');
                }
                resultadosAtuais = response.data || [];
                if (resultadosAtuais.length === 0) {
                    mostrarEstadoResultados('Nenhuma nota de compra ativa encontrada para os produtos, fornecedores e período selecionados.');
                    mostrarEstadoGrafico('Não há dados para montar o gráfico com estes filtros.');
                    $('#infoComparacao').text('Nenhum registro encontrado para o período selecionado.');
                    return;
                }
                atualizarTabela(resultadosAtuais);
                atualizarResumo(resultadosAtuais, response);
                atualizarGrafico(resultadosAtuais);
                $('#estadoResultados').addClass('d-none');
                $('#containerTabela').prop('hidden', false);
                $('#btnExportar').prop('disabled', false);
            })
            .fail(xhr => {
                const mensagem = xhr.responseJSON?.message || 'Falha ao consultar os preços. Verifique os filtros e tente novamente.';
                mostrarErro(mensagem);
                mostrarEstadoResultados('Não foi possível carregar os resultados.');
                mostrarEstadoGrafico('Não foi possível carregar o gráfico.');
            })
            .always(() => {
                $botaoGerar.prop('disabled', false).html('<i class="fas fa-chart-line me-1"></i>Comparar');
            });
    }

    function atualizarResumo(data, response) {
        const produtos = new Map();
        const fornecedores = new Set();
        let menor = null;

        data.forEach(row => {
            produtos.set(String(row.produto_codigo), row.produto_nome);
            fornecedores.add(String(row.fornecedor_codigo));
            if (!menor || Number(row.preco_medio) < Number(menor.preco_medio)) {
                menor = row;
            }
        });

        $('#resumoProdutos').text(produtos.size);
        $('#resumoFornecedores').text(fornecedores.size);
        $('#resumoMenorPreco').text(menor ? dinheiro(menor.preco_medio) : '—');
        $('#resumoMenorPrecoDetalhe').text(menor ? menor.produto_nome + ' · ' + menor.fornecedor_nome : '');
        $('#cardsResumo').prop('hidden', false);

        const periodo = response.periodo || {};
        const inicio = (periodo.inicio || '').split('-').reverse().join('/');
        const fim = (periodo.fim || '').split('-').reverse().join('/');
        $('#infoComparacao').text(produtos.size + ' produto(s) · ' + fornecedores.size + ' fornecedor(es) · ' + inicio + ' a ' + fim);
    }

    function atualizarTabela(data) {
        const menorPorProduto = {};
        data.forEach(row => {
            const key = String(row.produto_codigo);
            const preco = Number(row.preco_medio);
            if (menorPorProduto[key] === undefined || preco < menorPorProduto[key]) {
                menorPorProduto[key] = preco;
            }
        });

        if (tabela) {
            tabela.destroy();
            tabela = null;
        }
        $('#tabelaResultados').empty();

        tabela = $('#tabelaResultados').DataTable({
            data,
            columns: [
                {
                    title: 'Produto',
                    data: 'produto_nome',
                    render: (value, type, row) => {
                        if (type !== 'display') return value;
                        return '<div class="fw-semibold">' + escapeHtml(value) + '</div><small class="text-muted">' +
                            escapeHtml(row.unidade_medida || '') + '</small>';
                    }
                },
                {
                    title: 'Fornecedor',
                    data: 'fornecedor_nome',
                    render: (value, type, row) => type === 'display'
                        ? '<div class="fw-semibold">' + escapeHtml(value) + '</div><small class="text-muted">Cód. ' + escapeHtml(row.fornecedor_codigo) + '</small>'
                        : value
                },
                {
                    title: 'Preço médio',
                    data: 'preco_medio',
                    className: 'text-end fw-semibold',
                    render: value => dinheiro(value)
                },
                {
                    title: 'Faixa de preço',
                    data: null,
                    className: 'text-end',
                    render: row => dinheiro(row.preco_minimo) + ' – ' + dinheiro(row.preco_maximo)
                },
                {
                    title: 'Notas',
                    data: 'qtd_notas',
                    className: 'text-center'
                },
                {
                    title: 'Quantidade comprada',
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
                    render: (row, type) => {
                        if (type !== 'display') return '';
                        const key = String(row.produto_codigo);
                        return Number(row.preco_medio) === menorPorProduto[key]
                            ? '<span class="badge text-bg-success"><i class="fas fa-check me-1"></i>Menor médio</span>'
                            : '';
                    }
                }
            ],
            order: [[0, 'asc'], [2, 'asc']],
            pageLength: 10,
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json' }
        });
    }

    function atualizarGrafico(data) {
        const fornecedores = [...new Map(data.map(row => [String(row.fornecedor_codigo), row.fornecedor_nome])).entries()];
        const produtos = [...new Map(data.map(row => [String(row.produto_codigo), row.produto_nome])).entries()];
        const valoresPorPar = new Map(data.map(row => [String(row.produto_codigo) + ':' + String(row.fornecedor_codigo), Number(row.preco_medio)]));

        if (grafico) {
            grafico.destroy();
        }

        $('#estadoGrafico').addClass('d-none');
        $('#containerGrafico').prop('hidden', false);

        grafico = new Chart(document.getElementById('graficoComparacao'), {
            type: 'bar',
            data: {
                labels: fornecedores.map(item => item[1]),
                datasets: produtos.map(([codigo, nome], index) => ({
                    label: nome,
                    data: fornecedores.map(([fornecedorCodigo]) => valoresPorPar.get(codigo + ':' + fornecedorCodigo) ?? null),
                    backgroundColor: cores[index % cores.length],
                    borderRadius: 4
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: context => context.dataset.label + ': ' + dinheiro(context.raw) } }
                },
                scales: {
                    x: { title: { display: true, text: 'Fornecedor' } },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Preço médio' },
                        ticks: { callback: value => dinheiro(value) }
                    }
                }
            }
        });
    }

    function mostrarEstadoGrafico(mensagem) {
        if (grafico) {
            grafico.destroy();
            grafico = null;
        }
        $('#containerGrafico').prop('hidden', true);
        $('#estadoGrafico').text(mensagem).removeClass('d-none');
    }

    function exportarCsv() {
        if (!resultadosAtuais.length) return;
        const linhas = [
            ['Produto', 'Unidade', 'Fornecedor', 'Preço médio', 'Menor preço', 'Maior preço', 'Notas', 'Quantidade']
        ];
        resultadosAtuais.forEach(row => linhas.push([
            row.produto_nome,
            row.unidade_medida,
            row.fornecedor_nome,
            row.preco_medio,
            row.preco_minimo,
            row.preco_maximo,
            row.qtd_notas,
            row.quantidade_total
        ]));
        const csv = '\uFEFF' + linhas.map(linha => linha.map(value => '"' + String(value ?? '').replace(/"/g, '""') + '"').join(';')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = 'comparacao-precos-' + dataLocal(new Date()) + '.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }
});
