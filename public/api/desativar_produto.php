<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();
    
    if (empty($input['Codigo'])) {
        throw new Exception('Código do produto é obrigatório');
    }
    
    // Verifica se existem registros vinculados em NFeItens
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM NFeItens WHERE fk_Produtos_Codigo = ?");
    $stmt->execute([$input['Codigo']]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        throw new Exception('Não é possível desativar: existem notas fiscais vinculadas a este produto');
    }
    
    $stmt = $pdo->prepare("UPDATE Produtos SET Ativo = 0 WHERE Codigo = ?");
    $success = $stmt->execute([$input['Codigo']]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Produto desativado com sucesso!'
        ]);
    } else {
        throw new Exception('Produto não encontrado ou já desativado');
    }
    
} catch (PDOException $e) {
    error_log('PDOException ao desativar produto: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados',
        'error_details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Exception ao desativar produto: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>