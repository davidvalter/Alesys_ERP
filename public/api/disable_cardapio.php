<?php
/* /public/api/disable_cardapio.php */

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['Id'] ?? null;
    $ativo = $input['Ativo'] ?? null;

    if (!$id) {
        throw new Exception('ID não informado');
    }

    if (!in_array($ativo, [0,1], true)) {
        throw new Exception('Valor de Ativo inválido');
    }

    $stmt = $db->prepare("
        UPDATE Cardapio 
        SET Ativo = :ativo 
        WHERE Id = :id
    ");

    $stmt->execute([
        ':id' => $id,
        ':ativo' => $ativo
    ]);

    echo json_encode([
        'success' => true,
        'message' => $ativo ? 'Item ativado' : 'Item desativado'
    ]);

} catch (Exception $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}