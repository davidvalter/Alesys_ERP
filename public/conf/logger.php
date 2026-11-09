<?php
//public/conf/logger.php

define('LOG_DIR', __DIR__ . '/../logs/');
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context, JSON_PRETTY_PRINT);
    }
    
    $logMessage .= PHP_EOL;
    
    file_put_contents(LOG_DIR . 'logger_errors.log', $logMessage, FILE_APPEND);
    
    // Debug adicional no ambiente de desenvolvimento
    //if ($_ENV['APP_ENV'] === 'development') {
    //    error_log($logMessage);
    //}
}