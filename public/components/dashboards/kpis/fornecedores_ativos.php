<?php
/* /public/components/dashboards/fornecedores_ativos.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();

    $sql = "SELECT COUNT(*) AS total FROM Mercados WHERE Ativo = 1";
    $totalFornecedores = $pdo->query($sql)->fetchColumn() ?? 0;

    $cor = '#577590';
    $bgColor = 'rgba(87, 117, 144, 0.1)';
} catch (Exception $e) {
    error_log("Erro ao carregar KPI de fornecedores: " . $e->getMessage());
    $totalFornecedores = 0;
    $cor = '#6c757d';
    $bgColor = 'rgba(108, 117, 125, 0.1)';
}
?>

<div class="kpi-card">
    <div class="kpi-icon text-primary">
        <i class="fas fa-truck"></i>
    </div>

    <div class="kpi-title">Fornecedores Ativos</div>

    <div class="kpi-value">
        <?= number_format($totalFornecedores, 0, ',', '.') ?>
    </div>

    <div class="kpi-footer">
        Atualizado automaticamente
    </div>
</div>
