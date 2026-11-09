<?php
/* /public/financeiro/importar_ofx.php */

session_start();

$moduloAtual = 'financeiro.importacaoofx';
$tituloPagina = 'Importação OFX';
$tituloConteudo = 'Importar Extrato Bancário (OFX)';

require_once '../templates/base_protected_v2.php';
require_once '../../configs/database.php';
?>

<div class="card card-default">

    <!-- HEADER -->
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold">
            Importação de Extrato Bancário
        </h5>

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- BODY -->
    <div class="card-body">

        <!-- INFO -->
        <div class="alert alert-light border mb-4">
            <i class="fas fa-circle-info me-2 text-primary"></i>
            Selecione o arquivo <strong>.OFX</strong> exportado do seu banco.
            O sistema irá processar e preparar os lançamentos para conciliação.
        </div>

        <!-- FORM -->
        <form id="formImportarOfx" enctype="multipart/form-data">

            <div class="d-flex justify-content-center">
                <label class="ofx-upload-box" for="arquivoOfx">
                    <div class="text-center">
                        <i class="fas fa-file-import fa-2x mb-2 text-secondary"></i>
                        <div class="fw-semibold">Clique para selecionar o arquivo OFX</div>
                        <small class="text-muted" id="nomeArquivo"></small>
                    </div>

                    <input type="file" id="arquivoOfx" name="arquivo" accept=".ofx" hidden>
                </label>
            </div>

            <div class="mt-3 text-center">
                <button type="submit" class="btn btn-success" disabled id="btnImportar">
                    <i class="fas fa-upload me-1"></i> Importar OFX
                </button>
            </div>

        </form>

        <!-- RESULTADO -->
        <div id="resultado" class="mt-4"></div>

    </div>
</div>

<link rel="stylesheet" href="/styles/importar_ofx.css">

<?php require_once '../templates/footer_protected_v2.php'; ?>

<script src="/js/importar_ofx.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/importar_ofx.js') ?>"></script>