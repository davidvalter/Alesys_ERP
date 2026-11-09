<?php
/* /api/get_empresa.php — Retorna dados da Empresa e configurações ALENFE */
require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

header('Content-Type: application/json');

$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada']);
    exit;
}

$db = getDB();

/* ---------- Empresa ---------- */
$empresa = null;
try {
    $stmt = $db->query("SELECT * FROM Empresa WHERE Ativo = 1 ORDER BY Id LIMIT 1");
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* tabela ainda não criada */
}

/* ---------- Configurações ALENFE ---------- */
$configs = [];
try {
    $stmt = $db->query("SELECT Chave, Valor FROM Configuracoes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['Chave']] = $row['Valor'];
    }
} catch (Throwable $e) { /* tabela ainda não criada */
}

echo json_encode([
    'success' => true,
    'empresa' => $empresa,
    'configs' => $configs,
]);