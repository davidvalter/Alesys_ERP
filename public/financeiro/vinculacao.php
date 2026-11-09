<?php
/* /public/financeiro/vinculacao.php */

session_start();

$moduloAtual = 'financeiro.vinculacao';
$tituloPagina = 'Vinculação NF-e x Lançamentos';
$tituloConteudo = 'Vinculação NF-e x Lançamentos Financeiros';
$extraCss = '<link rel="stylesheet" href="/styles/conciliacao.css">';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

verificarPermissao($moduloAtual);

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}
?>

<!-- ====================== AÇÕES ====================== -->
<div class="card card-default mb-3 d-print-none">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <a href="/financeiro/conciliacao.php" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar para conciliação
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">

    <div class="alert alert-info d-print-none">
        <i class="fas fa-info-circle me-2"></i>
        Vincule lançamentos financeiros às NF-e correspondentes. O vínculo é o critério
        mais forte do matching automático da conciliação.
    </div>

    <!-- ===================== MODO (DIREÇÃO) ===================== -->
    <ul class="nav nav-tabs mb-3 d-print-none" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-por-lancamento" type="button"
                role="tab">
                <i class="fas fa-wallet me-1"></i> Por lançamento
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-por-nfe" type="button" role="tab">
                <i class="fas fa-file-invoice me-1"></i> Por NF-e
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ============ ABA 1: POR LANÇAMENTO ============ -->
        <div class="tab-pane fade show active" id="tab-por-lancamento" role="tabpanel">
            <!-- Filtro de data dos LANÇAMENTOS -->
            <div class="card mb-3 d-print-none">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3 col-5">
                            <label class="form-label mb-0 small" for="vinc-data-ini-lanc">Lançamentos de</label>
                            <input type="date" id="vinc-data-ini-lanc" class="form-control form-control-sm">
                        </div>
                        <div class="col-lg-3 col-5">
                            <label class="form-label mb-0 small" for="vinc-data-fim-lanc">Até</label>
                            <input type="date" id="vinc-data-fim-lanc" class="form-control form-control-sm">
                        </div>
                        <div class="col-lg-2 col-2">
                            <button id="vinc-btn-filtrar-lanc" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-lg-4 text-muted small">
                            Filtro pela data do lançamento financeiro (padrão: últimos 30 dias).
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-wallet me-2 text-muted"></i>Lançamentos sem vínculo</h5>
                            <span class="badge bg-warning text-dark" id="vinc-badge-lancamentos">0</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="vinc-loading-lancamentos" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                            <div id="vinc-lista-lancamentos" class="conc-lista"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-invoice me-2 text-muted"></i>NF-e candidatas</h5>
                            <span class="badge bg-primary" id="vinc-badge-selec-lanc">Nenhum lançamento</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="vinc-loading-cand-nfe" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                            <div id="vinc-lista-cand-nfe" class="conc-lista"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ ABA 2: POR NF-E (VICE-VERSA) ============ -->
        <div class="tab-pane fade" id="tab-por-nfe" role="tabpanel">
            <!-- Filtro de data das NF-E -->
            <div class="card mb-3 d-print-none">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3 col-5">
                            <label class="form-label mb-0 small" for="vinc-data-ini-nfe">NF-e de</label>
                            <input type="date" id="vinc-data-ini-nfe" class="form-control form-control-sm">
                        </div>
                        <div class="col-lg-3 col-5">
                            <label class="form-label mb-0 small" for="vinc-data-fim-nfe">Até</label>
                            <input type="date" id="vinc-data-fim-nfe" class="form-control form-control-sm">
                        </div>
                        <div class="col-lg-2 col-2">
                            <button id="vinc-btn-filtrar-nfe" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-lg-4 text-muted small">
                            Filtro pela data de emissão da NF-e (padrão: últimos 30 dias).
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-invoice me-2 text-muted"></i>NF-e sem vínculo</h5>
                            <span class="badge bg-warning text-dark" id="vinc-badge-nfes">0</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="vinc-loading-nfes" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                            <div id="vinc-lista-nfes" class="conc-lista"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-wallet me-2 text-muted"></i>Lançamentos candidatos</h5>
                            <span class="badge bg-primary" id="vinc-badge-selec-nfe">Nenhuma NF-e</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="vinc-loading-cand-lanc" class="text-center py-4 d-none">
                                <div class="spinner-border text-primary" role="status"></div>
                            </div>
                            <div id="vinc-lista-cand-lancamentos" class="conc-lista"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /tab-content -->

</div><!-- /container-fluid -->

<!-- ===================== MODAL VÍNCULO ===================== -->
<div class="modal fade" id="vinc-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vincular NF-e</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="vinc-modal-resumo" class="mb-3 small"></div>
                <label class="form-label" for="vinc-modal-obs">Observação / justificativa</label>
                <textarea id="vinc-modal-obs" class="form-control" rows="3"
                    placeholder="Ex.: valor inclui multa e juros por atraso; desconto à vista."></textarea>
                <div id="vinc-modal-erro" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="vinc-modal-confirmar" class="btn btn-success">
                    <i class="fas fa-link me-1"></i> Vincular
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/vinculacao.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/vinculacao.js') ?: '' ?>"></script>

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>