<?php
/* /public/admin/usuarios/toogle-status.php */

require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../conf/urls_redirect.php';
require_once __DIR__ . '/../../conf/autenticacao.php';
verificarPermissao('admin.usuarios');

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$userId || !is_numeric($userId)) {
        throw new Exception('ID de usuário inválido');
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE Usuarios SET Ativo = ? WHERE Id = ?");
    $stmt->execute([$status, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}