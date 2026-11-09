<?php
// public/api/insert_estoque.php
require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    $db = getDB();

    $nfeId = $_POST['nfeId'] ?? null;
    $responsavelId = $_SESSION['user_id'] ?? 1;

    if (!$nfeId) {
        throw new Exception("ID da NFe não informado.");
    }

    // Busca itens da NFe
    $sqlItens = "SELECT Id, ProdutoId, Quantidade 
                 FROM NFeItens 
                 WHERE NFeId = ?";
    $stmtItens = $db->prepare($sqlItens);
    $stmtItens->execute([$nfeId]);
    $itens = $stmtItens->fetchAll();

    if (!$itens) {
        throw new Exception("Nenhum item encontrado para a NFe #$nfeId.");
    }

    $sqlInsert = "INSERT INTO MovimentacoesEstoque 
        (ProdutoId, Quantidade, Tipo, Origem, OrigemId, DataMovimento, ResponsavelId, Observacoes)
        VALUES (?, ?, 'entrada', 'nfe', ?, NOW(), ?, ?)";

    $stmtInsert = $db->prepare($sqlInsert);

    foreach ($itens as $item) {
        $stmtInsert->execute([
            $item['ProdutoId'],
            $item['Quantidade'],
            $item['Id'],           // OrigemId = Id do item da NFe
            $responsavelId,
            'Entrada via nota fiscal'
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Movimentações de estoque geradas com sucesso.']);

} catch (Exception $e) {
    logError("Erro ao inserir estoque: " . $e->getMessage(), [
        'post' => $_POST
    ]);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
