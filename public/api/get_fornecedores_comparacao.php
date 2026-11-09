<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    $sql = "SELECT Codigo, Nome 
            FROM Mercados 
            WHERE Ativo = 1
            ORDER BY Nome";
    
    $stmt = $pdo->query($sql);
    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $fornecedores
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar fornecedores: ' . $e->getMessage()
    ]);
}