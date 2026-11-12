<?php
// public/api/save_cardapio.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';

try {
    $pdo = getDB();
    $input = $_POST;
    if (!$input) {
        throw new InvalidArgumentException('Dados inválidos.');
    }

    $id = !empty($input['Id']) ? (int)$input['Id'] : null;
    $nome = trim($input['Nome'] ?? '');
    $tipoItem = $input['TipoItem'] ?? '';
    $produtoId = !empty($input['ProdutoId']) ? (int)$input['ProdutoId'] : null;
    $pratoId = !empty($input['PratoId']) ? (int)$input['PratoId'] : null;
    $preco = isset($input['PrecoVenda']) ? (float)$input['PrecoVenda'] : -1;
    $categoria = trim($input['Categoria'] ?? '');
    $ativo = isset($input['Ativo']) ? (int)$input['Ativo'] : 1;

    if ($nome === '') {
        throw new InvalidArgumentException('Nome é obrigatório.');
    }
    if (!in_array($tipoItem, ['produto', 'prato'], true)) {
        throw new InvalidArgumentException('Tipo de item inválido.');
    }
    if ($preco < 0) {
        throw new InvalidArgumentException('O preço deve ser igual ou maior que zero.');
    }
    if ($tipoItem === 'produto' && !$produtoId) {
        throw new InvalidArgumentException('Selecione o produto vinculado.');
    }
    if ($tipoItem === 'prato' && !$pratoId) {
        throw new InvalidArgumentException('Selecione o prato vinculado.');
    }
    if (!in_array($ativo, [0, 1], true)) {
        throw new InvalidArgumentException('Status inválido.');
    }

    if ($tipoItem === 'produto') {
        $stmt = $pdo->prepare("SELECT 1 FROM Produtos WHERE Codigo = ? AND Ativo = 1 AND TipoProduto = 'vendavel'");
        $stmt->execute([$produtoId]);
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM Pratos WHERE Id = ? AND Status = 1");
        $stmt->execute([$pratoId]);
    }
    if (!$stmt->fetchColumn()) {
        throw new InvalidArgumentException('O produto ou prato vinculado não está ativo ou não foi encontrado.');
    }

    if ($id) {
        $sql = "UPDATE Cardapio SET
                    Nome = :nome,
                    TipoItem = :tipo,
                    ProdutoId = :produto,
                    PratoId = :prato,
                    PrecoVenda = :preco,
                    Categoria = :categoria,
                    Ativo = :ativo
                WHERE Id = :id";
    } else {
        $sql = "INSERT INTO Cardapio
                    (Nome, TipoItem, ProdutoId, PratoId, PrecoVenda, Categoria, Ativo)
                VALUES
                    (:nome, :tipo, :produto, :prato, :preco, :categoria, :ativo)";
    }

    $stmt = $pdo->prepare($sql);
    $params = [
        ':nome' => $nome,
        ':tipo' => $tipoItem,
        ':produto' => $tipoItem === 'produto' ? $produtoId : null,
        ':prato' => $tipoItem === 'prato' ? $pratoId : null,
        ':preco' => $preco,
        ':categoria' => $categoria !== '' ? $categoria : null,
        ':ativo' => $ativo
    ];
    if ($id) {
        $params[':id'] = $id;
    }
    $stmt->execute($params);

    if (!$id) {
        $id = (int)$pdo->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item salvo com sucesso.',
        'id' => $id
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Erro ao salvar item do cardápio: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível salvar o item do cardápio.'], JSON_UNESCAPED_UNICODE);
}
