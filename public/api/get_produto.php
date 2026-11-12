<?php
// public/api/get_produto.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    if (!isset($_GET['Codigo'])) {
        throw new InvalidArgumentException('Código do produto não fornecido.');
    }

    $pdo = getDB();
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
            m.CaminhoMiniatura AS Imagem,
            n.Descricao AS NCM_Descricao,
            c.Descricao AS CEST_Descricao
        FROM Produtos p
        LEFT JOIN Midias m ON m.Id = p.ImagemId
        LEFT JOIN ProdutosNCM n ON n.Codigo = p.NCM
        LEFT JOIN ProdutosCEST c ON c.Codigo = p.CEST
        WHERE p.Codigo = ?
    ");
    $stmt->execute([(int)$_GET['Codigo']]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        throw new RuntimeException('Produto não encontrado.');
    }

    echo json_encode($produto, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
