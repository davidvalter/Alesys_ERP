<?php
/* /configs/alenfe.php — Configurações da integração ALENFE.
 * Os valores são lidos da tabela Configuracoes (customizável no front).
 * Estes defines são apenas fallback se a tabela ainda não existir. */

define('ALENFE_TIMEOUT_CONEXAO', 5);   // segundos
define('ALENFE_TIMEOUT_TOTAL', 25);    // segundos para o POST /nfce/criar
define('ALENFE_LOG_PATH', __DIR__ . '/../logs/alenfe.log');

/* Fallback (usado se a tabela Configuracoes não tiver o registro) */
define('ALENFE_URL_FALLBACK', 'http://localhost:3001');
define('ALENFE_API_KEY_FALLBACK', 'ak_d074c14dceebf3adf9ff66b63c4966b3');
define('ALENFE_WEBHOOK_TOKEN_FALLBACK', 'troque-por-um-token-secreto-forte');