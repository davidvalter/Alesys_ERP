<?php
/* /public/components/dashboards/charts/categorias_compras.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = getDB();

    $sql = "SELECT 
                p.Categoria,
                SUM(ni.ValorTotal) total
            FROM NFeItens ni
            JOIN Produtos p ON ni.fk_Produtos_Codigo = p.Codigo
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            GROUP BY p.Categoria
            ORDER BY total DESC";

    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];
    $cores = ['#3b82f6','#10b981','#f59e0b','#ef4444','#06b6d4','#8b5cf6'];

    foreach ($dados as $i => $row) {
        $labels[] = $row['Categoria'] ?: 'Sem categoria';
        $valores[] = (float)$row['total'];
    }

} catch (Exception $e) {
    $labels = [];
    $valores = [];
    $cores = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header">
        <h6>Compras por Categoria</h6>
    </div>
    <div class="chart-card-body">
        <canvas id="chartCategorias"></canvas>
    </div>
</div>

<script>
new Chart(document.getElementById('chartCategorias'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: <?= json_encode($valores) ?>,
            backgroundColor: <?= json_encode($cores) ?>,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        const val = ctx.raw;
                        const pct = Math.round((val/total)*100);
                        return `${ctx.label}: R$ ${val.toLocaleString('pt-BR')} (${pct}%)`;
                    }
                }
            }
        }
    }
});
</script>