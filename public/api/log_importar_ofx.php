<?php
// public/api/log_importar_ofx.php

header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_fatal_errors.log');
error_reporting(E_ALL);



require_once __DIR__ . '/../utils/OfxParser.php';

const LOG_FILE = __DIR__ . '/../logs/importar_ofx.log';

/**
 * Loga apenas erros e exceções
 */
function logOfxError(string $message): void
{
    $dir = dirname(LOG_FILE);

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $entry = sprintf(
        "[%s] %s\n",
        date('Y-m-d H:i:s'),
        $message
    );

    file_put_contents(LOG_FILE, $entry, FILE_APPEND | LOCK_EX);
}

try {

    // 🔒 Validação de upload
    if (empty($_FILES['arquivo'])) {
        throw new Exception('Nenhum arquivo OFX enviado');
    }

    $file = $_FILES['arquivo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo OFX');
    }

    // 🔍 Validação de extensão
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'ofx') {
        throw new Exception('Arquivo inválido. Envie um arquivo .OFX');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Arquivo OFX inválido ou inseguro');
    }

    // 🚀 Processa OFX
// 🚀 Processa OFX
    $parser = new OfxParser();
    $dados = $parser->parse($file['tmp_name']);

    echo json_encode([
        'success' => true,
        'banco' => $dados['banco'],
        'agencia' => $dados['agencia'],
        'conta' => $dados['conta'],
        'transacoes' => $dados['transacoes'],
    ]);


} catch (Throwable $e) {

    logOfxError(
        "Erro ao importar OFX | " .
        "Mensagem: {$e->getMessage()} | " .
        "Arquivo: {$e->getFile()} | " .
        "Linha: {$e->getLine()}"
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Erro ao importar OFX. Verifique o arquivo ou contate o suporte.'
    ]);
}
