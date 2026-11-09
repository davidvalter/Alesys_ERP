<?php
// public/api/inativar_prato.php

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

    $stmt = $db->prepare("UPDATE Pratos SET Status = 0 WHERE Id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Prato inativado com sucesso.']);
    exit;

} catch (Exception $e) {
    logError("Erro ao inativar prato: " . $e->getMessage(), [
        'post' => $_POST
    ]);

    echo json_encode(['success' => false, 'error' => 'Erro interno ao inativar prato.']);
    exit;
}
