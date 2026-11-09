<?php
$moduloAtual = 'compras.comparacaoprecos';
$tituloPagina = 'Comparação de Preços';
$tituloConteudo = 'Comparação de Preços';

$extraCss = '
<link rel="stylesheet" href="/styles/components_chart_card.css">
';

require_once __DIR__ . '/../templates/base_protected_v2.php';
?>

<div class="container-fluid">

    <!-- Header -->
    <div class="mb-4">
        <h4 class="mb-0">Comparação de Preços</h4>
        <small class="text-muted">Analise preços por fornecedor e período</small>
    </div>

    <!-- ====================== AÇÕES ====================== -->

    <div class="card card-default mb-3">
        <div class="card-body d-flex flex-wrap gap-2">

            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>

        </div>
    </div>

    <!-- Filtros -->
    <?php include '../components/compras/filtros_comparacao.php'; ?>

    <!-- Resultados -->
    <?php include '../components/compras/tabela_resultados.php'; ?>

    <!-- Gráfico -->
    <div class="card">
        <div class="card-header">
            <strong>Gráfico Comparativo</strong>
        </div>
        <div class="card-body">
            <div style="height: 400px;">
                <canvas id="graficoComparacao"></canvas>
            </div>
        </div>
    </div>

</div>

<?php require_once '../templates/footer_protected_v2.php'; ?>

<script src="/js/libs/chart.js"></script>
<script src="../js/comparacao_precos.js"></script>