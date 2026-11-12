<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../configs/database.php';
require_once __DIR__ . '/../../includes/ImageMediaStorage.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = stripos($contentType, 'multipart/form-data') !== false
    ? $_POST
    : (json_decode(file_get_contents('php://input'), true) ?: []);

$pdo = null;
$thumbnailPath = null;
$oldImageId = null;

try {
    $pdo = getDB();
    $isUpdate = $method === 'PUT' || !empty($input['Codigo']);
    if (!in_array($method, ['POST', 'PUT'], true)) {
        throw new InvalidArgumentException('Método não permitido.');
    }

    $requiredFields = [
        'Nome' => 'Nome do produto é obrigatório',
        'UnidadeMedida' => 'Unidade de medida é obrigatória',
        'TipoProduto' => 'Tipo de produto é obrigatório'
    ];
    if (($input['TipoProduto'] ?? '') === 'vendavel') {
        $requiredFields['NCM'] = 'NCM é obrigatório para produtos vendáveis';
        $requiredFields['CFOP'] = 'CFOP é obrigatório para produtos vendáveis';
        $requiredFields['CSOSN'] = 'CSOSN é obrigatório para produtos vendáveis (Simples Nacional)';
    }
    if (
        ($input['TipoProduto'] ?? '') === 'vendavel'
        && in_array($input['CSOSN'] ?? '', ['201', '202', '203'], true)
    ) {
        $requiredFields['CEST'] = 'CEST é obrigatório para produtos com ICMS-ST (CSOSN 201/202/203)';
    }
    $errors = [];
    foreach ($requiredFields as $field => $message) {
        if (empty($input[$field])) {
            $errors[] = $message;
        }
    }
    if ($errors) {
        throw new InvalidArgumentException(implode("\n", $errors));
    }

    $codigo = $isUpdate ? (int)($input['Codigo'] ?? 0) : null;
    if ($isUpdate && !$codigo) {
        throw new InvalidArgumentException('Código do produto é obrigatório para atualização.');
    }

    $dados = [
        'Nome' => trim($input['Nome']),
        'Descricao' => !empty($input['Descricao']) ? trim($input['Descricao']) : null,
        'CodigoBarras' => !empty($input['CodigoBarras']) ? trim($input['CodigoBarras']) : null,
        'UnidadeMedida' => $input['UnidadeMedida'],
        'Categoria' => !empty($input['Categoria']) ? trim($input['Categoria']) : null,
        'TipoProduto' => $input['TipoProduto'],
        'CategoriaVenda' => !empty($input['CategoriaVenda']) ? $input['CategoriaVenda'] : null,
        'NCM' => !empty($input['NCM']) ? trim($input['NCM']) : null,
        'CFOP' => !empty($input['CFOP']) ? trim($input['CFOP']) : null,
        'CSOSN' => !empty($input['CSOSN']) ? trim($input['CSOSN']) : null,
        'CEST' => !empty($input['CEST']) ? trim($input['CEST']) : null,
        'Ativo' => isset($input['Ativo']) ? (int)$input['Ativo'] : 1
    ];

    if ($dados['TipoProduto'] === 'vendavel') {
        $stmt = $pdo->prepare("SELECT 1 FROM ProdutosNCM WHERE Codigo = ?");
        $stmt->execute([$dados['NCM']]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException('NCM informado não existe na tabela de referência.');
        }
    }
    if (!empty($dados['CEST'])) {
        $stmt = $pdo->prepare("SELECT 1 FROM ProdutosCESTNCM WHERE CEST = ? AND NCM = ?");
        $stmt->execute([$dados['CEST'], $dados['NCM']]);
        if (!$stmt->fetch()) {
            throw new InvalidArgumentException('CEST informado não é válido para o NCM selecionado.');
        }
    }

    if (!empty($dados['CodigoBarras'])) {
        $sqlCheck = "SELECT Codigo FROM Produtos WHERE CodigoBarras = ?";
        $params = [$dados['CodigoBarras']];
        if ($isUpdate) {
            $sqlCheck .= " AND Codigo != ?";
            $params[] = $codigo;
        }
        $stmt = $pdo->prepare($sqlCheck);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Já existe um produto cadastrado com este código de barras.');
        }
    }

    if ($isUpdate) {
        $stmtCurrent = $pdo->prepare('SELECT ImagemId FROM Produtos WHERE Codigo = ?');
        $stmtCurrent->execute([$codigo]);
        $currentImage = $stmtCurrent->fetchColumn();
        if ($currentImage === false) {
            throw new RuntimeException('Produto não encontrado.');
        }
        $oldImageId = $currentImage !== null ? (int)$currentImage : null;
    }

    $hasUpload = isset($_FILES['ImagemFile']) && (int)$_FILES['ImagemFile']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($hasUpload) {
        $thumbnailPath = ImageMediaStorage::storeUploaded($_FILES['ImagemFile'], 'produtos');
    }

    $pdo->beginTransaction();
    $dados['ImagemId'] = $thumbnailPath !== null
        ? ImageMediaStorage::register($pdo, $thumbnailPath)
        : $oldImageId;

    if ($isUpdate) {
        $dados['Codigo'] = $codigo;
        $sql = "UPDATE Produtos SET
            Nome = :Nome,
            Descricao = :Descricao,
            CodigoBarras = :CodigoBarras,
            UnidadeMedida = :UnidadeMedida,
            Categoria = :Categoria,
            TipoProduto = :TipoProduto,
            CategoriaVenda = :CategoriaVenda,
            NCM = :NCM,
            CFOP = :CFOP,
            CSOSN = :CSOSN,
            CEST = :CEST,
            Ativo = :Ativo,
            ImagemId = :ImagemId
        WHERE Codigo = :Codigo";
    } else {
        $sql = "INSERT INTO Produtos (
            Nome, Descricao, CodigoBarras, UnidadeMedida, Categoria, DataCadastro,
            TipoProduto, CategoriaVenda, NCM, CFOP, CSOSN, CEST, Ativo, ImagemId
        ) VALUES (
            :Nome, :Descricao, :CodigoBarras, :UnidadeMedida, :Categoria, NOW(),
            :TipoProduto, :CategoriaVenda, :NCM, :CFOP, :CSOSN, :CEST, :Ativo, :ImagemId
        )";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);
    if (!$isUpdate) {
        $codigo = (int)$pdo->lastInsertId();
    }
    $pdo->commit();

    if ($oldImageId && (int)$dados['ImagemId'] !== $oldImageId) {
        ImageMediaStorage::deleteIfUnused($pdo, $oldImageId);
    }

    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'Produto atualizado com sucesso!' : 'Produto cadastrado com sucesso!',
        'Codigo' => $codigo
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($thumbnailPath !== null) {
        ImageMediaStorage::deleteFiles($thumbnailPath);
    }
    error_log('Erro ao salvar produto: ' . $e->getMessage());
    $isUserError = $e instanceof InvalidArgumentException;
    http_response_code($isUserError ? 400 : 500);
    echo json_encode([
        'success' => false,
        'message' => $isUserError ? $e->getMessage() : 'Não foi possível salvar o produto.'
    ], JSON_UNESCAPED_UNICODE);
}
