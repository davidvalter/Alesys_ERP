/**
 * Relatório de Conciliação Bancária - Alesys
 * Arquivo: relatorio_conciliacao_bancaria.js
 * Endpoint: conciliacao_bancaria.php
 */
(function () {
    'use strict';

    // Elementos do DOM
    const btnExecutar = document.querySelector('#btn-executar-conciliacao');
    const inputMesAno = document.querySelector('#mes_ano');
    const loadingOverlay = document.querySelector('#loading-overlay');
    const lancamentosTbody = document.querySelector('#lancamentos-tbody');
    const dreTbody = document.querySelector('#dre-tbody');
    const kpiTotalDebitos = document.querySelector('#kpi-total-debitos');
    const kpiPctNotas = document.querySelector('#kpi-pct-notas');
    const kpiOrfaos = document.querySelector('#kpi-orfaos');
    const kpiLucro = document.querySelector('#kpi-lucro');
    const dreChartCanvas = document.querySelector('#dreChart');

    let dreChart = null;

    const BACKEND_URL = 'backend/relatorio_conciliacao_bancaria.php';

    function showLoading() {
        if (loadingOverlay) loadingOverlay.classList.add('show');
    }

    function hideLoading() {
        if (loadingOverlay) loadingOverlay.classList.remove('show');
    }

    function formatarMoeda(valor) {
        const numero = parseFloat(valor);
        if (isNaN(numero)) return 'R$ 0,00';
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(numero);
    }

    function formatarPercentual(valor) {
        const numero = parseFloat(valor);
        if (isNaN(numero)) return '0,00%';
        return new Intl.NumberFormat('pt-BR', {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(numero / 100);
    }

    function formatarData(dataString) {
        if (!dataString) return '-';
        const data = new Date(dataString);
        if (isNaN(data.getTime())) return dataString;
        return new Intl.DateTimeFormat('pt-BR').format(data);
    }

    function obterBadgeStatus(status) {
        const normalizado = String(status || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (normalizado === 'conciliado') {
            return '<span class="badge badge-success">Conciliado</span>';
        }
        if (normalizado === 'orfao' || normalizado === 'orfa') {
            return '<span class="badge badge-danger">Órfão</span>';
        }
        return '<span class="badge badge-warning">' + (status || 'Pendente') + '</span>';
    }

    function ajaxPost(acao, mesAno) {
        showLoading();
        const formData = new FormData();
        formData.append('acao', acao);
        formData.append('mes_ano', mesAno);

        return fetch(BACKEND_URL, {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Erro na requisição: ' + response.status);
                return response.json();
            })
            .finally(function () {
                hideLoading();
            });
    }

    function atualizarKPIs(kpis) {
        if (!kpis) return;
        if (kpiTotalDebitos) kpiTotalDebitos.textContent = formatarMoeda(kpis.total_debitos);
        if (kpiPctNotas) kpiPctNotas.textContent = formatarPercentual(kpis.pct_notas);
        if (kpiOrfaos) kpiOrfaos.textContent = parseInt(kpis.orfaos || 0, 10).toLocaleString('pt-BR');
        if (kpiLucro) kpiLucro.textContent = formatarMoeda(kpis.lucro);
    }

    function renderizarLancamentos(lancamentos) {
        if (!lancamentosTbody) return;
        lancamentosTbody.innerHTML = '';

        if (!Array.isArray(lancamentos) || lancamentos.length === 0) {
            lancamentosTbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum lançamento encontrado.</td></tr>';
            return;
        }

        lancamentos.forEach(function (lancamento) {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + formatarData(lancamento.data) + '</td>' +
                '<td>' + (lancamento.descricao || '-') + '</td>' +
                '<td>' + (lancamento.tipo || '-') + '</td>' +
                '<td>' + formatarMoeda(lancamento.valor) + '</td>' +
                '<td>' + (lancamento.nf || '-') + '</td>' +
                '<td>' + (lancamento.cliente || '-') + '</td>' +
                '<td>' + obterBadgeStatus(lancamento.status) + '</td>';
            lancamentosTbody.appendChild(tr);
        });
    }

    function renderizarDRE(dre) {
        if (!dreTbody) return;
        dreTbody.innerHTML = '';

        if (!dre || !Array.isArray(dre.itens) || dre.itens.length === 0) {
            dreTbody.innerHTML = '<tr><td colspan="2" class="text-center">Nenhum dado de DRE disponível.</td></tr>';
            return;
        }

        dre.itens.forEach(function (item) {
            const tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + (item.categoria || '-') + '</td>' +
                '<td class="text-right">' + formatarMoeda(item.valor) + '</td>';
            dreTbody.appendChild(tr);
        });
    }

    function atualizarGraficoDRE(dre) {
        if (!dreChartCanvas) return;

        const ctx = dreChartCanvas.getContext('2d');
        const labels = [];
        const valores = [];

        if (dre && Array.isArray(dre.itens)) {
            dre.itens.forEach(function (item) {
                labels.push(item.categoria || '-');
                valores.push(parseFloat(item.valor) || 0);
            });
        }

        if (dreChart) {
            dreChart.data.labels = labels;
            dreChart.data.datasets[0].data = valores;
            dreChart.update();
        } else {
            dreChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Valores DRE',
                        data: valores,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                callback: function (value) {
                                    return formatarMoeda(value);
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (context) {
                                return formatarMoeda(context.raw);
                            }
                        }
                    }
                }
            });
        }
    }

    function atualizarInterface(dados) {
        if (dados.kpis) atualizarKPIs(dados.kpis);
        if (dados.lancamentos) renderizarLancamentos(dados.lancamentos);
        if (dados.dre) {
            renderizarDRE(dados.dre);
            atualizarGraficoDRE(dados.dre);
        }
    }

    function executarAcao(acao) {
        const mesAno = inputMesAno ? inputMesAno.value : '';
        if (!mesAno) {
            alert('Por favor, selecione o mês/ano.');
            return;
        }

        ajaxPost(acao, mesAno)
            .then(function (dados) {
                atualizarInterface(dados);
                if (acao === 'conciliar') {
                    alert('Conciliação executada com sucesso.');
                }
            })
            .catch(function (erro) {
                console.error(erro);
                alert('Erro ao processar a solicitação.');
            });
    }

    function carregarDadosIniciais() {
        executarAcao('listar');
    }

    function init() {
        if (btnExecutar) {
            btnExecutar.addEventListener('click', function (e) {
                e.preventDefault();
                executarAcao('conciliar');
            });
        }

        if (inputMesAno) {
            inputMesAno.addEventListener('change', function () {
                executarAcao('listar');
            });
        }

        carregarDadosIniciais();
    }

    document.addEventListener('DOMContentLoaded', init);

    window.ConciliacaoApp = {
        atualizarKPIs: atualizarKPIs,
        renderizarLancamentos: renderizarLancamentos,
        renderizarDRE: renderizarDRE,
        atualizarGraficoDRE: atualizarGraficoDRE,
        executarAcao: executarAcao
    };
})();