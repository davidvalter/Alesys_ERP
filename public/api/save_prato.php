<?php
// public/api/save_prato.php
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../../includes/ImageMediaStorage.php';
require_once __DIR__ . '/../conf/logger.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$pdo = null;
$thumbnailPath = null;
$oldImageId = null;
$newImageId = null;

try {
    $pdo = getDB();
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $nome = trim($_POST['nome'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $preco = (float)($_POST['preco'] ?? 0);
    $responsavelId = (int)($_SESSION['Usuario']['id'] ?? $_SESSION['user_id'] ?? 1);

    if ($nome === '' || $categoria === '' || $preco < 0) {
        throw new InvalidArgumentException('Informe nome, categoria e preço válidos para o prato.');
    }

    $dadosFiscais = [
        trim($_POST['ncm'] ?? ''),
        trim($_POST['cest'] ?? ''),
        trim($_POST['cfop'] ?? ''),
        trim($_POST['csosn'] ?? ''),
        trim($_POST['unidade_medida'] ?? '') ?: 'UN'
    ];

    $hasUpload = isset($_FILES['foto']) && (int)$_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload) {
        $thumbnailPath = ImageMediaStorage::storeUploaded($_FILES['foto'], 'pratos');
    }

    $pdo->beginTransaction();
    if ($id) {
        $stmtCurrent = $pdo->prepare('SELECT ImagemId FROM Pratos WHERE Id = ?');
        $stmtCurrent->execute([$id]);
        $currentImage = $stmtCurrent->fetchColumn();
        if ($currentImage === false) {
            throw new RuntimeException('Prato não encontrado.');
        }
        $oldImageId = $currentImage !== null ? (int)$currentImage : null;
    }

    $newImageId = $thumbnailPath !== null
        ? ImageMediaStorage::register($pdo, $thumbnailPath)
        : $oldImageId;

    if ($id) {
        $sql = "UPDATE Pratos
                SET Nome=?, Categoria=?, Preco=?, ImagemId=?,
                    NCM=?, CEST=?, CFOP=?, CSOSN=?, UnidadeMedida=?, ResponsavelId=?
                WHERE Id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(
            [$nome, $categoria, $preco, $newImageId],
            $dadosFiscais,
            [$responsavelId, $id]
        ));
    } else {
        $sql = "INSERT INTO Pratos
                (Nome, Categoria, Preco, Status, ResponsavelId, ImagemId,
                 NCM, CEST, CFOP, CSOSN, UnidadeMedida)
                VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(
            [$nome, $categoria, $preco, $responsavelId, $newImageId],
            $dadosFiscais
        ));
        $id = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

    if ($oldImageId && $newImageId !== $oldImageId) {
        try {
            ImageMediaStorage::deleteIfUnused($pdo, $oldImageId);
        } catch (Throwable $cleanupError) {
            error_log('Não foi possível limpar a miniatura antiga do prato: ' . $cleanupError->getMessage());
        }
    }

    echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($thumbnailPath !== null) {
        ImageMediaStorage::deleteFiles($thumbnailPath);
    }
    logError('Erro ao salvar prato: ' . $e->getMessage(), [
        'post' => $_POST,
        'files' => array_keys($_FILES)
    ]);
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e instanceof InvalidArgumentException
            ? $e->getMessage()
            : 'Não foi possível salvar o prato. Verifique os dados e a configuração de imagens.'
    ], JSON_UNESCAPED_UNICODE);
}
