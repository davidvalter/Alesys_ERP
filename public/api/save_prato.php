<?php
// public/api/save_prato.php
require_once '../../configs/database.php';
require_once '../conf/logger.php'; // importa logger
session_start();

header('Content-Type: application/json');
try {
    $db = getDB();
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $preco = $_POST['preco'] ?? 0;
    $status = 1; // sempre ativo no cadastro
    $responsavelId = $_SESSION['user_id'] ?? 1; // pegar do login

    // Campos fiscais
    $ncm = trim($_POST['ncm'] ?? '');
    $cest = trim($_POST['cest'] ?? '');
    $cfop = trim($_POST['cfop'] ?? '');
    $csosn = trim($_POST['csosn'] ?? '');
    $unidade = trim($_POST['unidade_medida'] ?? '') ?: 'UN';

    $uploadDir = __DIR__ . '/../uploads/pratos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    // Upload da foto (se enviada)
    $fotoNome = null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $fotoNome = uniqid('prato_') . '.' . $ext;
        $destino = $uploadDir . $fotoNome;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            throw new Exception("Erro ao salvar a foto do prato.");
        }
    }
    if ($id) {
        // Atualizar prato
        if ($fotoNome) {
            $sql = "UPDATE Pratos SET Nome=?, Categoria=?, Preco=?, Foto=?,
                    NCM=?, CEST=?, CFOP=?, CSOSN=?, UnidadeMedida=?, ResponsavelId=?
                    WHERE Id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $nome,
                $categoria,
                $preco,
                $fotoNome,
                $ncm,
                $cest,
                $cfop,
                $csosn,
                $unidade,
                $responsavelId,
                $id
            ]);
        } else {
            $sql = "UPDATE Pratos SET Nome=?, Categoria=?, Preco=?,
                    NCM=?, CEST=?, CFOP=?, CSOSN=?, UnidadeMedida=?, ResponsavelId=?
                    WHERE Id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $nome,
                $categoria,
                $preco,
                $ncm,
                $cest,
                $cfop,
                $csosn,
                $unidade,
                $responsavelId,
                $id
            ]);
        }
    } else {
        // Novo prato
        $sql = "INSERT INTO Pratos (Nome, Categoria, Preco, Foto, Status, ResponsavelId,
                NCM, CEST, CFOP, CSOSN, UnidadeMedida)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nome,
            $categoria,
            $preco,
            $fotoNome,
            $status,
            $responsavelId,
            $ncm,
            $cest,
            $cfop,
            $csosn,
            $unidade
        ]);
        $id = $db->lastInsertId();
    }
    if (ob_get_level())
        ob_end_clean();
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
} catch (Exception $e) {
    if (ob_get_level())
        ob_end_clean();
    // Grava no log
    logError("Erro ao salvar prato: " . $e->getMessage(), [
        'post' => $_POST,
        'files' => $_FILES
    ]);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}