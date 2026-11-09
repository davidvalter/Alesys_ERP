<?php
$tmpDir = __DIR__ . '/../uploads/tmp/';
$files = glob($tmpDir . '*');

$limite = time() - (60 * 60 * 6); // 6 horas

foreach ($files as $file) {
    if (is_file($file) && filemtime($file) < $limite) {
        unlink($file);
    }
}

echo "Limpeza concluída";