<?php
/* /public/templates/base_protected_v2.php */

session_start();

require_once __DIR__ . '/../conf/autenticacao.php';
require_once __DIR__ . '/../../configs/database.php';

include __DIR__ . '/../components/topbar_v2.php';
include __DIR__ . '/../components/sidebar_v2.php';

if (!isset($moduloAtual)) {
    die('Módulo não definido na página');
}

verificarPermissao($moduloAtual);

$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');
$nomeUsuario = $_SESSION['Usuario']['nome'] ?? 'Usuário';
$perfilUsuario = $_SESSION['Usuario']['perfil_nome'] ?? 'Perfil não definido';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($tituloPagina ?? 'Alesys ERP') ?></title>

    <!-- Script do JQUERY -->
    <script src="/js/libs/jquery/jquery-3.7.1.min.js"></script>
    <script src="/js/libs/jquery/jquery.inputmask.js"></script>
    
    <!-- Datatables -->
    <link rel="stylesheet" href="/styles/datatables/datatables.min.css">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/styles/bootstrap/bootstrap.min.css">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- CSS base -->
    <link rel="stylesheet" href="/styles/app.css">

    <!-- CSS especifico -->

    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>

<body class="app-layout">

    <div class="app-wrapper d-flex min-vh-100">

        <!-- Sidebar -->
        <?php renderSidebar($paginaAtual); ?>

        <!-- Área principal -->
        <div class="flex-grow-1 d-flex flex-column">

            <!-- Topbar -->
            <?php renderTopbar(); ?>

            <!-- Conteúdo -->
            <main class="flex-grow-1 container-fluid py-4">

                <?php if (isset($tituloConteudo)): ?>
                    <div class="mb-4">
                        <h1 class="page-title">
                            <?= htmlspecialchars($tituloConteudo) ?>
                        </h1>
                    </div>
                <?php endif; ?>
                 <?= $content ?? '' ?>