<?php
// public/api/list_pdv.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    $sql = "SELECT
                c.Id,
                c.Nome,
                c.TipoItem,
                c.Categoria,
                c.PrecoVenda,
                COALESCE(mp.CaminhoMiniatura, mpr.CaminhoMiniatura) AS Imagem
            FROM Cardapio c
            LEFT JOIN Produtos p
                ON c.TipoItem = 'produto' AND c.ProdutoId = p.Codigo
            LEFT JOIN Pratos pr
                ON c.TipoItem = 'prato' AND c.PratoId = pr.Id
            LEFT JOIN Midias mp ON mp.Id = p.ImagemId
            LEFT JOIN Midias mpr ON mpr.Id = pr.ImagemId
            WHERE c.Ativo = 1
              AND (
                    (c.TipoItem = 'produto' AND p.Ativo = 1 AND p.TipoProduto = 'vendavel')
                 OR (c.TipoItem = 'prato' AND pr.Status = 1)
              )
            ORDER BY c.Categoria, c.Nome";
    $data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao listar itens do PDV: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível carregar os itens do PDV.'
    ], JSON_UNESCAPED_UNICODE);
}
