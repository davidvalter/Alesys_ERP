(() => {
    'use strict';

    const apiUrl = '/api/dashboard_resumo.php';
    const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    const integer = new Intl.NumberFormat('pt-BR');
    const periodLabels = { hoje: 'Hoje', '7d': 'Últimos 7 dias', mes: 'Este mês' };
    let salesChart = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);

    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    const showFeedback = (message) => {
        const feedback = document.getElementById('dashboard-feedback');
        if (!feedback) return;
        feedback.textContent = message;
        feedback.hidden = !message;
    };

    const formatVariation = (value, comparisonLabel) => {
        if (value === null || typeof value === 'undefined') return 'Sem base de comparação';
        const direction = value > 0 ? '▲' : (value < 0 ? '▼' : '•');
        const sign = value > 0 ? '+' : '';
        return direction + ' ' + sign + value.toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '% ' + comparisonLabel;
    };

    const renderMetrics = (data) => {
        const metrics = data.metricas || {};
        setText('metric-sales', currency.format(Number(metrics.vendas?.valor || 0)));
        setText('metric-orders', integer.format(Number(metrics.pedidosFechados?.valor || 0)));
        setText('metric-ticket', currency.format(Number(metrics.ticketMedio?.valor || 0)));
        setText('metric-open-orders', integer.format(Number(metrics.pedidosAbertos?.valor || 0)));

        [
            ['metric-sales-change', metrics.vendas?.variacao],
            ['metric-orders-change', metrics.pedidosFechados?.variacao],
            ['metric-ticket-change', metrics.ticketMedio?.variacao]
        ].forEach(([id, variation]) => {
            const element = document.getElementById(id);
            if (!element) return;
            element.textContent = formatVariation(variation, data.rotuloComparacao || 'no período anterior');
            element.classList.remove('is-positive', 'is-negative');
            if (typeof variation === 'number') {
                element.classList.add(variation >= 0 ? 'is-positive' : 'is-negative');
            }
        });
        setText('dashboard-period-caption', periodLabels[data.periodo] || data.rotuloPeriodo || 'Período selecionado');

        const updatedAt = new Date(data.atualizadoEm);
        setText('dashboard-updated', Number.isNaN(updatedAt.getTime())
            ? ''
            : 'Atualizado às ' + new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(updatedAt));
    };

    const renderChart = (trend) => {
        const canvas = document.getElementById('dashboard-sales-chart');
        const empty = document.getElementById('dashboard-chart-empty');
        if (!canvas || typeof window.Chart === 'undefined') return;

        const points = Array.isArray(trend) ? trend : [];
        const hasSales = points.some((point) => Number(point.vendas) > 0);
        canvas.hidden = !hasSales;
        if (empty) empty.hidden = hasSales;

        if (salesChart) {
            salesChart.destroy();
            salesChart = null;
        }
        if (!hasSales) return;

        salesChart = new window.Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: points.map((point) => point.label),
                datasets: [{
                    label: 'Vendas',
                    data: points.map((point) => Number(point.vendas)),
                    borderColor: '#2f6b4f',
                    backgroundColor: 'rgba(47, 107, 79, .10)',
                    borderWidth: 2.5,
                    pointRadius: points.length > 12 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2f6b4f',
                    fill: true,
                    tension: .35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => ' ' + currency.format(context.parsed.y) } }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#748178', maxTicksLimit: 8, maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#edf1ed' },
                        ticks: {
                            color: '#748178',
                            callback: (value) => {
                                const amount = Number(value);
                                return amount >= 1000
                                    ? 'R$ ' + (amount / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil'
                                    : 'R$ ' + amount.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    };

    const renderAlerts = (alerts) => {
        const container = document.getElementById('dashboard-alert-list');
        if (!container) return;
        const items = Array.isArray(alerts) ? alerts : [];
        if (!items.length) {
            container.innerHTML = '<div class="dashboard-empty"><i class="fas fa-circle-check" aria-hidden="true"></i>Nenhuma das pendências monitoradas no momento.</div>';
            return;
        }
        container.innerHTML = items.map((item) => {
            const type = ['warning', 'danger', 'info'].includes(item.tipo) ? item.tipo : 'info';
            const icon = escapeHtml(item.icone || 'fa-circle-info');
            return '<a class="dashboard-alert" href="' + escapeHtml(item.url || '#') + '">' +
                '<span class="dashboard-alert-icon is-' + type + '"><i class="fas ' + icon + '" aria-hidden="true"></i></span>' +
                '<span class="dashboard-alert-copy"><span class="dashboard-alert-title">' +
                '<span>' + escapeHtml(item.titulo) + '</span><span class="dashboard-alert-count">' + integer.format(Number(item.quantidade || 0)) +
                '</span></span><span class="dashboard-alert-description">' + escapeHtml(item.descricao) + '</span></span>' +
                '<i class="fas fa-chevron-right dashboard-shortcut-arrow" aria-hidden="true"></i></a>';
        }).join('');
    };

    const renderOrders = (orders) => {
        const body = document.getElementById('dashboard-open-orders');
        if (!body) return;
        const items = Array.isArray(orders) ? orders : [];
        if (!items.length) {
            body.innerHTML = '<tr><td colspan="4" class="dashboard-table-message">Não há pedidos em aberto.</td></tr>';
            return;
        }

        const typeLabels = { mesa: 'Mesa', delivery: 'Delivery', balcao: 'Balcão', manual: 'Manual' };
        body.innerHTML = items.map((order) => {
            const client = String(order.cliente || '').trim();
            const place = client || (order.mesa ? 'Mesa ' + order.mesa : (typeLabels[order.tipo] || 'Pedido sem identificação'));
            const dateValue = new Date(String(order.abertoEm || '').replace(' ', 'T'));
            const time = Number.isNaN(dateValue.getTime())
                ? '—'
                : new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(dateValue);
            const type = typeLabels[order.tipo] || 'Pedido';
            return '<tr><td><a href="/vendas/pdv.php" class="text-decoration-none">#' + integer.format(Number(order.id)) +
                '</a><span class="dashboard-order-type">' + escapeHtml(type) + '</span></td>' +
                '<td>' + escapeHtml(place) + '</td><td>' + escapeHtml(time) + '</td>' +
                '<td class="text-end">' + currency.format(Number(order.valor || 0)) + '</td></tr>';
        }).join('');
    };

    const loadDashboard = async () => {
        const period = document.getElementById('dashboard-period')?.value || '7d';
        const button = document.getElementById('dashboard-refresh');
        if (button) button.disabled = true;
        showFeedback('');

        try {
            const response = await fetch(apiUrl + '?periodo=' + encodeURIComponent(period), {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Não foi possível carregar os dados do dashboard.');
            }
            renderMetrics(data);
            renderChart(data.tendencia);
            renderAlerts(data.alertas);
            renderOrders(data.pedidosRecentes);
        } catch (error) {
            showFeedback(error.message || 'Ocorreu um erro ao carregar o dashboard.');
        } finally {
            if (button) button.disabled = false;
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('dashboard-period')?.addEventListener('change', loadDashboard);
        document.getElementById('dashboard-refresh')?.addEventListener('click', loadDashboard);
        loadDashboard();
    });
})();
