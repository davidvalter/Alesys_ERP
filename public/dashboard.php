<?php
/* /public/dashboard_v2.php */

$moduloAtual = 'dashboard';
$tituloPagina = 'Dashboard';
$tituloConteudo = 'Dashboard';

require __DIR__ . '/templates/base_protected_v2.php';
?>

<div class="dashboard-saas">

    <?php include __DIR__ . '/widgets/welcome.php'; ?>
    <?php include __DIR__ . '/widgets/status.php'; ?>
    <?php include __DIR__ . '/widgets/alerts.php'; ?>
    <?php include __DIR__ . '/widgets/shortcuts.php'; ?>
    <?php include __DIR__ . '/widgets/notices.php'; ?>

</div>

<?php require __DIR__ . '/templates/footer_protected_v2.php'; ?>