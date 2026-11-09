<?php
// public/api/nfe_upload_foto.php

require_once "../../configs/database.php";
header("Content-Type: application/json; charset=utf-8");

// ============================
// Função de log (somente erro)
// ============================
function logUploadNfeErro(string $mensagem): void
{
    $logDir = __DIR__ . "/../logs/mobile_nf/";

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $arquivo = $logDir . "log_uploadnfe_mobile_" . date("Y-m-d") . ".log";

    $linha = "[" . date("Y-m-d H:i:s") . "] " . $mensagem . PHP_EOL;
    file_put_contents($arquivo, $linha, FILE_APPEND);
}

try {

    if (empty($_POST)) {
        throw new Exception("FormData vazio");
    }

    $numero = $_POST['numero'] ?? null;
    $pdfBase64 = $_POST['pdfBase64'] ?? null;

    if (!$numero || !$pdfBase64) {
        throw new Exception("Número da nota ou PDF ausente");
    }

    $pdo = getDB();

    // ============================
    // Buscar nota
    // ============================
    $stmt = $pdo->prepare("
        SELECT Id, Numero, DataEmissao, fk_Mercados_Codigo
        FROM NFe
        WHERE Numero = ?
        LIMIT 1
    ");
    $stmt->execute([$numero]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nota) {
        throw new Exception("Nota não encontrada: {$numero}");
    }

    $id = $nota["Id"];
    $num = $nota["Numero"];
    $mercado = $nota["fk_Mercados_Codigo"];
    $dataEmissao = date("Y-m-d", strtotime($nota["DataEmissao"]));

    // ============================
    // Nome e diretório do PDF
    // ============================
    $nomeArquivo = "NF_COMPRA_{$dataEmissao}_{$num}_{$mercado}.pdf";
    $dir = __DIR__ . "/../uploads/nf_compras/";

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $caminhoCompleto = $dir . $nomeArquivo;
    $caminhoBanco = "/uploads/nf_compras/" . $nomeArquivo;

    // ============================
    // Decode Base64
    // ============================
    if (strpos($pdfBase64, "base64,") !== false) {
        $pdfBase64 = explode("base64,", $pdfBase64)[1];
    }

    $pdfBin = base64_decode($pdfBase64);
    if ($pdfBin === false) {
        throw new Exception("Falha ao decodificar PDF (base64 inválido)");
    }

    if (file_put_contents($caminhoCompleto, $pdfBin) === false) {
        throw new Exception("Falha ao salvar o PDF no disco");
    }

    // ============================
    // Atualiza banco
    // ============================
    $stmtUp = $pdo->prepare("
        UPDATE NFe
        SET pdf_path = ?
        WHERE Id = ?
    ");
    $stmtUp->execute([$caminhoBanco, $id]);

    echo json_encode([
        "success" => true,
        "message" => "PDF salvo com sucesso",
        "path" => $caminhoBanco
    ]);

} catch (Throwable $e) {

    logUploadNfeErro($e->getMessage());

    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao processar upload da NFe"
    ]);
}
