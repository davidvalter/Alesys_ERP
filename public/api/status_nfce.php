<?php
/* /public/api/status_nfce.php — polling leve a cada 2s. Só lê o banco.
 * Endpoint interno: não exige sessão (a página do PDV já é autenticada). */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json');

try {
    $deps = [
        __DIR__ . '/../../configs/database.php',
        __DIR__ . '/nfce_helpers.php',
    ];
    foreach ($deps as $arquivo) {
        if (!is_file($arquivo)) throw new RuntimeException("Arquivo não encontrado: {$arquivo}");
        require_once $arquivo;
    }

    $db = getDB();

    $pedidoId = (int) ($_GET['pedido_id'] ?? 0);
    if ($pedidoId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'pedido_id obrigatório']);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM VendasNFCE WHERE PedidoId = ? ORDER BY Id DESC LIMIT 1");
    $stmt->execute([$pedidoId]);
    $nfce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nfce) {
        echo json_encode(['success' => true, 'status' => 'PENDENTE', 'dados' => null, 'motivoRejeicao' => null]);
        exit;
    }

    echo json_encode([
        'success'        => true,
        'status'         => $nfce['Status'],
        'nfceId'         => $nfce['NfceId'],
        'dados'          => json_decode($nfce['DadosImpressao'] ?? 'null', true),
        'motivoRejeicao' => $nfce['MotivoRejeicao'],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}