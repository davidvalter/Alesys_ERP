<?php
// public/api/get_nfenumero_mobile.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    if (!isset($_GET['numero']) || trim($_GET['numero']) === '') {
        echo json_encode(['success' => false, 'message' => 'Parâmetro numero é obrigatório.']);
        exit;
    }

    $numero = trim($_GET['numero']);

    $db = getDB();

    $sql = "SELECT Id, Numero, Serie, Tipo, DATE_FORMAT(DataEmissao, '%Y-%m-%d %H:%i:%s') AS DataEmissao, DataRecebimento, ValorTotal, Status
            FROM NFe
            WHERE Numero = :numero
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':numero', $numero);
    $stmt->execute();
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota) {
        echo json_encode(['success' => false, 'message' => 'Nota não encontrada.']);
        exit;
    }

    echo json_encode(['success' => true, 'nota' => $nota]);

} catch (Exception $e) {
    error_log('[get_nfenumero_mobile] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao consultar nota.', 'error' => $e->getMessage()]);
}
