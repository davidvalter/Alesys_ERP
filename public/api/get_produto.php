<?php
// public/api/get_produto.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();

    if (!isset($_GET['Codigo'])) {
        throw new Exception('Código do produto não fornecido');
    }

    $stmt = $pdo->prepare("
        SELECT
            p.Codigo,
            p.Nome,
            p.Descricao,
            p.CodigoBarras,
            p.UnidadeMedida,
            p.Categoria,
            p.DataCadastro,
            p.TipoProduto,
            p.CategoriaVenda,
            p.NCM,
            p.CFOP,
            p.CSOSN,
            p.CEST,
            p.Ativo,
            n.Descricao AS NCM_Descricao,
            c.Descricao AS CEST_Descricao
        FROM Produtos p
        LEFT JOIN ProdutosNCM n ON n.Codigo = p.NCM
        LEFT JOIN ProdutosCEST c ON c.Codigo = p.CEST
        WHERE p.Codigo = ? AND p.Ativo = 1
    ");
    $stmt->execute([$_GET['Codigo']]);

    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        throw new Exception('Produto não encontrado');
    }

    echo json_encode($produto);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>