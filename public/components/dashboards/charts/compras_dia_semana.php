<?php
/* /public/components/dashboards/charts/compras_dia_semana.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = getDB();

    $sql = "SELECT DAYOFWEEK(DataEmissao) dia,
                   COUNT(*) qtd,
                   SUM(ValorTotal) total
            FROM NFe
            GROUP BY dia";

    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $map = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

    $labels = [];
    $qtd = [];
    $val = [];

    foreach ($dados as $row) {
        $labels[] = $map[$row['dia'] - 1];
        $qtd[] = (int)$row['qtd'];
        $val[] = (float)$row['total'];
    }

} catch (Exception $e) {
    $labels = [];
    $qtd = [];
    $val = [];
}
?>

<div class="chart-card">
    <div class="chart-card-header">
        <h6>Compras por Dia</h6>

        <div class="mt-2">
            <button class="btn btn-sm btn-outline-primary active" data-type="qtd">Qtd</button>
            <button class="btn btn-sm btn-outline-secondary" data-type="val">R$</button>
        </div>
    </div>

    <div class="chart-card-body">
        <canvas id="chartSemana"></canvas>
    </div>
</div>

<script>
const dataSemana = {
    qtd: <?= json_encode($qtd) ?>,
    val: <?= json_encode($val) ?>
};

const chartSemana = new Chart(document.getElementById('chartSemana'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            data: dataSemana.qtd,
            backgroundColor: '#3b82f6'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

document.querySelectorAll('[data-type]').forEach(btn => {
    btn.onclick = () => {
        document.querySelectorAll('[data-type]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        chartSemana.data.datasets[0].data = dataSemana[btn.dataset.type];
        chartSemana.update();
    };
});
</script>