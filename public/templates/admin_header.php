<?php
// public/templates/admin_header.php

// Inclui os componentes necessários ANTES de usar
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/topbar.php';

// Verifica se as variáveis necessárias estão definidas
$paginaAtual = $paginaAtual ?? basename($_SERVER['PHP_SELF'], '.php');
$nomeUsuario = $_SESSION['Usuario']['nome'] ?? 'Administrador';
$perfilUsuario = $_SESSION['Usuario']['perfil_nome'] ?? 'Perfil não definido';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Admin') ?></title>
    <link rel="stylesheet" href="<?= url('styles/base.css') ?>">
    <link rel="stylesheet" href="<?= url('styles/layout.css') ?>">
    <link rel="stylesheet" href="<?= url('styles/components.css') ?>">
    <!-- Remova ou corrija o admin.css se não existir -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="admin layout-2cols">
    <?php renderTopbar(); ?>
    <?php renderSidebar($paginaAtual); ?>
    
    <main class="main-content admin-content">
        <div class="admin-header">
            <h1><?= htmlspecialchars($tituloConteudo ?? 'Administração') ?></h1>
            
            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['sucesso'] ?>
                    <?php unset($_SESSION['sucesso']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['erro'] ?>
                    <?php unset($_SESSION['erro']); ?>
                </div>
            <?php endif; ?>
        </div>