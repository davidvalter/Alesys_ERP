<?php
/* Distribuição das notas de compra ativas por dia da semana. */
require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sql = "SELECT DAYOFWEEK(DataEmissao) AS dia, COUNT(*) AS qtd, COALESCE(SUM(ValorTotal), 0) AS total
            FROM NFe
            WHERE Status = 1
            GROUP BY dia";
    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $map = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $qtd = array_fill(0, 7, 0);
    $valores = array_fill(0, 7, 0);
    foreach ($dados as $row) {
        $indice = (int)$row['dia'] - 1;
        if ($indice >= 0 && $indice < 7) {
            $qtd[$indice] = (int)$row['qtd'];
            $valores[$indice] = (float)$row['total'];
        }
    }
    $temDados = array_sum($qtd) > 0;
} catch (Throwable $e) {
    error_log('Erro no gráfico de compras por dia da semana: ' . $e->getMessage());
    $map = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $qtd = array_fill(0, 7, 0);
    $valores = array_fill(0, 7, 0);
    $temDados = false;
}
?>

<div class="chart-card">
    <div class="chart-card-header d-flex justify-content-between align-items-center flex-wrap">
        <h6>Compras por Dia da Semana</h6>
        <div>
            <button class="btn btn-sm btn-outline-primary active" data-compras-tipo="qtd">Qtd.</button>
            <button class="btn btn-sm btn-outline-secondary" data-compras-tipo="valor">R$</button>
        </div>
    </div>
    <div class="chart-card-body">
        <?php if ($temDados): ?>
            <canvas id="chartSemana"></canvas>
        <?php else: ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-muted">Sem notas de compra ativas.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($temDados): ?>
<script>
(() => {
    const container = document.getElementById('chartSemana').closest('.chart-card');
    const quantidade = <?= json_encode($qtd) ?>;
    const valores = <?= json_encode($valores) ?>;
    const chart = new Chart(document.getElementById('chartSemana'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($map) ?>,
            datasets: [{ label: 'Quantidade de notas', data: quantidade, backgroundColor: '#3b82f6' }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
    container.querySelectorAll('[data-compras-tipo]').forEach(button => {
        button.addEventListener('click', () => {
            container.querySelectorAll('[data-compras-tipo]').forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            const porValor = button.dataset.comprasTipo === 'valor';
            chart.data.datasets[0].data = porValor ? valores : quantidade;
            chart.data.datasets[0].label = porValor ? 'Valor comprado' : 'Quantidade de notas';
            chart.options.plugins.tooltip = porValor
                ? { callbacks: { label: context => 'R$ ' + Number(context.raw).toLocaleString('pt-BR', {minimumFractionDigits: 2}) } }
                : { callbacks: { label: context => Number(context.raw).toLocaleString('pt-BR') + ' notas' } };
            chart.update();
        });
    });
})();
</script>
<?php endif; ?>
