<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    if (!isset($_GET['product_id'])) {
        throw new Exception('ID do produto não fornecido');
    }
    
    $productId = (int)$_GET['product_id'];
    
    // Obter nome do produto
    $sqlProduto = "SELECT Nome FROM Produtos WHERE Codigo = ?";
    $stmt = $pdo->prepare($sqlProduto);
    $stmt->execute([$productId]);
    $productName = $stmt->fetchColumn() ?: "Produto #$productId";
    
    // Obter histórico de preços
    $sql = "SELECT 
                DATE_FORMAT(n.DataEmissao, '%Y-%m') AS mes,
                AVG(ni.ValorUnitario) AS preco_medio
            FROM NFeItens ni
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            WHERE ni.fk_Produtos_Codigo = ?
            AND n.Tipo = 0
            AND n.DataEmissao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY mes
            ORDER BY mes";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatando os dados para o gráfico
    $labels = [];
    $values = [];
    
    foreach ($historico as $row) {
        $labels[] = date('M/Y', strtotime($row['mes']));
        $values[] = (float)$row['preco_medio'];
    }
    
    echo json_encode([
        'success' => true,
        'productName' => $productName,
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}