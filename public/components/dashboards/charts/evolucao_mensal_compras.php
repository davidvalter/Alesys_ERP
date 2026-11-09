<?php
/* /public/components/dashboards/charts/evolucao_mensal_compras.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = getDB();

    $sql = "SELECT 
                DATE_FORMAT(DataEmissao, '%Y-%m') AS mes,
                SUM(ValorTotal) AS total
            FROM NFe
            WHERE DataEmissao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY mes
            ORDER BY mes";

    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];

    foreach ($dados as $row) {
        $labels[] = date('M/Y', strtotime($row['mes']));
        $valores[] = (float)$row['total'];
    }

} catch (Exception $e) {
    $labels = [];
    $valores = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header">
        <h6>Evolução Mensal de Compras</h6>
    </div>
    <div class="chart-card-body">
        <canvas id="chartEvolucaoCompras"></canvas>
    </div>
</div>

<script>
new Chart(document.getElementById('chartEvolucaoCompras'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
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
            tooltip: {
                callbacks: {
                    label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2})
                }
            }
        },
        scales: {
            y: {
                ticks: {
                    callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                }
            }
        }
    }
});
</script>