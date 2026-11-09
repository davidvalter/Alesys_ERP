<?php
/* /public/relatorios/dre.php */

session_start();

$moduloAtual = 'financeiro.relatorios';
$tituloPagina = 'DRE - Demonstração do Resultado do Exercício';
$tituloConteudo = 'DRE - Demonstração do Resultado do Exercício';
$extraCss = '<link rel="stylesheet" href="/styles/dre.css">';

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';
include __DIR__ . '/../templates/base_protected_v2.php';

// =========================
// Verifica permissão
// =========================
verificarPermissao($moduloAtual);

// =========================
// Valida usuário
// =========================
$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}
?>

<div class="container-fluid">

    <!-- ===================== AREA DE IMPRESSAO ===================== -->
    <div id="dre-print-area">

        <!-- Cabeçalho executivo: apenas na impressão -->
        <div class="d-none d-print-block dre-print-header">
            <div class="d-flex justify-content-between align-items-end dre-print-cabecalho-linha">
                <div>
                    <h2 class="mb-0">Demonstração do Resultado do Exercício</h2>
                    <div class="dre-print-subtitulo">Alesys ERP · Relatório Gerencial</div>
                </div>
                <div class="text-end">
                    <div id="dre-print-rotulo" class="dre-print-periodo"></div>
                    <div class="dre-print-subtitulo">Emitido em <?= date('d/m/Y H:i') ?></div>
                </div>
            </div>
            <div id="dre-print-filtros" class="dre-print-filtros"></div>
        </div>

        <!-- ===================== FILTROS ===================== -->
        <div class="card mb-4 d-print-none">
            <div class="card-body">
                <div class="row g-3 align-items-end">

                    <div class="col-lg-2 col-md-3">
                        <label class="form-label" for="dre-modo">Visão</label>
                        <select id="dre-modo" class="form-select">
                            <option value="mensal" selected>Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3" id="dre-grupo-mes">
                        <label class="form-label" for="dre-mes">Mês</label>
                        <input type="month" id="dre-mes" class="form-control">
                    </div>

                    <div class="col-lg-2 col-md-3 d-none" id="dre-grupo-ano">
                        <label class="form-label" for="dre-ano">Ano</label>
                        <select id="dre-ano" class="form-select"></select>
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <label class="form-label" for="dre-conta">Conta</label>
                        <select id="dre-conta" class="form-select">
                            <option value="">Todas as contas</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <label class="form-label" for="dre-status">Status</label>
                        <select id="dre-status" class="form-select">
                            <option value="todos" selected>Todos (realizado + previsto)</option>
                            <option value="pagos">Somente pagos (caixa)</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-4 d-flex gap-2">
                        <button id="dre-btn-atualizar" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-sync-alt me-1"></i> Atualizar
                        </button>
                        <button id="dre-btn-imprimir" class="btn btn-outline-secondary" title="Imprimir / PDF">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>

                </div>
                <div class="text-muted small mt-2">
                    Competência apurada pela data de baixa; sem baixa, usa a data prevista; sem previsão, a data de
                    lançamento.
                </div>
            </div>
        </div>

        <!-- ===================== KPIs (tela, não imprime) ===================== -->
        <div class="row g-4 mb-4 d-print-none" id="dre-kpis">
            <div class="col-xl-4 col-md-4">
                <div class="card widget-card h-100">
                    <div class="widget-icon bg-primary-soft"><i class="fas fa-chart-line"></i></div>
                    <div class="widget-content">
                        <h6 class="text-muted mb-1">Receita Bruta</h6>
                        <h4 id="dre-kpi-receita">—</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4">
                <div class="card widget-card h-100">
                    <div class="widget-icon bg-warning-soft"><i class="fas fa-layer-group"></i></div>
                    <div class="widget-content">
                        <h6 class="text-muted mb-1">Lucro Bruto</h6>
                        <h4 id="dre-kpi-lucro">—</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-4">
                <div class="card widget-card h-100">
                    <div class="widget-icon bg-success-soft"><i class="fas fa-sack-dollar"></i></div>
                    <div class="widget-content">
                        <h6 class="text-muted mb-1">Resultado Líquido</h6>
                        <h4 id="dre-kpi-resultado">—</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== ALERTA DE CLASSIFICAÇÃO ===================== -->
        <div id="dre-alerta-sem-classe" class="alert alert-warning d-none" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Lançamentos sem classificação na DRE:</strong>
            <span id="dre-alerta-total"></span>
            <div id="dre-alerta-categorias" class="mt-2 small"></div>
        </div>

        <!-- ===================== TABELA DRE ===================== -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center d-print-none">
                <h5 class="mb-0">Demonstração do Resultado</h5>
                <span id="dre-periodo-rotulo" class="badge bg-primary"></span>
            </div>
            <div class="card-body">
                <div id="dre-loading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle dre-tabela" id="dre-tabela">
                        <thead id="dre-thead"></thead>
                        <tbody id="dre-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rodapé executivo: apenas na impressão -->
        <div class="d-none d-print-block dre-print-footer">
            <div class="d-flex justify-content-between">
                <div>Emitido em <?= date('d/m/Y') ?> via Alesys ERP</div>
                <div class="text-center">
                    ____________________________________<br>
                    Direção / Diretoria Financeira
                </div>
            </div>
        </div>

    </div>

</div>

<script src="/js/dre.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/dre.js') ?: '' ?>"></script>

<?php require __DIR__ . '/../templates/footer_protected_v2.php'; ?>