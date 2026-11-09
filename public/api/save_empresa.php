<?php
/* /api/save_empresa.php — Salva dados da Empresa ou configurações ALENFE
 * Recebe (JSON): { tipo: 'empresa'|'alenfe', dados: {...} }
 */

session_start();

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

header('Content-Type: application/json');

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !isset($input['tipo']) || !is_array($input['dados'] ?? null)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload inválido']);
    exit;
}

$db = getDB();
$tipo = $input['tipo'];
$dados = $input['dados'];

try {
    if ($tipo === 'empresa') {
        salvarEmpresa($db, $dados);
    } elseif ($tipo === 'alenfe') {
        salvarAlenfe($db, $dados);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Salvo com sucesso!']);
exit;

/* ============================================================ */

function salvarEmpresa(PDO $db, array $d): void
{
    $id = (int) ($d['id'] ?? 0);
    $campos = [
        'RazaoSocial' => mb_substr((string) ($d['razaoSocial'] ?? ''), 0, 200),
        'NomeFantasia' => mb_substr((string) ($d['nomeFantasia'] ?? ''), 0, 200),
        'Cnpj' => preg_replace('/\D/', '', (string) ($d['cnpj'] ?? '')),
        'InscricaoEstadual' => mb_substr((string) ($d['inscricaoEstadual'] ?? ''), 0, 20),
        'Logradouro' => mb_substr((string) ($d['logradouro'] ?? ''), 0, 200),
        'Numero' => mb_substr((string) ($d['numero'] ?? ''), 0, 20),
        'Bairro' => mb_substr((string) ($d['bairro'] ?? ''), 0, 100),
        'Cidade' => mb_substr((string) ($d['cidade'] ?? ''), 0, 100),
        'Uf' => mb_strtoupper(mb_substr((string) ($d['uf'] ?? ''), 0, 2)),
        'Cep' => preg_replace('/\D/', '', (string) ($d['cep'] ?? '')),
        'Telefone' => mb_substr((string) ($d['telefone'] ?? ''), 0, 20),
        'Email' => mb_substr((string) ($d['email'] ?? ''), 0, 150),
    ];

    if ($id > 0) {
        $sets = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($campos)));
        $stmt = $db->prepare("UPDATE Empresa SET $sets WHERE Id = :id");
        $stmt->execute($campos + ['id' => $id]);
    } else {
        $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($campos)));
        $ph = implode(', ', array_map(fn($c) => ":$c", array_keys($campos)));
        $stmt = $db->prepare("INSERT INTO Empresa ($cols, Ativo) VALUES ($ph, 1)");
        $stmt->execute($campos);
    }
}

function salvarAlenfe(PDO $db, array $d): void
{
    $mapa = [
        'ALENFE_URL' => $d['alenfeUrl'] ?? '',
        'ALENFE_API_KEY' => $d['alenfeApiKey'] ?? '',
        'ALENFE_WEBHOOK_TOKEN' => $d['alenfeWebhookToken'] ?? '',
        'ALENFE_CPF_PADRAO' => $d['alenfeCpfPadrao'] ?? '',
        'ALENFE_CONSUMIDOR_PADRAO' => $d['alenfeConsumidorPadrao'] ?? 'CONSUMIDOR FINAL',
        'ALENFE_ENDERECO_LOGRADOURO' => $d['alenfeEndLogradouro'] ?? 'NAO INFORMADO',
        'ALENFE_ENDERECO_NUMERO' => $d['alenfeEndNumero'] ?? '0',
        'ALENFE_ENDERECO_BAIRRO' => $d['alenfeEndBairro'] ?? 'NAO INFORMADO',
        'ALENFE_ENDERECO_CIDADE' => $d['alenfeEndCidade'] ?? 'NAO INFORMADO',
        'ALENFE_ENDERECO_UF' => mb_strtoupper(mb_substr((string) ($d['alenfeEndUf'] ?? ''), 0, 2)),
        'ALENFE_ENDERECO_CEP' => preg_replace('/\D/', '', (string) ($d['alenfeEndCep'] ?? '')),
    ];

    $stmt = $db->prepare("
        INSERT INTO Configuracoes (Chave, Valor)
        VALUES (:chave, :valor)
        ON DUPLICATE KEY UPDATE Valor = VALUES(Valor)
    ");
    foreach ($mapa as $chave => $valor) {
        $stmt->execute(['chave' => $chave, 'valor' => $valor]);
    }
}