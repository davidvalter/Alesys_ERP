<?php
/* /public/components/dashboards/ticket_medio_nfe.php */

require_once __DIR__ . '/../../../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();

    // Consulta para ticket médio
    $sql = "SELECT AVG(ValorTotal) AS ticket_medio 
            FROM NFe 
            WHERE Tipo = 0 
            AND DataEmissao BETWEEN DATE_SUB(NOW(), INTERVAL 3 MONTH) AND NOW()";
    $ticketMedio = $pdo->query($sql)->fetchColumn() ?? 0;

    $cor = '#f9c74f'; // Amarelo vibrante
    $bgColor = 'rgba(249, 199, 79, 0.1)';
} catch (Exception $e) {
    error_log("Erro ao carregar KPI de ticket médio: " . $e->getMessage());
    $ticketMedio = 0;
    $cor = '#6c757d';
    $bgColor = 'rgba(108, 117, 125, 0.1)';
}
?>

<div class="kpi-card">
    <div class="kpi-icon text-warning">
        <i class="fas fa-receipt"></i>
    </div>

    <div class="kpi-title">Ticket Médio</div>

    <div class="kpi-value">
        R$ <?= number_format($ticketMedio, 2, ',', '.') ?>
    </div>

    <div class="kpi-footer">
        Últimos 3 meses
    </div>
</div>

