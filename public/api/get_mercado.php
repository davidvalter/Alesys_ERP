<?php
// /public/api/get_mercado.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    
    if (!isset($_GET['Codigo'])) {
        throw new Exception('Código do mercado não fornecido');
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            Codigo, Nome, RazaoSocial, CNPJ, InscricaoEstadual, 
            Email, Endereco, CEP, Municipio, Estado, Pais, 
            MatrizFilial, Telefone
        FROM Mercados 
        WHERE Codigo = ? AND Ativo = 1
    ");
    $stmt->execute([$_GET['Codigo']]);
    $mercado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mercado) {
        throw new Exception('Mercado não encontrado');
    }
    
    echo json_encode($mercado);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>