<?php
// public/api/list_cardapio.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    $sql = "
        SELECT 
            c.Id,
            c.Nome,
            c.PrecoVenda,
            c.TipoItem,
            c.Categoria,
            c.Imagem,

            -- Nome fallback (caso queira usar padrão do produto/prato)
            COALESCE(c.Nome, p.Nome, pr.Nome) as NomeExibicao

        FROM Cardapio c
        LEFT JOIN Produtos p ON c.ProdutoId = p.Codigo
        LEFT JOIN Pratos pr ON c.PratoId = pr.Id

        WHERE c.Ativo = 1
        ORDER BY c.Categoria, c.Nome
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    $dados = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $dados
    ]);

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar cardápio',
        'error' => $e->getMessage() // pode remover em produção
    ]);
}