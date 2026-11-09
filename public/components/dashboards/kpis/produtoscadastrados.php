<?php
/* /public/components/dashboards/produtoscadastrados.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();

    $sql = "SELECT COUNT(*) AS total FROM Produtos";
    $totalProdutos = $pdo->query($sql)->fetchColumn() ?? 0;

    $cor = '#577590';
    $bgColor = 'rgba(87, 117, 144, 0.1)';
} catch (Exception $e) {
    error_log("Erro ao carregar KPI de Produtos: " . $e->getMessage());
    $totalProdutos = 0;
    $cor = '#6c757d';
    $bgColor = 'rgba(108, 117, 125, 0.1)';
}
?>

<div class="kpi-card">
    <div class="kpi-icon text-success">
        <i class="fas fa-box-open"></i>
    </div>

    <div class="kpi-title">Produtos</div>

    <div class="kpi-value">
        <?= number_format($totalProdutos, 0, ',', '.') ?>
    </div>

    <div class="kpi-footer">
        Itens cadastrados
    </div>
</div>

