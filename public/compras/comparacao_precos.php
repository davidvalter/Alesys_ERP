<?php
$moduloAtual = 'compras.comparacaoprecos';
$tituloPagina = 'Comparação de Preços';
$tituloConteudo = 'Comparação de Preços';

$extraCss = '
<link rel="stylesheet" href="/styles/components_chart_card.css">
';

require_once __DIR__ . '/../templates/base_protected_v2.php';
?>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-success fs-4"><i class="fas fa-scale-balanced"></i></span>
                <h4 class="mb-0">Comparação de preços</h4>
            </div>
            <small class="text-muted">Compare o histórico de compra de produtos entre fornecedores.</small>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Compras
        </a>
    </div>

    <?php include '../components/compras/filtros_comparacao.php'; ?>

    <section class="row g-3 mb-3" id="cardsResumo" aria-live="polite" hidden>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-semibold">Produtos analisados</div>
                    <div class="fs-3 fw-bold" id="resumoProdutos">—</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-semibold">Fornecedores encontrados</div>
                    <div class="fs-3 fw-bold" id="resumoFornecedores">—</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-uppercase text-muted fw-semibold">Menor preço médio</div>
                    <div class="fs-5 fw-bold text-success" id="resumoMenorPreco">—</div>
                    <div class="small text-muted" id="resumoMenorPrecoDetalhe"></div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../components/compras/tabela_resultados.php'; ?>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-0">Comparativo visual por fornecedor</h6>
                <small class="text-muted">Cada série representa um produto; os valores mostram o preço médio.</small>
            </div>
        </div>
        <div class="card-body">
            <div id="estadoGrafico" class="text-center text-muted py-5">
                <i class="fas fa-chart-column fs-3 d-block mb-2"></i>
                Gere uma comparação para visualizar os preços.
            </div>
            <div id="containerGrafico" style="height: 380px;" hidden>
                <canvas id="graficoComparacao" aria-label="Comparativo de preços por fornecedor" role="img"></canvas>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer_protected_v2.php'; ?>

<script src="/js/libs/chart.js"></script>
<script src="/js/comparacao_precos.js"></script>
