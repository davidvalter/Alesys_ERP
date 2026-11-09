<?php
// public/api/registrar_lancamento_compra.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

//file_put_contents('../logs/registrar_lancamento_compra.log',
//    date('Y-m-d H:i:s') . " - POST recebido: " . json_encode($_POST) . "\n",
//    FILE_APPEND
//);


try {
    $db = getDB();

    // aceita GET ou POST
    $notaId = $_REQUEST['notaId'] ?? null;
    if (!$notaId) {
        throw new Exception("Parâmetro notaId não informado.");
    }
    $notaId = (int)$notaId;
    if ($notaId <= 0) {
        throw new Exception("notaId inválido.");
    }

    // Verifica se a nota existe e obtém dados (inclui nome do mercado)
    $stmt = $db->prepare("
        SELECT 
            n.Id,
            n.Numero,
            n.ValorTotal,
            n.fk_Mercados_Codigo,
            m.Nome AS MercadoNome
        FROM NFe n
        LEFT JOIN Mercados m ON n.fk_Mercados_Codigo = m.Codigo
        WHERE n.Id = ?
        LIMIT 1
    ");
    $stmt->execute([$notaId]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nota) {
        throw new Exception("Nota fiscal #$notaId não encontrada.");
    }

    // Verifica duplicidade (Origem = 'compra' e OrigemId = notaId)
    $stmtChk = $db->prepare("SELECT Id FROM FinanceiroLancamentos WHERE Origem = 'compra' AND OrigemId = ? LIMIT 1");
    $stmtChk->execute([$notaId]);
    $existe = $stmtChk->fetchColumn();
    if ($existe) {
        echo json_encode([
            'success' => false,
            'already' => true,
            'message' => "Já existe um lançamento financeiro vinculado a esta nota (Id: $existe).",
            'lancamentoId' => (int)$existe
        ]);
        exit;
    }

    // Busca conta padrão: ID = 1 (deve existir e estar ativa)
    $contaPadraoId = 1;
    $stmtConta = $db->prepare("SELECT Id, Nome, Tipo, Ativo FROM FinanceiroContasBancarias WHERE Id = ? LIMIT 1");
    $stmtConta->execute([$contaPadraoId]);
    $conta = $stmtConta->fetch(PDO::FETCH_ASSOC);
    if (!$conta || (int)$conta['Ativo'] !== 1) {
        throw new Exception("Conta bancária padrão (ID = 1) não encontrada ou está inativa. Cadastre/ative a conta com ID = 1.");
    }

    // Garante que exista a categoria "Compra de Mercadoria" (cria se não existir)
    $categoriaNome = 'Compra de Mercadoria';
    $stmtCat = $db->prepare("SELECT Id FROM FinanceiroCategorias WHERE Nome = ? LIMIT 1");
    $stmtCat->execute([$categoriaNome]);
    $categoriaId = $stmtCat->fetchColumn();

    if (!$categoriaId) {
        $stmtInsCat = $db->prepare("INSERT INTO FinanceiroCategorias (Nome, Tipo, Grupo, Ativo) VALUES (?, 'saida', 'Compras', 1)");
        $stmtInsCat->execute([$categoriaNome]);
        $categoriaId = $db->lastInsertId();
        // opcional: log da criação
        logError("Categoria financeira criada automaticamente: $categoriaNome (Id: $categoriaId)");
    }

    // Preparar dados do lançamento
    $responsavelId = $_SESSION['user_id'] ?? 1;
    $numeroNota = $nota['Numero'] ?? $notaId;
    $mercadoNome = $nota['MercadoNome'] ?? null;
    $descricao = "Compra" . ($mercadoNome ? " no Mercado {$mercadoNome}" : "") . " - NF #{$numeroNota}";

    // Valor: sempre NEGATIVO para compras (saída)
    $valorTotal = (float)($nota['ValorTotal'] ?? 0.0);
    $valorLancamento = -abs($valorTotal);

    // inserir lançamento e log dentro de transação
    $db->beginTransaction();

    $sql = "INSERT INTO FinanceiroLancamentos
        (DataLancamento, Tipo, CategoriaId, ContaId, FormaPagamentoId, ValorBruto, Taxas, Descricao, Origem, OrigemId, Status, ResponsavelId)
        VALUES (NOW(), :Tipo, :CategoriaId, :ContaId, NULL, :ValorBruto, :Taxas, :Descricao, :Origem, :OrigemId, :Status, :ResponsavelId)";
    $stmtIns = $db->prepare($sql);
    $stmtIns->execute([
        ':Tipo' => 'saida',
        ':CategoriaId' => $categoriaId,
        ':ContaId' => $contaPadraoId,
        ':ValorBruto' => $valorLancamento,
        ':Taxas' => 0.00,
        ':Descricao' => $descricao,
        ':Origem' => 'compra',
        ':OrigemId' => $notaId,
        ':Status' => 'pendente',
        ':ResponsavelId' => $responsavelId
    ]);

    $lancId = $db->lastInsertId();

    // grava log na tabela FinanceiroLogMovimentacoes
    $stmtLog = $db->prepare("INSERT INTO FinanceiroLogMovimentacoes (LancamentoId, ContaId, Valor, Acao, Observacoes) VALUES (:LancamentoId, :ContaId, :Valor, :Acao, :Observacoes)");
    $stmtLog->execute([
        ':LancamentoId' => $lancId,
        ':ContaId' => $contaPadraoId,
        ':Valor' => $valorLancamento,
        ':Acao' => 'insercao',
        ':Observacoes' => "Gerado via registrar_lancamento_compra.php para NFe.Id={$notaId}"
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => "Lançamento financeiro criado com sucesso.",
        'lancamentoId' => (int)$lancId
    ]);
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    // registra no log sem vazar detalhes pro usuário
    logError("Erro ao registrar lancamento_compra: " . $e->getMessage(), ['request' => $_REQUEST ?? null]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar lançamento financeiro. Verifique os logs.'
    ]);
    exit;
}
