<?php
/* /public/compras/index.php */

session_start();

$moduloAtual = 'compras';
$tituloPagina = 'Gerenciamento de Compras';
$tituloConteudo = 'Gerenciamento de Compras';

$extraCss = '
<link rel="stylesheet" href="/styles/components_kpis_card.css">
<link rel="stylesheet" href="/styles/components_chart_card.css">
';

require_once __DIR__ . '/../templates/base_protected_v2.php';
?>

<div class="container-fluid">

    <!-- Header com ação -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

        <!-- Título -->
        <div>
            <h4 class="mb-0">Visão Geral de Compras</h4>
            <small class="text-muted">Acompanhe indicadores e desempenho de compras</small>
        </div>

        <!-- ======================AÇÕES DE COMPRAS===================== -->

        <div class="card card-default mb-3">

            <div class="card-body d-flex flex-wrap gap-2">

                <a href="produtos.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-box me-1"></i>
                    Produtos
                </a>

                <a href="mercados.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-truck me-1"></i>
                    Fornecedores / Mercados
                </a>

                <a href="notas_fiscais.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-invoice me-1"></i>
                    Notas Fiscais
                </a>

                <a href="comparacao_precos.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-shop me-1"></i>
                    Comparar Preços
                </a>

            </div>

        </div>

    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-lg-3">
            <?php include '../components/dashboards/kpis/total_compras_mes.php'; ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <?php include '../components/dashboards/kpis/ticket_medio_nfe.php'; ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <?php include '../components/dashboards/kpis/fornecedores_ativos.php'; ?>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <?php include '../components/dashboards/kpis/produtoscadastrados.php'; ?>
        </div>

    </div>

    <!-- Gráficos -->
    <div class="row g-4">

        <div class="col-12 col-lg-6">
            <?php include '../components/dashboards/charts/evolucao_mensal_compras.php'; ?>
        </div>

        <div class="col-12 col-lg-6">
            <?php include '../components/dashboards/charts/top_fornecedores.php'; ?>
        </div>

        <div class="col-12 col-lg-6">
            <?php include '../components/dashboards/charts/categorias_compras.php'; ?>
        </div>

        <div class="col-12 col-lg-6">
            <?php include '../components/dashboards/charts/historico_precos.php'; ?>
        </div>

        <div class="col-12">
            <?php include '../components/dashboards/charts/compras_dia_semana.php'; ?>
        </div>

    </div>

</div>

<?php require_once '../templates/footer_protected_v2.php'; ?>

<!-- Chart.js -->
<script src="/js/libs/chart.js"></script>