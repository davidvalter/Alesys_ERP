<?php
/* Fornecedores com maior volume de compras ativas nos últimos seis meses. */
require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sql = "SELECT m.Nome, COALESCE(SUM(n.ValorTotal), 0) AS total
            FROM NFe n
            JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
            WHERE n.Status = 1
              AND n.DataEmissao >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              AND n.DataEmissao < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            GROUP BY m.Codigo, m.Nome
            ORDER BY total DESC
            LIMIT 5";
    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $labels = array_column($dados, 'Nome');
    $valores = array_map('floatval', array_column($dados, 'total'));
} catch (Throwable $e) {
    error_log('Erro no gráfico de fornecedores: ' . $e->getMessage());
    $labels = [];
    $valores = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header"><h6>Top 5 Fornecedores</h6></div>
    <div class="chart-card-body">
        <?php if ($labels): ?>
            <canvas id="chartTopFornecedores"></canvas>
        <?php else: ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-muted">Sem compras ativas nos últimos seis meses.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($labels): ?>
<script>
new Chart(document.getElementById('chartTopFornecedores'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ label: 'Compras', data: <?= json_encode($valores) ?>, backgroundColor: '#10b981' }]
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
