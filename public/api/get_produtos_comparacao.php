<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    $sql = "SELECT Codigo, Nome, UnidadeMedida 
            FROM Produtos 
            WHERE Ativo = 1
            ORDER BY Nome";
    
    $stmt = $pdo->query($sql);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $produtos
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar produtos: ' . $e->getMessage()
    ]);
}