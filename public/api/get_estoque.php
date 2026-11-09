<?php
// public/api/get_estoque.php
require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    $db = getDB();

    $sql = "
        SELECT 
            p.Codigo,
            p.Nome,
            p.Categoria,
            p.UnidadeMedida,
            IFNULL(SUM(
                CASE 
                    WHEN m.Tipo = 'entrada' THEN m.Quantidade
                    WHEN m.Tipo = 'saida'   THEN -m.Quantidade
                    WHEN m.Tipo = 'ajuste'  THEN m.Quantidade
                END
            ), 0) AS EstoqueAtual,
            (
                SELECT 
                    ROUND(
                        SUM(ni.Quantidade * ni.ValorUnitario) / NULLIF(SUM(ni.Quantidade),0),
                        2
                    )
                FROM NFeItens ni
                JOIN NFe n ON ni.fk_NFe_Id = n.Id
                WHERE ni.fk_Produtos_Codigo = p.Codigo
                AND n.DataCadastro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ) AS PrecoMedio,
            MAX(m.DataMovimento) AS UltimaMovimentacao
        FROM Produtos p
        LEFT JOIN MovimentacoesEstoque m ON p.Codigo = m.ProdutoId
        WHERE p.Ativo = 1
        GROUP BY p.Codigo, p.Nome, p.Categoria, p.UnidadeMedida
        ORDER BY p.Nome;
    ";


    $stmt = $db->query($sql);
    $estoque = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $estoque]);
    exit;

} catch (Exception $e) {
    logError("Erro ao carregar estoque: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno ao carregar estoque.']);
    exit;
}
