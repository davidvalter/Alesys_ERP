<?php
/* /public/components/dashboards/charts/historico_precos.php */

require_once __DIR__ . '/../../../../configs/database.php';

$produtoId = 1;

try {
    $pdo = getDB();

    $nomeProduto = $pdo->query("SELECT Nome FROM Produtos WHERE Codigo = $produtoId")->fetchColumn();

    $sql = "SELECT 
                DATE_FORMAT(n.DataEmissao, '%Y-%m') mes,
                AVG(ni.ValorUnitario) preco
            FROM NFeItens ni
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            WHERE ni.fk_Produtos_Codigo = $produtoId
            GROUP BY mes
            ORDER BY mes";

    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $valores = [];

    foreach ($dados as $row) {
        $labels[] = date('M/Y', strtotime($row['mes']));
        $valores[] = (float)$row['preco'];
    }

} catch (Exception $e) {
    $labels = [];
    $valores = [];
    $nomeProduto = 'Produto';
}
?>

<div class="chart-card">
    <div class="chart-card-header d-flex justify-content-between">
        <h6 id="tituloHistorico">Histórico - <?= htmlspecialchars($nomeProduto) ?></h6>
        <select id="selectProduto" class="form-select form-select-sm w-auto"></select>
    </div>

    <div class="chart-card-body">
        <canvas id="chartHistorico"></canvas>
    </div>
</div>

<script>
const chartHistorico = new Chart(document.getElementById('chartHistorico'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: <?= json_encode($valores) ?>,
            borderColor: '#3b82f6',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

fetch('api/get_produtos_dashboard.php')
.then(r => r.json())
.then(data => {
    const sel = document.getElementById('selectProduto');

    data.data.forEach(p => {
        sel.appendChild(new Option(p.Nome, p.Codigo));
    });

    sel.onchange = () => {
        fetch(`api/get_historico_precos.php?product_id=${sel.value}`)
        .then(r => r.json())
        .then(d => {
            chartHistorico.data.labels = d.labels;
            chartHistorico.data.datasets[0].data = d.values;
            chartHistorico.update();

            document.getElementById('tituloHistorico').innerText =
                'Histórico - ' + d.productName;
        });
    };
});
</script>