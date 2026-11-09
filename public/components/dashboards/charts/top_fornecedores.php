<?php
/* /public/components/dashboards/charts/top_fornecedores.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = getDB();

    $sql = "SELECT m.Nome, SUM(n.ValorTotal) total
            FROM NFe n
            JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
            WHERE n.DataEmissao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY m.Nome
            ORDER BY total DESC
            LIMIT 5";

    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($dados, 'Nome');
    $valores = array_map('floatval', array_column($dados, 'total'));

} catch (Exception $e) {
    $labels = [];
    $valores = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header">
        <h6>Top 5 Fornecedores</h6>
    </div>
    <div class="chart-card-body">
        <canvas id="chartTopFornecedores"></canvas>
    </div>
</div>

<script>
    new Chart(document.getElementById('chartTopFornecedores'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($valores) ?>,
                backgroundColor: '#10b981'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'R$ ' + ctx.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
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