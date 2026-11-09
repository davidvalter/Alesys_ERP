<?php
// public/conf/autenticacao.php
function inicializarLog()
{
    $logDir = __DIR__ . '/../logs/permission.log';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    return $logDir;
}

function registrarLog($mensagem, $tipo = 'ERRO', $detalhes = [])
{
    $logDir = inicializarLog();
    $logFile = $logDir . '/auth_' . date('Y-m-d') . '.log';

    $logEntry = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        $tipo,
        $mensagem,
        !empty($detalhes) ? json_encode($detalhes) : ''
    );

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function verificarAutenticacao()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['Usuario']['id']) || !$_SESSION['Usuario']['logado']) {
        registrarLog('Sessão expirada ou inválida', 'AUTH');
        $_SESSION['login_erro'] = 'Sessão expirada ou inválida. Faça login novamente.';
        header('Location: ../login.php');
        exit();
    }
}

function verificarPermissao($modulo = null)
{
    verificarAutenticacao();

    // Se nenhum módulo for especificado, usa o nome do arquivo atual
    if ($modulo === null) {
        $modulo = basename($_SERVER['PHP_SELF'], '.php');
    }

    $dadosUsuario = $_SESSION['Usuario'] ?? [];
    $isAdmin = ($dadosUsuario['perfil_nome'] ?? '') === 'admin';

    if ($isAdmin) {
        registrarLog('Acesso concedido (admin)', 'ACESSO', [
            'modulo' => $modulo,
            'usuario' => $dadosUsuario['username'] ?? ''
        ]);
        return;
    }

    $permissoes = $dadosUsuario['permissoes'] ?? [];

    if (!in_array($modulo, $permissoes)) {
        registrarLog('Acesso negado', 'ACESSO', [
            'modulo' => $modulo,
            'usuario' => $dadosUsuario['username'] ?? '',
            'permissoes' => $permissoes,
            'pagina' => basename($_SERVER['PHP_SELF'])
        ]);

        $_SESSION['login_erro'] = 'Você não tem permissão para acessar este recurso.';
        header('Location: ../dashboard.php');
        exit();
    }

    registrarLog('Acesso concedido', 'ACESSO', [
        'modulo' => $modulo,
        'usuario' => $dadosUsuario['username'] ?? ''
    ]);
}