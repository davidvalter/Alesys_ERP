<?php
/**
 * API - Salvar Mercado (CRUD)
 * CREATE e UPDATE via POST
 * LOG apenas para ERROS e EXCEÇÕES
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../configs/database.php';

// ===============================
// CONFIG LOG
// ===============================
$logFile = __DIR__ . '/../logs/save_mercado.log';

function logSaveMercado($level, $message, $context = [])
{
    global $logFile;

    $date = date('Y-m-d H:i:s');
    $ctx  = !empty($context)
        ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : '';

    $line = "[$date] [$level] $message $ctx" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

// ===============================
// LEITURA DO JSON
// ===============================
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

try {

    if (!is_array($input)) {
        throw new Exception('Payload JSON inválido');
    }

    $pdo = getDB();

    // ===============================
    // VALIDAÇÃO
    // ===============================
    $required = ['Nome', 'RazaoSocial', 'CNPJ'];
    $missing = [];

    foreach ($required as $field) {
        if (empty(trim($input[$field] ?? ''))) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        throw new Exception(
            'Campos obrigatórios ausentes: ' . implode(', ', $missing)
        );
    }

    // ===============================
    // NORMALIZA DADOS
    // ===============================
    $dados = [
        'Nome'              => trim($input['Nome']),
        'RazaoSocial'       => trim($input['RazaoSocial']),
        'CNPJ'              => preg_replace('/\D/', '', $input['CNPJ']),
        'InscricaoEstadual' => $input['InscricaoEstadual'] ?? null,
        'Email'             => $input['Email'] ?? null,
        'Endereco'          => $input['Endereco'] ?? null,
        'CEP'               => isset($input['CEP']) ? preg_replace('/\D/', '', $input['CEP']) : null,
        'Municipio'         => $input['Municipio'] ?? null,
        'Estado'            => $input['Estado'] ?? null,
        'Pais'              => 'Brasil',
        'MatrizFilial'      => isset($input['MatrizFilial']) ? (int)$input['MatrizFilial'] : 1,
        'Telefone'          => isset($input['Telefone']) ? preg_replace('/\D/', '', $input['Telefone']) : null
    ];

    $isUpdate = !empty($input['Codigo']);

    // ===============================
    // CNPJ DUPLICADO (REGRA)
    // ===============================
    if ($isUpdate) {
        $stmt = $pdo->prepare("
            SELECT Codigo 
            FROM Mercados 
            WHERE CNPJ = :CNPJ AND Codigo != :Codigo
            LIMIT 1
        ");
        $stmt->execute([
            ':CNPJ'   => $dados['CNPJ'],
            ':Codigo' => $input['Codigo']
        ]);
    } else {
        $stmt = $pdo->prepare("
            SELECT Codigo 
            FROM Mercados 
            WHERE CNPJ = :CNPJ
            LIMIT 1
        ");
        $stmt->execute([
            ':CNPJ' => $dados['CNPJ']
        ]);
    }

    if ($stmt->fetch()) {
        logSaveMercado('WARNING', 'CNPJ duplicado', [
            'CNPJ'   => $dados['CNPJ'],
            'Codigo' => $input['Codigo'] ?? null
        ]);

        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Já existe um mercado cadastrado com este CNPJ.'
        ]);
        exit;
    }

    // ===============================
    // SQL
    // ===============================
    if ($isUpdate) {
        $dados['Codigo'] = (int)$input['Codigo'];

        $sets = [];
        foreach ($dados as $k => $v) {
            if ($k !== 'Codigo') {
                $sets[] = "$k = :$k";
            }
        }

        $sql = "UPDATE Mercados SET " . implode(', ', $sets) . " WHERE Codigo = :Codigo";
    } else {
        $cols = implode(', ', array_keys($dados));
        $pars = ':' . implode(', :', array_keys($dados));
        $sql  = "INSERT INTO Mercados ($cols) VALUES ($pars)";
    }

    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($dados)) {
        throw new Exception('Falha ao executar SQL');
    }

    echo json_encode([
        'success' => true,
        'message' => $isUpdate
            ? 'Mercado atualizado com sucesso!'
            : 'Mercado cadastrado com sucesso!',
        'Codigo' => $isUpdate ? $input['Codigo'] : $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {

    logSaveMercado('ERROR', 'PDOException', [
        'msg'  => $e->getMessage(),
        'code' => $e->getCode()
    ]);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro no banco de dados'
    ]);

} catch (Exception $e) {

    logSaveMercado('WARNING', 'Exception', [
        'msg' => $e->getMessage()
    ]);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
