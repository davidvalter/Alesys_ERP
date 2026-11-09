<?php
require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception("ID da receita não informado.");
    }

    $id = intval($_POST['id']);
    $db = getDB();

    $stmt = $db->prepare("UPDATE Receitas SET Ativo = 0 WHERE Id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Ingrediente removido da receita.']);
    exit;

} catch (Exception $e) {
    logError("Erro ao excluir receita: " . $e->getMessage(), [
        'post' => $_POST
    ]);
    echo json_encode(['success' => false, 'error' => 'Erro interno ao excluir receita.']);
}
