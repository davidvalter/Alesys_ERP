<?php
// public/templates/base_protected.php

session_start();
require_once __DIR__ . '/../conf/autenticacao.php';

// Conexão com o banco de dados
require_once __DIR__ . '/../../configs/database.php';

// Inclui os componentes
include __DIR__ . '/../components/topbar.php';
include __DIR__ . '/../components/sidebar.php';

// Define o módulo atual (deve ser definido em cada página que usa este template)
if (!isset($moduloAtual)) {
    die('Módulo não definido na página');
}

// Verifica permissão
verificarPermissao($moduloAtual);

// Configurações padrão
$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');
$nomeUsuario = $_SESSION['Usuario']['nome'] ?? 'Usuário';
$perfilUsuario = $_SESSION['Usuario']['perfil_nome'] ?? 'Perfil não definido';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Sistema Restaurante') ?></title>
    <link rel="stylesheet" href="/../styles/base.css">
    <link rel="stylesheet" href="/../styles/layout.css">
    <link rel="stylesheet" href="/../styles/components.css">
    <link rel="stylesheet" href="/../styles/modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</head>

<body class="layout-2cols">
    <!-- Barra Superior -->
    <?php renderTopbar(); ?>

    <!-- Menu Lateral Esquerdo -->
    <?php renderSidebar($paginaAtual); ?>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <?php if (isset($tituloConteudo)): ?>
            <h1 style="color: var(--verde-escuro); margin-bottom: 20px;">
                <?= htmlspecialchars($tituloConteudo) ?>
            </h1>
        <?php endif; ?>

        <script src="../../js/topbar.js"></script>
        <script src="../../js/sidebar.js"></script>

</body>