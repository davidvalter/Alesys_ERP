<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();
    
    if (empty($input['Codigo'])) {
        throw new Exception('Código do produto é obrigatório');
    }
    
    // Inicia transação
    $pdo->beginTransaction();
    
    try {
        // 1. Remove registros vinculados em ProdutoPrecos
        $pdo->prepare("DELETE FROM ProdutoPrecos WHERE fk_Produtos_Codigo = ?")
            ->execute([$input['Codigo']]);
        
        // 2. Remove registros vinculados em NFeItens
        $pdo->prepare("DELETE FROM NFeItens WHERE fk_Produtos_Codigo = ?")
            ->execute([$input['Codigo']]);
        
        // 3. Remove o produto
        $stmt = $pdo->prepare("DELETE FROM Produtos WHERE Codigo = ?");
        $stmt->execute([$input['Codigo']]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Produto excluído com sucesso!'
            ]);
        } else {
            $pdo->rollBack();
            throw new Exception('Produto não encontrado');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('PDOException ao excluir produto: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados',
        'error_details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Exception ao excluir produto: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>