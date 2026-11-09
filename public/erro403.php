<?php
http_response_code(403);
session_start();

$moduloAtual = 'dashboard';
$tituloPagina = 'Acesso Negado';
$tituloConteudo = 'Acesso Negado';

$mensagemPadrao = 'Você não tem permissão para acessar este recurso.';
$mensagem = $_SESSION['mensagem_erro'] ?? $mensagemPadrao;
unset($_SESSION['mensagem_erro']);

require_once __DIR__ . '/templates/base_protected_v2.php';
?>

<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-xl-6 col-lg-7 col-md-9">

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div class="card-header bg-danger text-white py-4 text-center border-0">
                    <div class="mb-3">
                        <i class="fas fa-ban fa-3x"></i>
                    </div>
                    <h2 class="fw-bold mb-0">Acesso Negado</h2>
                    <small class="opacity-75">Erro 403</small>
                </div>

                <!-- BODY -->
                <div class="card-body p-5 text-center">

                    <p class="fs-5 text-muted mb-4">
                        <?= htmlspecialchars($mensagem) ?>
                    </p>

                    <p class="text-secondary mb-4">
                        Se você acredita que isso é um erro, entre em contato com o administrador do sistema.
                    </p>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="/dashboard.php" class="btn btn-success px-4">
                            <i class="fas fa-arrow-left me-2"></i>
                            Voltar ao Dashboard
                        </a>

                        <button onclick="history.back()" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-undo me-2"></i>
                            Página Anterior
                        </button>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="card-footer bg-light text-center py-3 border-0">
                    <small class="text-muted">
                        Alecrim Restaurante • Segurança e Controle de Acesso
                    </small>
                </div>

            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/templates/footer_protected_v2.php'; ?>