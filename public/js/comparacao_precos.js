/* /public/js/comparacao_precos.js */

$(document).ready(function () {

    const selectProduto = $('#produto');
    const selectFornecedores = $('#fornecedores');
    const tabelaElement = $('#tabelaResultados');

    let dataTable = null;

    // =========================
    // LOADER GLOBAL SIMPLES
    // =========================
    function toggleLoading(show = true) {
        if (show) {
            $('body').append('<div id="globalLoader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;font-weight:bold;">Carregando...</div>');
        } else {
            $('#globalLoader').remove();
        }
    }

    // =========================
    // CARREGAR PRODUTOS
    // =========================
    $.get('../api/get_produtos_comparacao.php')
        .done(function (response) {
            if (response.success) {
                selectProduto.empty().append('<option value="">Selecione um produto</option>');

                response.data.forEach(produto => {
                    selectProduto.append($('<option>', {
                        value: produto.Codigo,
                        text: produto.Nome,
                        'data-unidade': produto.UnidadeMedida
                    }));
                });
            } else {
                alert(response.message);
            }
        })
        .fail(() => alert('Erro ao carregar produtos'));

    // =========================
    // CARREGAR FORNECEDORES
    // =========================
    $.get('../api/get_fornecedores_comparacao.php')
        .done(function (response) {
            if (response.success) {
                selectFornecedores.empty();

                response.data.forEach(fornecedor => {
                    selectFornecedores.append($('<option>', {
                        value: fornecedor.Codigo,
                        text: fornecedor.Nome
                    }));
                });

                selectFornecedores.select2({
                    placeholder: "Selecione fornecedores (opcional)",
                    allowClear: true,
                    width: '100%'
                });

            } else {
                alert(response.message);
            }
        })
        .fail(() => alert('Erro ao carregar fornecedores'));

    // =========================
    // DATAS PADRÃO
    // =========================
    const dataFim = new Date();
    const dataInicio = new Date();
    dataInicio.setMonth(dataInicio.getMonth() - 3);

    $('#dataFim').val(dataFim.toISOString().split('T')[0]);
    $('#dataInicio').val(dataInicio.toISOString().split('T')[0]);

    // =========================
    // SUBMIT
    // =========================
    $('#formFiltros').submit(function (e) {
        e.preventDefault();
        gerarComparacao();
    });

    // =========================
    // EXPORTAR
    // =========================
    $('#btnExportar').click(function () {
        exportarParaExcel();
    });

    // =========================
    // GERAR COMPARAÇÃO
    // =========================
    function gerarComparacao() {
        const formData = $('#formFiltros').serialize();

        toggleLoading(true);

        $.get('../api/get_comparacao_precos.php?' + formData)
            .done(function (response) {
                if (response.success && response.data.length > 0) {
                    atualizarTabela(response.data);
                    atualizarGrafico(response.data);
                    atualizarInfo(response);
                } else {
                    alert('Nenhum dado encontrado para os filtros selecionados.');
                }
            })
            .fail(() => {
                alert('Erro ao processar requisição');
            })
            .always(() => {
                toggleLoading(false);
            });
    }

    // =========================
    // INFO RESUMO
    // =========================
    function atualizarInfo(response) {
        const produtoNome = $('#produto option:selected').text();

        $('#infoComparacao').html(`
            <strong>Produto:</strong> ${produtoNome} <br>
            <strong>Período:</strong> ${$('#dataInicio').val()} até ${$('#dataFim').val()} <br>
            <strong>Fornecedores analisados:</strong> ${response.data.length}
        `);
    }

    // =========================
    // TABELA
    // =========================
    function atualizarTabela(data) {

        if (dataTable) {
            dataTable.clear().rows.add(data).draw();
            return;
        }

        dataTable = tabelaElement.DataTable({
            data: data,
            columns: [
                {
                    title: 'Fornecedor',
                    data: 'fornecedor_nome',
                    render: (data, type, row) =>
                        `${data} <small class="text-muted">(Cód: ${row.fornecedor_codigo})</small>`
                },
                {
                    title: 'Preço Médio',
                    data: 'preco_medio',
                    render: data => formatMoney(data)
                },
                {
                    title: 'Menor Preço',
                    data: 'preco_minimo',
                    render: data => formatMoney(data)
                },
                {
                    title: 'Maior Preço',
                    data: 'preco_maximo',
                    render: data => formatMoney(data)
                },
                {
                    title: 'Qtd. Notas',
                    data: 'qtd_notas'
                },
                {
                    title: 'Qtd. Total',
                    data: 'quantidade_total',
                    render: data => {
                        const unidade = $('#produto').find(':selected').data('unidade') || '';
                        return `${parseFloat(data).toFixed(3)} ${unidade}`;
                    }
                }
            ],
            order: [[1, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            }
        });
    }

    // =========================
    // GRÁFICO
    // =========================
    let chartComparacao = null;

    function atualizarGrafico(data) {
        const ctx = document.getElementById('graficoComparacao').getContext('2d');

        if (chartComparacao) {
            chartComparacao.destroy();
        }

        chartComparacao = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(i => i.fornecedor_nome),
                datasets: [
                    {
                        label: 'Preço Médio',
                        data: data.map(i => parseFloat(i.preco_medio)),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    },
                    {
                        label: 'Menor Preço',
                        data: data.map(i => parseFloat(i.preco_minimo)),
                        backgroundColor: 'rgba(75, 192, 192, 0.7)'
                    },
                    {
                        label: 'Maior Preço',
                        data: data.map(i => parseFloat(i.preco_maximo)),
                        backgroundColor: 'rgba(255, 99, 132, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `Comparação - ${$('#produto option:selected').text()}`
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => formatMoney(ctx.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: value => formatMoney(value)
                        }
                    }
                }
            }
        });
    }

    // =========================
    // UTIL
    // =========================
    function formatMoney(value) {
        return 'R$ ' + parseFloat(value || 0)
            .toFixed(2)
            .replace('.', ',');
    }

    // =========================
    // EXPORTAÇÃO (placeholder)
    // =========================
    function exportarParaExcel() {
        alert('Exportação será implementada (CSV/XLSX)');
    }

});