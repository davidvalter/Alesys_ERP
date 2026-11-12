<?php
$moduloAtual = 'compras.comparacaoprecos';
$tituloPagina = 'Comparação de Preços';
$tituloConteudo = 'Comparação de Preços';

$extraCss = '
<link rel="stylesheet" href="/styles/components_chart_card.css">
<style>
.multi-picker { position: relative; }
.multi-picker-menu { position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1080; max-height: 280px; overflow-y: auto; padding: .35rem; background: #fff; border: 1px solid #ced4da; border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.14); }
.multi-picker-menu[hidden] { display: none !important; }
.multi-picker-option { display: flex; align-items: flex-start; gap: .6rem; padding: .55rem .6rem; border-radius: .35rem; cursor: pointer; }
.multi-picker-option:hover { background: #f1f8f4; }
.multi-picker-option input { margin-top: .25rem; accent-color: #198754; }
.multi-picker-empty { color: #6c757d; padding: .7rem; }
.multi-picker-selected { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
.multi-picker-chip { display: inline-flex; align-items: center; gap: .35rem; max-width: 100%; padding: .2rem .5rem; border-radius: 999px; background: #e8f3ec; color: #17643a; font-size: .8rem; }
.multi-picker-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.multi-picker-chip button { border: 0; padding: 0; background: transparent; color: inherit; line-height: 1; }
.multi-picker-values { display: none; }
</style>
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

<!-- Os scripts da página precisam vir antes do footer, que encerra o HTML. -->
<script src="/js/libs/chart.js"></script>
<script src="/js/comparacao_precos.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/comparacao_precos.js') ?>"></script>

<?php require_once '../templates/footer_protected_v2.php'; ?>
