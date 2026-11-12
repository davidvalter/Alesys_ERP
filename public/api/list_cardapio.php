<?php
// public/api/list_cardapio.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    $sql = "SELECT
                c.Id,
                c.Nome,
                c.PrecoVenda,
                c.TipoItem,
                c.ProdutoId,
                c.PratoId,
                c.Categoria,
                c.Ativo,
                COALESCE(mp.CaminhoMiniatura, mpr.CaminhoMiniatura) AS Imagem,
                COALESCE(c.Nome, p.Nome, pr.Nome) AS NomeExibicao
            FROM Cardapio c
            LEFT JOIN Produtos p ON c.ProdutoId = p.Codigo
            LEFT JOIN Pratos pr ON c.PratoId = pr.Id
            LEFT JOIN Midias mp ON mp.Id = p.ImagemId
            LEFT JOIN Midias mpr ON mpr.Id = pr.ImagemId
            ORDER BY c.Categoria, c.Nome";
    $dados = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $dados], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao listar cardápio: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar cardápio.'
    ], JSON_UNESCAPED_UNICODE);
}
