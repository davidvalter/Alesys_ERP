<?php
// /public/api/delete_mercado.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();
    
    if (empty($input['Codigo'])) {
        throw new Exception('Código do mercado é obrigatório');
    }
    
    // Verifica se existem notas fiscais vinculadas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM NFe WHERE fk_Mercados_Codigo = ?");
    $stmt->execute([$input['Codigo']]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        throw new Exception('Não é possível excluir: existem notas fiscais vinculadas a este mercado');
    }
    
    // Inicia transação para segurança
    $pdo->beginTransaction();
    
    // Primeiro remove os preços vinculados
    $pdo->prepare("DELETE FROM ProdutoPrecos WHERE fk_Mercados_Codigo = ?")
        ->execute([$input['Codigo']]);
    
    // Depois remove o mercado
    $stmt = $pdo->prepare("DELETE FROM Mercados WHERE Codigo = ?");
    $success = $stmt->execute([$input['Codigo']]);
    
    if ($success && $stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Mercado excluído com sucesso!'
        ]);
    } else {
        $pdo->rollBack();
        throw new Exception('Mercado não encontrado ou já excluído');
    }
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('PDOException ao excluir mercado: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir mercado',
        'error_details' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Exception ao excluir mercado: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}