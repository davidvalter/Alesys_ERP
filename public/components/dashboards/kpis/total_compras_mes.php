<?php
/* Indicador de compras emitidas no mês atual. NFe contém notas de compra; Tipo é mantido como dado fiscal do emitente. */
require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sqlMesAtual = "
        SELECT COALESCE(SUM(ValorTotal), 0)
        FROM NFe
        WHERE Status = 1
          AND DataEmissao >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND DataEmissao < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
    ";
    $totalMesAtual = (float)$pdo->query($sqlMesAtual)->fetchColumn();

    $sqlMesAnterior = "
        SELECT COALESCE(SUM(ValorTotal), 0)
        FROM NFe
        WHERE Status = 1
          AND DataEmissao >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
          AND DataEmissao < DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ";
    $totalMesAnterior = (float)$pdo->query($sqlMesAnterior)->fetchColumn();

    $temComparacao = $totalMesAnterior > 0;
    $variacao = $temComparacao
        ? (($totalMesAtual - $totalMesAnterior) / $totalMesAnterior) * 100
        : 0;
    $corVariacao = !$temComparacao ? '#6c757d' : ($variacao < 0 ? '#f94144' : '#43aa8b');
    $iconeVariacao = !$temComparacao ? 'fa-minus' : ($variacao < 0 ? 'fa-arrow-down' : 'fa-arrow-up');
} catch (Throwable $e) {
    error_log('Erro ao carregar KPI de compras: ' . $e->getMessage());
    $totalMesAtual = 0;
    $variacao = 0;
    $temComparacao = false;
    $corVariacao = '#6c757d';
    $iconeVariacao = 'fa-question';
}
?>

<div class="kpi-card">
    <div class="kpi-icon <?= $variacao < 0 ? 'kpi-negative' : 'kpi-positive' ?>">
        <i class="fas fa-shopping-cart"></i>
    </div>
    <div class="kpi-title">Compras do Mês</div>
    <div class="kpi-value">R$ <?= number_format($totalMesAtual, 2, ',', '.') ?></div>
    <div class="kpi-footer">
        <?php if ($temComparacao): ?>
            <span class="kpi-badge <?= $variacao < 0 ? 'kpi-negative' : 'kpi-positive' ?>">
                <i class="fas <?= $iconeVariacao ?>"></i>
                <?= number_format(abs($variacao), 2, ',', '.') ?>%
            </span>
            <span>vs mês anterior</span>
        <?php else: ?>
            <span class="text-muted">Sem compras no mês anterior para comparar</span>
        <?php endif; ?>
    </div>
</div>
