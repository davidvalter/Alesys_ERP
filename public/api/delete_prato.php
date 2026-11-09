<?php
// public/api/delete_prato.php

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

    $stmt = $db->prepare("DELETE FROM Pratos WHERE Id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Prato excluído permanentemente.']);
    exit;

} catch (Exception $e) {
    logError("Erro ao excluir prato: " . $e->getMessage(), [
        'post' => $_POST
    ]);

    echo json_encode(['success' => false, 'error' => 'Erro interno ao excluir prato.']);
    exit;
}
