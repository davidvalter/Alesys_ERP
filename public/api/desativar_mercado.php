<?php
// public/api/desativar_mercado.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();
    
    if (empty($input['Codigo'])) {
        throw new Exception('Código do mercado é obrigatório');
    }
    
    $stmt = $pdo->prepare("UPDATE Mercados SET Ativo = 0 WHERE Codigo = ?");
    $success = $stmt->execute([$input['Codigo']]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Mercado desativado com sucesso!'
        ]);
    } else {
        throw new Exception('Mercado não encontrado ou já desativado');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>