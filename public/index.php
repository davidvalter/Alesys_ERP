<?php
//public/index.php
session_start();
require_once __DIR__ . '/conf/autenticacao.php';

// Verificação básica de autenticação
if (isset($_SESSION['Usuario']['logado']) && $_SESSION['Usuario']['logado'] === true) {
    header('Location: dashboard.php');
    exit();
} else {
    header('Location: login.php');
    exit();
}