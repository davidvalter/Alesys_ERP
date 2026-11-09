<?php
// public/api/get_nota_fiscal.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

// Ativar logs detalhados
file_put_contents('../logs/nfe_debug.log', date('Y-m-d H:i:s') . " - Iniciando consulta\n", FILE_APPEND);

try {
    $pdo = getDB();

    // Validação mais robusta do ID
    if (!isset($_GET['Id'])) {
        throw new Exception('ID da nota fiscal não fornecido');
    }

    $notaId = (int) $_GET['Id'];
    if ($notaId <= 0) {
        throw new Exception('ID da nota fiscal inválido');
    }

    file_put_contents('../logs/nfe_debug.log', "Consultando nota ID: $notaId\n", FILE_APPEND);

    // Consulta a nota fiscal com tratamento de datas
    $stmtNota = $pdo->prepare("
        SELECT 
            n.Id,
            n.Numero,
            n.Serie,
            n.Tipo,
            DATE_FORMAT(n.DataEmissao, '%Y-%m-%d') AS DataEmissao,
            DATE_FORMAT(n.DataRecebimento, '%Y-%m-%d') AS DataRecebimento,
            n.NaturezaOperacao,
            n.ChaveAcesso,
            n.ValorTotal,
            n.Desconto,
            n.Status,
            n.Observacoes,
            n.pdf_path AS pdf_path,
            n.fk_Mercados_Codigo,
            m.Nome AS MercadoNome
        FROM NFe n
        JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
        WHERE n.Id = ?
    ");

    $stmtNota->execute([$notaId]);
    $nota = $stmtNota->fetch(PDO::FETCH_ASSOC);

    // Após buscar a nota:
    $nota['Desconto'] = (float) ($nota['Desconto'] ?? 0);

    // Verifica se já existe lançamento financeiro vinculado a esta nota
    $stmtLanc = $pdo->prepare("
    SELECT Id 
    FROM FinanceiroLancamentos 
    WHERE Origem = 'compra' AND OrigemId = ?
    LIMIT 1
    ");
    
    $stmtLanc->execute([$notaId]);
    $lancamento = $stmtLanc->fetch(PDO::FETCH_ASSOC);

    $nota['TemLancamentoFinanceiro'] = $lancamento ? true : false;
    $nota['LancamentoFinanceiroId'] = $lancamento['Id'] ?? null;


    if (!$nota) {
        throw new Exception("Nota fiscal #$notaId não encontrada");
    }

    // Consulta os itens da nota fiscal
    $stmtItens = $pdo->prepare("
        SELECT 
            i.Id,
            i.fk_Produtos_Codigo,
            p.Nome AS ProdutoNome,
            i.Quantidade,
            i.ValorUnitario,
            COALESCE(i.UnidadeMedida, p.UnidadeMedida) AS UnidadeMedida,
            i.DescricaoComplementar
        FROM NFeItens i
        JOIN Produtos p ON i.fk_Produtos_Codigo = p.Codigo
        WHERE i.fk_NFe_Id = ?
        ORDER BY i.Id
    ");
    $stmtItens->execute([$notaId]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    // Convertemos para float explícito
    foreach ($itens as &$item) {
        $item['Quantidade'] = (float) $item['Quantidade'];
        $item['ValorUnitario'] = (float) $item['ValorUnitario'];
    }
    unset($item);

    // Formata os dados para resposta
    $response = [
        'success' => true,
        'data' => [
            'nota' => $nota,
            'itens' => $itens
        ]
    ];

    file_put_contents('../logs/nfe_debug.log', "Consulta bem-sucedida para nota #$notaId\n", FILE_APPEND);
    echo json_encode($response);

} catch (PDOException $e) {
    $errorMsg = 'Erro no banco de dados: ' . $e->getMessage();
    file_put_contents('../logs/nfe_debug.log', "ERRO PDO: $errorMsg\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao acessar o banco de dados'
    ]);

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    file_put_contents('../logs/nfe_debug.log', "ERRO: $errorMsg\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMsg
    ]);
}
?>