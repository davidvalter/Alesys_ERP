/* public/js/relatorio_registro_maquininhas.js */
/* JavaScript completo — ajustado ao schema real das tabelas FechamentosCaixa + FechamentosCaixaItens */
/* Filtros disponíveis: dataInicial, dataFinal, operadora, statusCaixa, meioPagamento, responsavelId */

// ============================================================
// ESTADO GLOBAL
// ============================================================

const state = {
    dados: null,
    dadosFiltrados: null,
    paginaAtual: 1,
    itensPorPagina: 25,
    ordenacao: { coluna: null, direcao: 'asc' },
    filtros: {
        dataInicial: '',
        dataFinal: '',
        operadora: '',
        statusCaixa: 'fechado',
        meioPagamento: '',
        responsavelId: ''
    }
};

let chartFaturamento = null;
let chartOperadoras = null;
let chartMeios = null;

const cores = ['#0d6efd', '#198754', '#fd7e14', '#dc3545', '#6f42c1', '#20c997', '#ffc107', '#0dcaf0'];

// ============================================================
// HELPERS
// ============================================================

function formatarData(dataStr) {
    if (!dataStr) return '';
    const [y, m, d] = dataStr.split('-');
    return `${d.padStart(2, '0')}/${m}`;
}

function formatarDataCompleta(dataStr) {
    if (!dataStr) return '';
    const [y, m, d] = dataStr.split('-');
    return `${d.padStart(2, '0')}/${m}/${y}`;
}

function formatarMoeda(valor) {
    return Number(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function classificarValor(valor, media) {
    const v = Number(valor) || 0;
    const m = Number(media) || 0;
    if (m === 0) return '';
    if (v > m * 1.5) return 'valor-destaque';
    if (v > m) return 'valor-alerta';
    return '';
}

// ============================================================
// FILTROS
// ============================================================

function lerFiltros() {
    state.filtros.dataInicial = document.getElementById('dataInicial')?.value || '';
    state.filtros.dataFinal = document.getElementById('dataFinal')?.value || '';
    state.filtros.operadora = document.getElementById('operadora')?.value || '';
    state.filtros.statusCaixa = document.getElementById('statusCaixa')?.value || 'fechado';
    state.filtros.meioPagamento = document.getElementById('meioPagamento')?.value || '';
    state.filtros.responsavelId = document.getElementById('responsavel')?.value || '';
}

function limparFiltros() {
    const hoje = new Date();
    const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    document.getElementById('dataInicial').value = inicioMes.toISOString().split('T')[0];
    document.getElementById('dataFinal').value = hoje.toISOString().split('T')[0];
    document.getElementById('operadora').value = '';
    document.getElementById('statusCaixa').value = 'fechado';
    document.getElementById('meioPagamento').value = '';
    document.getElementById('responsavel').value = '';
    lerFiltros();
    carregarRelatorio(true);
}

// ============================================================
// CARREGAMENTO PRINCIPAL
// ============================================================

function carregarRelatorio(filterChanged = false) {
    
    // 🔥 GARANTE que os filtros são lidos sempre antes de montar a URL
    lerFiltros();

    if (filterChanged) {
        state.paginaAtual = 1;
        state.ordenacao = { coluna: null, direcao: 'asc' };
    }

    const params = new URLSearchParams();
    Object.entries(state.filtros).forEach(([key, value]) => {
        if (value) params.append(key, value);
    });

    const url = `/api/get_registro_maquininhas_matriz.php?${params.toString()}`;

    const loading = document.getElementById('loadingOverlay');
    if (loading) loading.style.display = 'flex';

    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error(`Erro HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.error || data.message || 'Erro ao carregar relatório');
            state.dados = data;
            aplicarFiltrosLocal();
            ordenarDados();
            renderizarTabela();
            renderizarDashboard();
            renderizarGraficos();
            renderizarPaginacao();
        })
        .catch(err => {
            console.error('Erro:', err);
            alert('Erro ao carregar relatório: ' + err.message);
        })
        .finally(() => {
            if (loading) loading.style.display = 'none';
        });
}

// ============================================================
// FILTRAGEM LOCAL
// ============================================================

function aplicarFiltrosLocal() {
    state.dadosFiltrados = state.dados?.linhas ? [...state.dados.linhas] : [];
}

// ============================================================
// ORDENAÇÃO
// ============================================================

function ordenarDados() {
    const { coluna, direcao } = state.ordenacao;
    if (!coluna || !state.dadosFiltrados?.length) return;

    const dir = direcao === 'asc' ? 1 : -1;

    state.dadosFiltrados.sort((a, b) => {
        let valA, valB;

        if (coluna === 'operadora' || coluna === 'meio') {
            valA = (a[coluna] || '').toLowerCase();
            valB = (b[coluna] || '').toLowerCase();
            return valA.localeCompare(valB, 'pt-BR') * dir;
        }

        // coluna de data — encontrar índice
        const idx = state.dados.datas.findIndex(d => d === coluna);
        if (idx === -1) return 0;
        valA = Number(a.valores[idx]) || 0;
        valB = Number(b.valores[idx]) || 0;
        return (valA - valB) * dir;
    });
}

function sortTable(coluna) {
    if (state.ordenacao.coluna === coluna) {
        state.ordenacao.direcao = state.ordenacao.direcao === 'asc' ? 'desc' : 'asc';
    } else {
        state.ordenacao.coluna = coluna;
        state.ordenacao.direcao = 'asc';
    }
    ordenarDados();
    renderizarTabela();
    renderizarPaginacao();
}

// ============================================================
// RENDERIZAR TABELA
// ============================================================

function renderizarTabela() {
    const tabela = document.getElementById('tabelaRelatorio');
    const thead = tabela.querySelector('thead');
    const tbody = tabela.querySelector('tbody');
    const tfoot = tabela.querySelector('tfoot');

    if (!state.dados || !state.dadosFiltrados?.length) {
        const colspan = 2 + (state.dados?.datas?.length || 1);
        thead.innerHTML = '';
        tbody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-4 text-muted">Nenhum registro encontrado.</td></tr>`;
        tfoot.innerHTML = '';
        document.getElementById('contadorRegistros').textContent = 'Nenhum registro carregado';
        return;
    }

    const datas = state.dados.datas;
    const numDatas = datas.length;

    // Calcular médias por coluna de data
    const medias = new Array(numDatas).fill(0);
    state.dadosFiltrados.forEach(linha => {
        linha.valores.forEach((v, i) => { medias[i] += Number(v) || 0; });
    });
    medias.forEach((_, i) => { medias[i] /= state.dadosFiltrados.length; });

    // CABEÇALHO
    let theadHtml = '<tr>';
    ['operadora', 'meio'].forEach(col => {
        const ordenando = state.ordenacao.coluna === col;
        const classe = ordenando ? `sort-${state.ordenacao.direcao}` : '';
        const label = col === 'operadora' ? 'Operadora' : 'Meio Pagamento';
        theadHtml += `<th data-col="${col}" class="sortable ${classe}">${label}</th>`;
    });
    datas.forEach(data => {
        const ordenando = state.ordenacao.coluna === data;
        const classe = ordenando ? `sort-${state.ordenacao.direcao}` : '';
        theadHtml += `<th data-col="${data}" class="sortable ${classe}">${formatarData(data)}</th>`;
    });
    theadHtml += '</tr>';
    thead.innerHTML = theadHtml;

    // CORPO (paginado)
    const inicio = (state.paginaAtual - 1) * state.itensPorPagina;
    const fim = inicio + state.itensPorPagina;
    const paginados = state.dadosFiltrados.slice(inicio, fim);

    let tbodyHtml = '';
    paginados.forEach(linha => {
        tbodyHtml += '<tr>';
        tbodyHtml += `<td class="fw-medium">${linha.operadora || '-'}</td>`;
        tbodyHtml += `<td>${linha.meio || '-'}</td>`;
        linha.valores.forEach((v, i) => {
            const valorNum = Number(v) || 0;
            const classe = classificarValor(valorNum, medias[i]);
            tbodyHtml += `<td class="valor ${classe}">${formatarMoeda(valorNum)}</td>`;
        });
        tbodyHtml += '</tr>';
    });
    tbody.innerHTML = tbodyHtml;

    // RODAPÉ
    const totaisOp = {};
    state.dadosFiltrados.forEach(linha => {
        const op = linha.operadora;
        if (!totaisOp[op]) totaisOp[op] = new Array(numDatas).fill(0);
        linha.valores.forEach((v, i) => { totaisOp[op][i] += Number(v) || 0; });
    });

    let tfootHtml = '';
    Object.keys(totaisOp).sort((a, b) => a.localeCompare(b, 'pt-BR')).forEach(op => {
        tfootHtml += '<tr class="linha-total-operadora">';
        tfootHtml += `<td class="fw-semibold">${op}</td>`;
        tfootHtml += '<td class="text-muted small">Total</td>';
        totaisOp[op].forEach(v => { tfootHtml += `<td class="valor fw-semibold">${formatarMoeda(v)}</td>`; });
        tfootHtml += '</tr>';
    });

    tfootHtml += '<tr class="total-geral">';
    tfootHtml += '<td colspan="2" class="fw-bold">Total Geral</td>';
    state.dados.total_geral.forEach(v => { tfootHtml += `<td class="valor fw-bold">${formatarMoeda(Number(v))}</td>`; });
    tfootHtml += '</tr>';
    tfoot.innerHTML = tfootHtml;

    // Contador
    const total = state.dadosFiltrados.length;
    const contador = document.getElementById('contadorRegistros');
    if (total === 0) {
        contador.textContent = 'Nenhum registro encontrado';
    } else {
        contador.textContent = `Mostrando ${inicio + 1} a ${Math.min(fim, total)} de ${total} registros`;
    }
}

// ============================================================
// PAGINAÇÃO
// ============================================================

function renderizarPaginacao() {
    const total = state.dadosFiltrados?.length || 0;
    const totalPaginas = Math.ceil(total / state.itensPorPagina);
    const ul = document.getElementById('paginacao');

    if (totalPaginas <= 1) {
        ul.innerHTML = '';
        return;
    }

    const atual = state.paginaAtual;
    let html = '';

    html += `<li class="page-item ${atual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-pag="${atual - 1}">« Anterior</a></li>`;

    const maxVisiveis = 5;
    let inicio = Math.max(1, atual - 2);
    let fim = Math.min(totalPaginas, inicio + maxVisiveis - 1);
    if (fim - inicio + 1 < maxVisiveis) {
        inicio = Math.max(1, fim - maxVisiveis + 1);
    }

    if (inicio > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-pag="1">1</a></li>`;
        if (inicio > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    }

    for (let i = inicio; i <= fim; i++) {
        html += `<li class="page-item ${i === atual ? 'active' : ''}"><a class="page-link" href="#" data-pag="${i}">${i}</a></li>`;
    }

    if (fim < totalPaginas) {
        if (fim < totalPaginas - 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="#" data-pag="${totalPaginas}">${totalPaginas}</a></li>`;
    }

    html += `<li class="page-item ${atual >= totalPaginas ? 'disabled' : ''}"><a class="page-link" href="#" data-pag="${atual + 1}">Próxima »</a></li>`;

    ul.innerHTML = html;
}

function mudarPagina(pag) {
    const total = state.dadosFiltrados?.length || 0;
    const totalPaginas = Math.ceil(total / state.itensPorPagina);
    const pagina = parseInt(pag);

    if (isNaN(pagina) || pagina < 1 || pagina > totalPaginas) return;

    state.paginaAtual = pagina;
    renderizarTabela();
    renderizarPaginacao();
}

// ============================================================
// DASHBOARD (KPIs)
// ============================================================

function renderizarDashboard() {
    if (!state.dados || !state.dados.total_geral) {
        document.getElementById('kpiFaturamento').textContent = 'R$ 0,00';
        document.getElementById('kpiTicket').textContent = 'R$ 0,00';
        document.getElementById('kpiMelhorDia').textContent = '-';
        document.getElementById('kpiOperadora').textContent = '-';
        return;
    }

    const totalGeral = state.dados.total_geral.reduce((a, b) => a + Number(b), 0);
    const numDias = state.dados.datas.length;

    document.getElementById('kpiFaturamento').textContent = formatarMoeda(totalGeral);
    document.getElementById('kpiTicket').textContent = formatarMoeda(numDias > 0 ? totalGeral / numDias : 0);

    const valoresDia = state.dados.total_geral.map(Number);
    const maxValor = Math.max(...valoresDia);
    const maxIdx = valoresDia.indexOf(maxValor);
    document.getElementById('kpiMelhorDia').innerHTML = `${formatarData(state.dados.datas[maxIdx])}<br><small class="text-muted">${formatarMoeda(maxValor)}</small>`;

    const totaisOp = state.dados.totais_operadora || {};
    let lider = ['-', 0];
    for (const [op, val] of Object.entries(totaisOp)) {
        const totalOp = Array.isArray(val) ? val.reduce((a, b) => a + Number(b), 0) : Number(val);
        if (totalOp > lider[1]) lider = [op, totalOp];
    }
    const pct = totalGeral > 0 ? ((lider[1] / totalGeral) * 100).toFixed(1) : '0,0';
    document.getElementById('kpiOperadora').innerHTML = `${lider[0]}<br><small class="text-muted">${formatarMoeda(lider[1])} (${pct}%)</small>`;
}

// ============================================================
// GRÁFICOS (Chart.js)
// ============================================================

function renderizarGraficos() {
    if (!state.dados || !state.dados.datas?.length) return;

    const datas = state.dados.datas.map(formatarData);
    const totalGeral = state.dados.total_geral.map(Number);

    // 1. Faturamento Diário (Line)
    if (chartFaturamento) chartFaturamento.destroy();
    const ctxFat = document.getElementById('chartFaturamento')?.getContext('2d');
    if (ctxFat) {
        chartFaturamento = new Chart(ctxFat, {
            type: 'line',
            data: {
                labels: datas,
                datasets: [{
                    label: 'Faturamento',
                    data: totalGeral,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.08)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => formatarMoeda(ctx.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => formatarMoeda(v) }
                    }
                }
            }
        });
    }

    // 2. Operadoras (Doughnut)
    if (chartOperadoras) chartOperadoras.destroy();
    const ctxOp = document.getElementById('chartOperadoras')?.getContext('2d');
    if (ctxOp && state.dados.totais_operadora) {
        const labels = Object.keys(state.dados.totais_operadora);
        const data = labels.map(op => {
            const vals = state.dados.totais_operadora[op];
            return Array.isArray(vals) ? vals.reduce((a, b) => a + Number(b), 0) : Number(vals);
        });

        chartOperadoras = new Chart(ctxOp, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: labels.map((_, i) => cores[i % cores.length]),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const total = data.reduce((a, b) => a + b, 0);
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return `${ctx.label}: ${formatarMoeda(ctx.parsed)} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Meios de Pagamento (Bar Horizontal)
    if (chartMeios) chartMeios.destroy();
    const ctxMeio = document.getElementById('chartMeios')?.getContext('2d');
    if (ctxMeio) {
        const meiosMap = {};
        state.dadosFiltrados.forEach(linha => {
            const label = linha.meio || 'Outros';
            if (!meiosMap[label]) meiosMap[label] = 0;
            meiosMap[label] += linha.valores.reduce((a, v) => a + Number(v), 0);
        });

        const labelsMeio = Object.keys(meiosMap).sort((a, b) => meiosMap[b] - meiosMap[a]);
        const dataMeio = labelsMeio.map(m => meiosMap[m]);

        chartMeios = new Chart(ctxMeio, {
            type: 'bar',
            data: {
                labels: labelsMeio,
                datasets: [{
                    label: 'Faturamento',
                    data: dataMeio,
                    backgroundColor: labelsMeio.map((_, i) => cores[i % cores.length] + 'CC'),
                    borderRadius: 6,
                    borderSkipped: false
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
                            label: ctx => formatarMoeda(ctx.parsed.x)
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: v => formatarMoeda(v) }
                    }
                }
            }
        });
    }
}

// ============================================================
// INICIALIZAÇÃO
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // 🔥 LER FILTROS ANTES DA PRIMEIRA CHAMADA
    lerFiltros();

    const form = document.getElementById('formFiltros');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            lerFiltros();
            carregarRelatorio(true);
        });
    }

    const btnLimpar = document.getElementById('btnLimpar');
    if (btnLimpar) {
        btnLimpar.addEventListener('click', limparFiltros);
    }

    const selectPag = document.getElementById('itensPorPagina');
    if (selectPag) {
        selectPag.addEventListener('change', function () {
            const val = parseInt(this.value);
            state.itensPorPagina = val > 0 ? val : Infinity;
            state.paginaAtual = 1;
            renderizarTabela();
            renderizarPaginacao();
        });
    }

    document.addEventListener('click', function (e) {
        const th = e.target.closest('th.sortable');
        if (th) {
            const col = th.dataset.col;
            if (col) sortTable(col);
            return;
        }
        const link = e.target.closest('.page-link[data-pag]');
        if (link) {
            e.preventDefault();
            const pag = link.dataset.pag;
            mudarPagina(pag);
        }
    });

    // 🔥 AGORA sim, chama com os filtros preenchidos
    carregarRelatorio();
});