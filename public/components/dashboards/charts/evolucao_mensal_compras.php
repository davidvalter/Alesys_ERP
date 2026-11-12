<?php
/* Evolução mensal das notas de compra ativas nos últimos 12 meses. */
require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sql = "SELECT DATE_FORMAT(DataEmissao, '%Y-%m') AS mes, COALESCE(SUM(ValorTotal), 0) AS total
            FROM NFe
            WHERE Status = 1
              AND DataEmissao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND DataEmissao < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            GROUP BY mes
            ORDER BY mes";
    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $labels = [];
    $valores = [];
    foreach ($dados as $row) {
        $labels[] = date('M/Y', strtotime($row['mes'] . '-01'));
        $valores[] = (float)$row['total'];
    }
} catch (Throwable $e) {
    error_log('Erro no gráfico de evolução de compras: ' . $e->getMessage());
    $labels = [];
    $valores = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header"><h6>Evolução Mensal de Compras</h6></div>
    <div class="chart-card-body">
        <?php if ($labels): ?>
            <canvas id="chartEvolucaoCompras"></canvas>
        <?php else: ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-muted">Sem notas de compra ativas no período.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($labels): ?>
<script>
new Chart(document.getElementById('chartEvolucaoCompras'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'Compras',
            data: <?= json_encode($valores) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => 'R$ ' + Number(ctx.raw).toLocaleString('pt-BR', {minimumFractionDigits: 2}) } }
        },
        scales: { y: { ticks: { callback: value => 'R$ ' + Number(value).toLocaleString('pt-BR') } } }
    }
});
</script>
<?php endif; ?>
