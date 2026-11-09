<?php
// public/api/save_cardapio.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

try {
    $db = getDB();

    // Recebe JSON
    $input = $_POST;

    // Validação básica
    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $id = $input['Id'] ?? null;
    $nome = trim($input['Nome'] ?? '');
    $tipoItem = $input['TipoItem'] ?? '';
    $produtoId = $input['ProdutoId'] ?? null;
    $pratoId = $input['PratoId'] ?? null;
    $preco = $input['PrecoVenda'] ?? 0;
    $categoria = $input['Categoria'] ?? null;
    $imagem = $input['Imagem'] ?? null;
    $ativo = $input['Ativo'] ?? 1;

    // Validações
    if (!$nome) {
        throw new Exception('Nome é obrigatório');
    }

    if (!in_array($tipoItem, ['produto', 'prato'])) {
        throw new Exception('TipoItem inválido');
    }

    if ($tipoItem === 'produto' && !$produtoId) {
        throw new Exception('ProdutoId é obrigatório para tipo produto');
    }

    if ($tipoItem === 'prato' && !$pratoId) {
        throw new Exception('PratoId é obrigatório para tipo prato');
    }

    $imagem = null;

    if (isset($_FILES['ImagemFile']) && $_FILES['ImagemFile']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = __DIR__ . '/../uploads/cardapio/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['ImagemFile']['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid('img_') . '.' . $ext;

        $caminho = $uploadDir . $nomeArquivo;

        if (move_uploaded_file($_FILES['ImagemFile']['tmp_name'], $caminho)) {
            $imagem = '/uploads/cardapio/' . $nomeArquivo;
        }
    }

    // INSERT
    if (!$id) {

        $sql = "
            INSERT INTO Cardapio 
            (Nome, TipoItem, ProdutoId, PratoId, PrecoVenda, Categoria, Imagem, Ativo)
            VALUES 
            (:nome, :tipoItem, :produtoId, :pratoId, :preco, :categoria, :imagem, :ativo)
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':tipoItem' => $tipoItem,
            ':produtoId' => $tipoItem === 'produto' ? $produtoId : null,
            ':pratoId' => $tipoItem === 'prato' ? $pratoId : null,
            ':preco' => $preco,
            ':categoria' => $categoria,
            ':imagem' => $imagem,
            ':ativo' => $ativo
        ]);

        $id = $db->lastInsertId();

    } else {
        // UPDATE

        $sql = "
            UPDATE Cardapio SET
                Nome = :nome,
                TipoItem = :tipoItem,
                ProdutoId = :produtoId,
                PratoId = :pratoId,
                PrecoVenda = :preco,
                Categoria = :categoria,
                Imagem = :imagem,
                Ativo = :ativo
            WHERE Id = :id
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nome' => $nome,
            ':tipoItem' => $tipoItem,
            ':produtoId' => $tipoItem === 'produto' ? $produtoId : null,
            ':pratoId' => $tipoItem === 'prato' ? $pratoId : null,
            ':preco' => $preco,
            ':categoria' => $categoria,
            ':imagem' => $imagem,
            ':ativo' => $ativo
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item salvo com sucesso',
        'id' => $id
    ]);

} catch (Exception $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}