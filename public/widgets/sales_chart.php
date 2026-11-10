<section class="dashboard-panel dashboard-chart-panel" aria-labelledby="dashboard-chart-title">
    <div class="dashboard-panel-heading">
        <div>
            <span class="dashboard-eyebrow">Desempenho</span>
            <h2 id="dashboard-chart-title">Vendas de pedidos fechados</h2>
        </div>
        <span class="dashboard-period-caption" id="dashboard-period-caption">Últimos 7 dias</span>
    </div>
    <div class="dashboard-chart-wrap">
        <canvas id="dashboard-sales-chart" role="img" aria-label="Gráfico de vendas no período selecionado"></canvas>
        <div id="dashboard-chart-empty" class="dashboard-empty dashboard-chart-empty" hidden>
            Ainda não há vendas fechadas neste período.
        </div>
    </div>
</section>
