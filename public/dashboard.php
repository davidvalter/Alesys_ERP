<?php
/* Dashboard inicial do Alesys ERP. O template comum monta o head, navegação e rodapé. */

$moduloAtual = 'dashboard';
$tituloPagina = 'Visão geral';
$tituloConteudo = 'Visão geral';
$extraCss = '<link rel="stylesheet" href="/styles/dashboard_v2.css">';

require __DIR__ . '/templates/base_protected_v2.php';
?>

<div class="dashboard-saas">
    <div class="dashboard-page-heading">
        <div>
            <?php include __DIR__ . '/widgets/welcome.php'; ?>
        </div>
        <div class="dashboard-controls">
            <label class="visually-hidden" for="dashboard-period">Período dos indicadores</label>
            <select class="form-select dashboard-period-select" id="dashboard-period">
                <option value="hoje">Hoje</option>
                <option value="7d" selected>Últimos 7 dias</option>
                <option value="mes">Este mês</option>
            </select>
            <button type="button" class="dashboard-refresh" id="dashboard-refresh" aria-label="Atualizar dados" title="Atualizar dados">
                <i class="fas fa-rotate" aria-hidden="true"></i>
            </button>
            <span class="dashboard-updated" id="dashboard-updated" aria-live="polite"></span>
        </div>
    </div>

    <div class="dashboard-feedback" id="dashboard-feedback" role="alert" hidden></div>

    <?php include __DIR__ . '/widgets/status.php'; ?>

    <div class="dashboard-main-grid">
        <?php include __DIR__ . '/widgets/sales_chart.php'; ?>
        <?php include __DIR__ . '/widgets/alerts.php'; ?>
    </div>

    <div class="dashboard-lower-grid">
        <?php include __DIR__ . '/widgets/recent_orders.php'; ?>
        <?php include __DIR__ . '/widgets/shortcuts.php'; ?>
    </div>
</div>

<script src="/js/libs/chart.js"></script>
<script src="/js/dashboard.js"></script>

<?php require __DIR__ . '/templates/footer_protected_v2.php'; ?>
