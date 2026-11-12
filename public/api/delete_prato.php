<?php
// public/api/delete_prato.php
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../../includes/ImageMediaStorage.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_POST['id'])) {
        throw new InvalidArgumentException('ID do prato não informado.');
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('ID do prato inválido.');
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT ImagemId FROM Pratos WHERE Id = ?');
    $stmt->execute([$id]);
    $imageId = $stmt->fetchColumn();
    if ($imageId === false) {
        throw new RuntimeException('Prato não encontrado.');
    }

    $stmt = $pdo->prepare('DELETE FROM Pratos WHERE Id = ?');
    $stmt->execute([$id]);

    if ($imageId !== null) {
        try {
            ImageMediaStorage::deleteIfUnused($pdo, (int)$imageId);
        } catch (Throwable $cleanupError) {
            error_log('Não foi possível limpar a mídia do prato ' . $id . ': ' . $cleanupError->getMessage());
        }
    }

    echo json_encode(['success' => true, 'message' => 'Prato excluído permanentemente.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    logError('Erro ao excluir prato: ' . $e->getMessage(), ['post' => $_POST]);
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e instanceof InvalidArgumentException
            ? $e->getMessage()
            : 'Não foi possível excluir o prato.'
    ], JSON_UNESCAPED_UNICODE);
}
