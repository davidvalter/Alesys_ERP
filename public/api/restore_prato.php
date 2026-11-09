<?php
// public/api/restore_prato.php
require_once '../../configs/database.php';
require_once '../conf/logger.php';
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception("ID do prato não informado.");
    }

    $id = intval($_POST['id']);
    $db = getDB();

    $stmt = $db->prepare("UPDATE Pratos SET Status = 1 WHERE Id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Prato ativado com sucesso.']);
    exit;

} catch (Exception $e) {
    logError("Erro ao restaurar prato: " . $e->getMessage(), [
        'post' => $_POST
    ]);

    echo json_encode(['success' => false, 'error' => 'Erro interno ao restaurar prato.']);
    exit;
}
