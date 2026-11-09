<?php
/* /public/components/dashboards/total_compras_mes.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = getDB();

    // Consulta para total de compras no mês atual
    $sqlMesAtual = "SELECT SUM(ValorTotal) AS total 
                    FROM NFe 
                    WHERE Tipo = 0 
                    AND DataEmissao BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())";
    $totalMesAtual = $pdo->query($sqlMesAtual)->fetchColumn() ?? 0;

    // Consulta para total de compras no mês anterior
    $sqlMesAnterior = "SELECT SUM(ValorTotal) AS total 
                       FROM NFe 
                       WHERE Tipo = 0 
                       AND DataEmissao BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') 
                       AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH))";
    $totalMesAnterior = $pdo->query($sqlMesAnterior)->fetchColumn() ?? 0;

    // Calcular variação percentual
    $variacao = $totalMesAnterior != 0 ?
        (($totalMesAtual - $totalMesAnterior) / $totalMesAnterior) * 100 : 0;
    $corVariacao = $variacao < 0 ? '#f94144' : '#43aa8b'; // Cores mais vibrantes
    $iconeVariacao = $variacao < 0 ? 'fa-arrow-down' : 'fa-arrow-up';
    $bgColor = $variacao < 0 ? 'rgba(249, 65, 68, 0.1)' : 'rgba(67, 170, 139, 0.1)';
} catch (Exception $e) {
    error_log("Erro ao carregar KPI de compras: " . $e->getMessage());
    $totalMesAtual = 0;
    $variacao = 0;
    $corVariacao = '#6c757d';
    $iconeVariacao = 'fa-question';
    $bgColor = 'rgba(108, 117, 125, 0.1)';
}
?>

<div class="kpi-card">
    <div class="kpi-icon <?= $variacao < 0 ? 'kpi-negative' : 'kpi-positive' ?>">
        <i class="fas fa-shopping-cart"></i>
    </div>

    <div class="kpi-title">Compras do Mês</div>

    <div class="kpi-value">
        R$
        <?= number_format($totalMesAtual, 2, ',', '.') ?>
    </div>

    <div class="kpi-footer">
        <span class="kpi-badge <?= $variacao < 0 ? 'kpi-negative' : 'kpi-positive' ?>">
            <i class="fas <?= $iconeVariacao ?>"></i>
            <?= number_format(abs($variacao), 2) ?>%
        </span>
        <span>vs mês anterior</span>
    </div>
</div>

