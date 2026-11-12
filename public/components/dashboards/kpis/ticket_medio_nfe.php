<?php
/* Ticket médio das notas de compra ativas nos últimos três meses. */
require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $sql = "
        SELECT COALESCE(AVG(ValorTotal), 0)
        FROM NFe
        WHERE Status = 1
          AND DataEmissao >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
          AND DataEmissao < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ";
    $ticketMedio = (float)$pdo->query($sql)->fetchColumn();
} catch (Throwable $e) {
    error_log('Erro ao carregar KPI de ticket médio: ' . $e->getMessage());
    $ticketMedio = 0;
}
?>

<div class="kpi-card">
    <div class="kpi-icon text-warning">
        <i class="fas fa-receipt"></i>
    </div>
    <div class="kpi-title">Ticket Médio</div>
    <div class="kpi-value">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></div>
    <div class="kpi-footer">Notas de compra ativas · últimos 3 meses</div>
</div>
