<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
    if (!$productId || $productId < 1) {
        throw new InvalidArgumentException('ID do produto inválido.');
    }

    $pdo = DatabaseConfig::getConnection();
    $stmtProduto = $pdo->prepare("SELECT Nome FROM Produtos WHERE Codigo = ?");
    $stmtProduto->execute([$productId]);
    $productName = $stmtProduto->fetchColumn();
    if ($productName === false) {
        throw new RuntimeException('Produto não encontrado.');
    }

    $sql = "SELECT DATE_FORMAT(n.DataEmissao, '%Y-%m') AS mes,
                   AVG(ni.ValorUnitario) AS preco_medio
            FROM NFeItens ni
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            WHERE ni.fk_Produtos_Codigo = ?
              AND n.Status = 1
              AND n.DataEmissao >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND n.DataEmissao < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            GROUP BY mes
            ORDER BY mes";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($historico as $row) {
        $labels[] = date('M/Y', strtotime($row['mes'] . '-01'));
        $values[] = (float)$row['preco_medio'];
    }

    echo json_encode([
        'success' => true,
        'productName' => $productName,
        'labels' => $labels,
        'values' => $values
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Erro ao carregar histórico de preços: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível carregar o histórico de preços.']);
}
