<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $produtoIds = $_GET['produto'] ?? [];
    if (!is_array($produtoIds)) {
        $produtoIds = [$produtoIds];
    }
    $produtoIds = array_values(array_unique(array_filter(
        array_map('intval', $produtoIds),
        static fn($id) => $id > 0
    )));
    if (!$produtoIds) {
        throw new InvalidArgumentException('Selecione pelo menos um produto.');
    }
    if (count($produtoIds) > 20) {
        throw new InvalidArgumentException('Selecione no máximo 20 produtos por comparação.');
    }

    $dataInicio = $_GET['dataInicio'] ?? '';
    $dataFim = $_GET['dataFim'] ?? '';
    $inicio = DateTime::createFromFormat('!Y-m-d', $dataInicio);
    $fim = DateTime::createFromFormat('!Y-m-d', $dataFim);
    if (!$inicio || $inicio->format('Y-m-d') !== $dataInicio || !$fim || $fim->format('Y-m-d') !== $dataFim) {
        throw new InvalidArgumentException('Informe um período válido.');
    }
    if ($fim < $inicio) {
        throw new InvalidArgumentException('A data final deve ser igual ou posterior à data inicial.');
    }

    $fornecedores = $_GET['fornecedores'] ?? [];
    if (!is_array($fornecedores)) {
        $fornecedores = [$fornecedores];
    }
    $fornecedores = array_values(array_unique(array_filter(
        array_map('intval', $fornecedores),
        static fn($id) => $id > 0
    )));

    $pdo = DatabaseConfig::getConnection();
    $placeholdersProdutos = implode(',', array_fill(0, count($produtoIds), '?'));
    $sql = "SELECT Codigo, Nome, UnidadeMedida
            FROM Produtos
            WHERE Ativo = 1 AND Codigo IN ($placeholdersProdutos)
            ORDER BY Nome";
    $stmtProdutos = $pdo->prepare($sql);
    $stmtProdutos->execute($produtoIds);
    $produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);
    if (!$produtos) {
        throw new InvalidArgumentException('Nenhum dos produtos selecionados está ativo ou disponível.');
    }

    $sql = "SELECT
                p.Codigo AS produto_codigo,
                p.Nome AS produto_nome,
                p.UnidadeMedida AS unidade_medida,
                m.Codigo AS fornecedor_codigo,
                m.Nome AS fornecedor_nome,
                AVG(ni.ValorUnitario) AS preco_medio,
                MIN(ni.ValorUnitario) AS preco_minimo,
                MAX(ni.ValorUnitario) AS preco_maximo,
                COUNT(DISTINCT n.Id) AS qtd_notas,
                COALESCE(SUM(ni.Quantidade), 0) AS quantidade_total
            FROM NFeItens ni
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            JOIN Produtos p ON ni.fk_Produtos_Codigo = p.Codigo
            JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
            WHERE ni.fk_Produtos_Codigo IN ($placeholdersProdutos)
              AND n.Status = 1
              AND n.DataEmissao >= ?
              AND n.DataEmissao < DATE_ADD(?, INTERVAL 1 DAY)";

    $params = array_merge($produtoIds, [$dataInicio, $dataFim]);
    if ($fornecedores) {
        $placeholdersFornecedores = implode(',', array_fill(0, count($fornecedores), '?'));
        $sql .= " AND m.Codigo IN ($placeholdersFornecedores)";
        $params = array_merge($params, $fornecedores);
    }
    $sql .= " GROUP BY p.Codigo, p.Nome, p.UnidadeMedida, m.Codigo, m.Nome
              ORDER BY p.Nome, preco_medio ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'produtos' => $produtos,
        'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim],
        'data' => $resultados
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro na comparação de preços: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível gerar a comparação de preços.'], JSON_UNESCAPED_UNICODE);
}
