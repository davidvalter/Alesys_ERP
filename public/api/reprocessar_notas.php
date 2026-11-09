<?php
// public/api/reprocessar_notas.php
require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    $db = getDB();
    $responsavelId = $_SESSION['user_id'] ?? 1;

    // Busca todas as NFe
    $stmtNotas = $db->query("SELECT Id, Numero, DataEmissao, DataCadastro FROM NFe ORDER BY Id ASC");
    $notas = $stmtNotas->fetchAll();

    $movsInseridas = 0;
    $notasProcessadas = 0;

    $stmtItens = $db->prepare("SELECT Id, fk_Produtos_Codigo, Quantidade 
                           FROM NFeItens 
                           WHERE fk_NFe_Id = ?");


    $stmtVerifica = $db->prepare("SELECT COUNT(*) FROM MovimentacoesEstoque WHERE Origem = 'nfe' AND OrigemId = ?");

    $stmtInsert = $db->prepare("INSERT INTO MovimentacoesEstoque 
        (ProdutoId, Quantidade, Tipo, Origem, OrigemId, DataMovimento, ResponsavelId, Observacoes)
        VALUES (?, ?, 'entrada', 'nfe', ?, NOW(), ?, ?)");

    foreach ($notas as $nota) {
        $notasProcessadas++;

        // Busca itens da nota
        $stmtItens->execute([$nota['Id']]);
        $itens = $stmtItens->fetchAll();

        foreach ($itens as $item) {
            // Verifica se já existe movimentação desse item
            $stmtVerifica->execute([$item['Id']]);
            $existe = $stmtVerifica->fetchColumn();

            if ($existe == 0) {
                // Insere movimentação
                $stmtInsert->execute([
                    $item['fk_Produtos_Codigo'], // ProdutoId correto
                    $item['Quantidade'],
                    $item['Id'], // OrigemId = Id do item da NFe
                    $responsavelId,
                    'Reprocessamento de nota fiscal #' . $nota['Id']
                ]);

                $movsInseridas++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'notas_processadas' => $notasProcessadas,
        'movimentacoes_inseridas' => $movsInseridas,
        'message' => "Reprocessamento concluído: $movsInseridas movimentações criadas."
    ]);

} catch (Exception $e) {
    logError("Erro ao reprocessar notas: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
