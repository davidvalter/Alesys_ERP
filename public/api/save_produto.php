<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../configs/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDB();

    // ============================================================
    // VALIDAÇÃO DOS CAMPOS OBRIGATÓRIOS (comuns a todos os produtos)
    // ============================================================
    $errors = [];
    $requiredFields = [
        'Nome' => 'Nome do produto é obrigatório',
        'UnidadeMedida' => 'Unidade de medida é obrigatória',
        'TipoProduto' => 'Tipo de produto é obrigatório'
    ];

    // ============================================================
    // VALIDAÇÃO CONDICIONAL: campos fiscais só são obrigatórios
    // para produtos VENDÁVEIS (insumos não geram nota fiscal)
    // ============================================================
    if (($input['TipoProduto'] ?? '') === 'vendavel') {
        $requiredFields['NCM'] = 'NCM é obrigatório para produtos vendáveis';
        $requiredFields['CFOP'] = 'CFOP é obrigatório para produtos vendáveis';
        $requiredFields['CSOSN'] = 'CSOSN é obrigatório para produtos vendáveis (Simples Nacional)';
    }

    // Validação condicional: CEST obrigatório quando CSOSN é de ST (201/202/203)
    if (
        ($input['TipoProduto'] ?? '') === 'vendavel'
        && in_array($input['CSOSN'] ?? '', ['201', '202', '203'], true)
    ) {
        $requiredFields['CEST'] = 'CEST é obrigatório para produtos com ICMS-ST (CSOSN 201/202/203)';
    }

    foreach ($requiredFields as $field => $message) {
        if (empty($input[$field])) {
            $errors[] = $message;
        }
    }

    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    // ============================================================
    // PREPARA OS DADOS
    // ============================================================
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
        'Ativo' => isset($input['Ativo']) ? (int) $input['Ativo'] : 1
    ];

    // ============================================================
    // VALIDA O NCM CONTRA A TABELA DE REFERÊNCIA (quando informado)
    // ============================================================
    if (!empty($dados['NCM'])) {
        $stmt = $pdo->prepare("SELECT 1 FROM ProdutosNCM WHERE Codigo = ?");
        $stmt->execute([$dados['NCM']]);
        if (!$stmt->fetch()) {
            throw new Exception('NCM informado não existe na tabela de referência');
        }
    }

    // Validação do CEST contra o vínculo com o NCM (quando informado)
    if (!empty($dados['CEST'])) {
        $stmt = $pdo->prepare("
            SELECT 1 FROM ProdutosCESTNCM
            WHERE CEST = ? AND NCM = ?
        ");
        $stmt->execute([$dados['CEST'], $dados['NCM']]);
        if (!$stmt->fetch()) {
            throw new Exception('CEST informado não é válido para o NCM selecionado');
        }
    }

    // ============================================================
    // VERIFICA DUPLICIDADE DE CÓDIGO DE BARRAS
    // (exceto para o próprio produto em edição)
    // ============================================================
    if (!empty($dados['CodigoBarras'])) {
        $sqlCheck = "SELECT Codigo FROM Produtos WHERE CodigoBarras = ?";
        $params = [$dados['CodigoBarras']];

        if ($method === 'PUT' && !empty($input['Codigo'])) {
            $sqlCheck .= " AND Codigo != ?";
            $params[] = $input['Codigo'];
        }

        $stmt = $pdo->prepare($sqlCheck);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            throw new Exception('Já existe um produto cadastrado com este código de barras');
        }
    }

    // ============================================================
    // INSERT / UPDATE
    // ============================================================
    if ($method === 'POST') {
        $sql = "INSERT INTO Produtos (
            Nome, Descricao, CodigoBarras, UnidadeMedida, Categoria, DataCadastro,
            TipoProduto, CategoriaVenda, NCM, CFOP, CSOSN, CEST, Ativo
        ) VALUES (
            :Nome, :Descricao, :CodigoBarras, :UnidadeMedida, :Categoria, NOW(),
            :TipoProduto, :CategoriaVenda, :NCM, :CFOP, :CSOSN, :CEST, :Ativo
        )";
    } elseif ($method === 'PUT') {
        if (empty($input['Codigo'])) {
            throw new Exception('Código do produto é obrigatório para atualização');
        }

        $dados['Codigo'] = $input['Codigo'];

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
            Ativo = :Ativo
        WHERE Codigo = :Codigo";
    } else {
        throw new Exception('Método não permitido');
    }

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($dados);

    if ($success) {
        $response = [
            'success' => true,
            'message' => ($method === 'POST')
                ? 'Produto cadastrado com sucesso!'
                : 'Produto atualizado com sucesso!',
            'Codigo' => ($method === 'POST')
                ? $pdo->lastInsertId()
                : $input['Codigo']
        ];
        echo json_encode($response);
    } else {
        throw new Exception('Erro ao salvar dados do produto');
    }

} catch (PDOException $e) {
    error_log('PDOException: ' . $e->getMessage() . ' - SQL: ' . ($sql ?? ''));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados',
        'error' => $e->getMessage(),
        'sql' => $sql ?? null
    ]);
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>