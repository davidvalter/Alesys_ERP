<?php
/* /public/api/delete_cardapio.php */

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['Id'] ?? null;

    if (!$id) {
        throw new Exception('ID não informado');
    }

    // Verifica existência
    $stmt = $db->prepare("SELECT Id FROM Cardapio WHERE Id = :id");
    $stmt->execute([':id' => $id]);

    if (!$stmt->fetch()) {
        throw new Exception('Item não encontrado');
    }

    // DELETE
    $stmt = $db->prepare("DELETE FROM Cardapio WHERE Id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Item removido com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}