<?php
/* /public/financeiro/conciliacao.php */

session_start();

$moduloAtual = 'financeiro.conciliacao';
$tituloPagina = 'Conciliação Financeira';
$tituloConteudo = 'Conciliação Financeira - Lançamentos x NF-e';
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
            <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
        </div>
        <div>
            <a href="/financeiro/vinculacao.php" class="btn btn-success btn-sm">
                <i class="fas fa-link me-1"></i>
                Vinculação NF-e x Lançamentos
            </a>
        </div>
    </div>
</div>

<div class="container-fluid">

    <!-- ===================== RESUMO (PANORAMA GERAL - SOMENTE LEITURA) ===================== -->
    <div class="row g-3 mb-4 d-print-none" id="conc-kpis">
        <div class="col-xl-3 col-md-6">
            <div class="card widget-card h-100">
                <div class="widget-icon bg-success-soft"><i class="fas fa-wallet"></i></div>
                <div class="widget-content">
                    <h6 class="text-muted mb-1">Lançamentos já conciliados</h6>
                    <h4 id="conc-kpi-lancamentos">—</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-card h-100">
                <div class="widget-icon bg-primary-soft"><i class="fas fa-file-invoice"></i></div>
                <div class="widget-content">
                    <h6 class="text-muted mb-1">NF-e já conciliadas</h6>
                    <h4 id="conc-kpi-nfe">—</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-card h-100">
                <div class="widget-icon bg-info-soft"><i class="fas fa-calendar-check"></i></div>
                <div class="widget-content">
                    <h6 class="text-muted mb-1">Conciliado no mês</h6>
                    <h4 id="conc-kpi-conciliado">—</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card widget-card h-100">
                <div class="widget-icon bg-warning-soft"><i class="fas fa-link"></i></div>
                <div class="widget-content">
                    <h6 class="text-muted mb-1">Vínculos cadastrados</h6>
                    <h4 id="conc-kpi-vinculos">—</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== FILTROS ===================== -->
    <div class="card mb-4 d-print-none">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="conc-conta">Conta bancária</label>
                    <select id="conc-conta" class="form-select">
                        <option value="">Todas as contas</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="conc-data-ini">De</label>
                    <input type="date" id="conc-data-ini" class="form-control">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="conc-data-fim">Até</label>
                    <input type="date" id="conc-data-fim" class="form-control">
                </div>
                <div class="col-lg-3 col-md-6">
                    <button id="conc-btn-atualizar" class="btn btn-primary w-100">
                        <i class="fas fa-sync-alt me-1"></i> Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== DUAS COLUNAS ===================== -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-wallet me-2 text-muted"></i>Lançamentos financeiros</h5>
                    <span class="badge bg-warning text-dark" id="conc-badge-lancamentos">0 pendentes</span>
                </div>
                <div class="card-body p-0">
                    <div id="conc-loading-lancamento" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="conc-lista-lancamento" class="conc-lista"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2 text-muted"></i>NF-e a conciliar</h5>
                    <span class="badge bg-primary" id="conc-badge-nfe">0 notas</span>
                </div>
                <div class="card-body p-0">
                    <div id="conc-loading-nfe" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="conc-lista-nfe" class="conc-lista"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== BARRA DE CONCILIAÇÃO ===================== -->
    <div id="conc-barra" class="conc-barra d-none">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div>
                <div class="small text-muted">Lançamento selecionado</div>
                <strong id="conc-barra-lancamento">—</strong>
            </div>
            <div>
                <div class="small text-muted">Total das NF-e selecionadas</div>
                <strong id="conc-barra-total">R$ 0,00</strong>
            </div>
            <div id="conc-barra-diferenca"></div>
            <div class="ms-auto d-flex gap-2">
                <button id="conc-btn-limpar" class="btn btn-outline-secondary">Limpar</button>
                <button id="conc-btn-confirmar" class="btn btn-success" disabled>
                    <i class="fas fa-check me-1"></i> Confirmar conciliação
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== HISTÓRICO ===================== -->
    <div class="card mt-4 d-print-none">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2 text-muted"></i>Histórico de conciliações</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th class="text-center">Itens</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="conc-historico-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ===================== MODAL CONFIRMAÇÃO ===================== -->
<div class="modal fade" id="conc-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar conciliação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="conc-modal-resumo" class="mb-3"></div>
                <label class="form-label" for="conc-modal-obs">Observação / justificativa</label>
                <textarea id="conc-modal-obs" class="form-control" rows="3"
                    placeholder="Ex.: valor inclui multa e mora por atraso; pagamento agregado de 2 notas."></textarea>
                <div id="conc-modal-erro" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="conc-modal-confirmar" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL ESTORNO ===================== -->
<div class="modal fade" id="conc-modal-estorno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Estornar conciliação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>A conciliação será marcada como <strong>estornada</strong> e o histórico será preservado.</p>
                <label class="form-label" for="conc-estorno-motivo">Motivo do estorno</label>
                <textarea id="conc-estorno-motivo" class="form-control" rows="3"></textarea>
                <div id="conc-estorno-erro" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="conc-estorno-confirmar" class="btn btn-danger">Estornar</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/conciliacao.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/conciliacao.js') ?: '' ?>"></script>

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>