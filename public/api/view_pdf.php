<?php
require_once __DIR__ . '/../configs/database.php';
require_once __DIR__ . '/../configs/auth.php';

// Verifica autenticação
if (!isset($_SESSION['Usuario']) || !$_SESSION['Usuario']['Logado']) {
    header('HTTP/1.0 401 Unauthorized');
    exit('Acesso não autorizado');
}

// Valida o parâmetro
if (!isset($_GET['path']) || empty($_GET['path'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Caminho do PDF não especificado');
}

$pdfPath = realpath(urldecode($_GET['path']));

// Verifica se o arquivo existe e é um PDF
if (!file_exists($pdfPath) || mime_content_type($pdfPath) !== 'application/pdf') {
    header('HTTP/1.0 404 Not Found');
    exit('PDF não encontrado');
}

// Configura os headers para exibir o PDF inline
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($pdfPath) . '"');
header('Content-Length: ' . filesize($pdfPath));

// Envia o arquivo para o navegador
readfile($pdfPath);
exit;
?>