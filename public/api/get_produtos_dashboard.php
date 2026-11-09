<?php
// public/api/get_produtos_dashboard.php

header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Consulta otimizada para o dashboard (apenas campos necessários)
    $sql = "SELECT Codigo, Nome FROM Produtos WHERE Ativo = 1 ORDER BY Nome";
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
        'message' => 'Erro ao carregar produtos para dashboard: ' . $e->getMessage()
    ]);
}