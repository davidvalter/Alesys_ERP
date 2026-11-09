<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Validar parâmetros
    $required = ['produto', 'dataInicio', 'dataFim'];
    foreach ($required as $param) {
        if (!isset($_GET[$param])) {
            throw new Exception("Parâmetro '$param' não fornecido");
        }
    }
    
    $produtoId = (int)$_GET['produto'];
    $dataInicio = $_GET['dataInicio'];
    $dataFim = $_GET['dataFim'];
    $fornecedores = isset($_GET['fornecedores']) ? $_GET['fornecedores'] : [];
    
    // Validar datas
    if (!strtotime($dataInicio) || !strtotime($dataFim)) {
        throw new Exception("Datas inválidas");
    }
    
    // Obter informações do produto
    $sqlProduto = "SELECT Nome, UnidadeMedida FROM Produtos WHERE Codigo = ?";
    $stmt = $pdo->prepare($sqlProduto);
    $stmt->execute([$produtoId]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        throw new Exception("Produto não encontrado");
    }
    
    // Consulta principal
    $sql = "SELECT 
                m.Codigo AS fornecedor_codigo,
                m.Nome AS fornecedor_nome,
                AVG(ni.ValorUnitario) AS preco_medio,
                MIN(ni.ValorUnitario) AS preco_minimo,
                MAX(ni.ValorUnitario) AS preco_maximo,
                COUNT(DISTINCT n.Id) AS qtd_notas,
                SUM(ni.Quantidade) AS quantidade_total
            FROM NFeItens ni
            JOIN NFe n ON ni.fk_NFe_Id = n.Id
            JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
            WHERE ni.fk_Produtos_Codigo = ?
            AND n.DataEmissao BETWEEN ? AND ?
            AND n.Tipo = 0";
    
    $params = [$produtoId, $dataInicio, $dataFim];
    
    // Filtro por fornecedores específicos
    if (!empty($fornecedores)) {
        $placeholders = implode(',', array_fill(0, count($fornecedores), '?'));
        $sql .= " AND m.Codigo IN ($placeholders)";
        $params = array_merge($params, $fornecedores);
    }
    
    $sql .= " GROUP BY m.Codigo, m.Nome
              ORDER BY preco_medio ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'produto' => $produto,
        'periodo' => [
            'inicio' => $dataInicio,
            'fim' => $dataFim
        ],
        'data' => $resultados
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}