<?php
/* Categorias dos itens em notas de compra ativas. */
require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sql = "SELECT p.Categoria, COALESCE(SUM(ni.ValorTotal), 0) AS total
            FROM NFeItens ni
            JOIN Produtos p ON ni.fk_Produtos_Codigo = p.Codigo
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            WHERE n.Status = 1
            GROUP BY p.Categoria
            ORDER BY total DESC";
    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $labels = [];
    $valores = [];
    $cores = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6'];
    foreach ($dados as $row) {
        $labels[] = $row['Categoria'] ?: 'Sem categoria';
        $valores[] = (float)$row['total'];
    }
} catch (Throwable $e) {
    error_log('Erro no gráfico de categorias de compras: ' . $e->getMessage());
    $labels = [];
    $valores = [];
    $cores = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header"><h6>Compras por Categoria</h6></div>
    <div class="chart-card-body">
        <?php if ($labels): ?>
            <canvas id="chartCategorias"></canvas>
        <?php else: ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-muted">Sem itens vinculados a notas de compra ativas.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($labels): ?>
<script>
new Chart(document.getElementById('chartCategorias'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{ data: <?= json_encode($valores) ?>, backgroundColor: <?= json_encode($cores) ?>, borderWidth: 0 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' },
            tooltip: { callbacks: { label: ctx => {
                const total = ctx.dataset.data.reduce((sum, value) => sum + Number(value), 0);
                const value = Number(ctx.raw);
                const pct = total ? Math.round(value / total * 100) : 0;
                return ctx.label + ': R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ' (' + pct + '%)';
            } } }
        }
    }
});
</script>
<?php endif; ?>
