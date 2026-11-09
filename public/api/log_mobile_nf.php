<?php
// /public/api/log_mobile_nf.php
header("Content-Type: application/json; charset=utf-8");

$body = file_get_contents("php://input");
$data = json_decode($body, true);

if (!$data || empty($data["msg"])) {
    echo json_encode(["success" => false, "message" => "Mensagem inválida"]);
    exit;
}

$logDir = __DIR__ . "/../logs/mobile_nf/";
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$arquivo = $logDir . "mobile_" . date("Y-m-d") . ".log";

$linha = "[" . date("Y-m-d H:i:s") . "] " . $data["msg"] . "\n";
file_put_contents($arquivo, $linha, FILE_APPEND);

echo json_encode(["success" => true]);
