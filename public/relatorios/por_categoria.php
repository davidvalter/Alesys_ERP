<?php
// public/relatorios/por_categoria.php

session_start();

$moduloAtual = 'financeiro.relatorios';
$tituloPagina = 'Relatório por Categoria';
$tituloConteudo = 'Relatório por Categoria';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

$db = getDB();

/* =========================
   FILTROS
========================= */

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$categoriasSelecionadas = $_GET['categorias'] ?? [];
$tipoSelecionado = $_GET['tipo'] ?? '';
$visao = $_GET['visao'] ?? 'mensal';

/* =========================
   CATEGORIAS
========================= */

$categorias = $db->query("
    SELECT Id, Nome
    FROM FinanceiroCategorias
    WHERE Ativo = 1
    ORDER BY Nome
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FILTROS SQL
========================= */

$params = [
    ':dataInicio' => $dataInicio,
    ':dataFim' => $dataFim
];

$filtroTipo = '';
if ($tipoSelecionado) {
    $filtroTipo = " AND l.Tipo = :tipo ";
    $params[':tipo'] = $tipoSelecionado;
}

$filtroCategorias = '';
if ($categoriasSelecionadas) {
    $in = [];
    foreach ($categoriasSelecionadas as $i => $catId) {
        $key = ":cat{$i}";
        $in[] = $key;
        $params[$key] = $catId;
    }
    $filtroCategorias = " AND l.CategoriaId IN (" . implode(',', $in) . ")";
}

$campoPeriodo = "DATE_FORMAT(l.DataBaixa, '%Y-%m')";

/* =========================
   QUERY
========================= */

$sql = "
    SELECT
        $campoPeriodo AS Periodo,
        c.Nome AS Categoria,
        l.Tipo,
        SUM(l.ValorBruto) AS Total
    FROM FinanceiroLancamentos l
    INNER JOIN FinanceiroCategorias c ON c.Id = l.CategoriaId
    WHERE l.Status = 'pago'
      AND l.DataBaixa BETWEEN :dataInicio AND :dataFim
      $filtroTipo
      $filtroCategorias
    GROUP BY Periodo, c.Id, l.Tipo
    ORDER BY Periodo DESC, c.Nome
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   ORGANIZA DADOS
========================= */

$dados = [];
foreach ($resultados as $r) {
    $p = $r['Periodo'];
    $c = $r['Categoria'];

    if (!isset($dados[$p][$c])) {
        $dados[$p][$c] = ['entrada' => 0, 'saida' => 0];
    }

    $dados[$p][$c][$r['Tipo']] += (float) $r['Total'];
}

/* =========================
   TOTAL POR PERÍODO
========================= */

$totaisPeriodo = [];
foreach ($dados as $p => $cats) {
    $totaisPeriodo[$p] = 0;
    foreach ($cats as $v) {
        $totaisPeriodo[$p] += $v['entrada'];
    }
}

/* =========================
   GRÁFICO
========================= */

$chartLabels = array_keys($dados);
sort($chartLabels);

$chartDatasets = [];
foreach ($dados as $p => $cats) {
    foreach ($cats as $cat => $v) {
        $chartDatasets[$cat][$p] = $v['entrada'];
    }
}

$datasetsFinal = [];
foreach ($chartDatasets as $cat => $valores) {
    $serie = [];
    foreach ($chartLabels as $p) {
        $serie[] = $valores[$p] ?? 0;
    }

    $datasetsFinal[] = [
        'label' => $cat,
        'data' => $serie,
        'fill' => false,
        'tension' => 0.3
    ];
}
?>

<div class="container-fluid relatorio-categoria">

    <!-- HEADER -->
    <div class="page-header mb-4">
        <div>
            <i class="fas fa-tags me-2"></i>
            <small class="text-muted">
                Análise de entradas e saídas por período
            </small>
        </div>
        <div class="page-actions">
            <button type="button" onclick="imprimirRelatorio()" class="btn btn-outline-secondary">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card card-modern mb-4">
        <div class="card-header">
            Filtros
        </div>
        <div class="card-body">
            <form method="get">
                <div class="row g-3 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label">Visão</label>
                        <select name="visao" class="form-select">
                            <option value="mensal" <?= $visao === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                            <option value="anual" <?= $visao === 'anual' ? 'selected' : '' ?>>Anual</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Data início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= $dataInicio ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Data fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?= $dataFim ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="entrada" <?= $tipoSelecionado === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                            <option value="saida" <?= $tipoSelecionado === 'saida' ? 'selected' : '' ?>>Saída</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Categorias</label>
                        <select name="categorias[]" class="form-select" multiple size="5">
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['Id'] ?>"
                                    <?= in_array($c['Id'], $categoriasSelecionadas) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['Nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-success w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- GRÁFICO -->
    <div class="card card-modern mb-4">
        <div class="card-header">Evolução por Período</div>
        <div class="card-body">
            <canvas id="graficoCategorias" height="100"></canvas>
        </div>
    </div>

    <!-- TABELAS -->
    <?php foreach ($dados as $p => $cats): ?>
        <div class="card card-modern mb-4">
            <div class="card-header">
                <?= $p ?>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th class="text-end">Entradas</th>
                                <th class="text-end">Saídas</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-end">% Total</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php foreach ($cats as $cat => $v):
                                $entrada = $v['entrada'];
                                $saida = $v['saida'];
                                $saldo = $entrada - $saida;
                                $percentual = ($totaisPeriodo[$p] > 0)
                                    ? ($entrada / $totaisPeriodo[$p]) * 100
                                    : 0;
                                ?>

                                <tr>
                                    <td><?= htmlspecialchars($cat) ?></td>
                                    <td class="text-end text-success fw-semibold">
                                        <?= number_format($entrada, 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-danger fw-semibold">
                                        <?= number_format($saida, 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= number_format($saldo, 2, ',', '.') ?>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($percentual, 1, ',', '.') ?>%
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    <?php endforeach; ?>

</div>

<script src="/js/libs/chart.js"></script>
<script>
new Chart(document.getElementById('graficoCategorias'), {
    type: '<?= $visao === 'anual' ? 'line' : 'bar' ?>',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: <?= json_encode($datasetsFinal) ?>
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<script>
function imprimirRelatorio() {
    setTimeout(() => window.print(), 800);
}
</script>

<link rel="stylesheet" href="/styles/relatorios_por_categoria.css">
<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>