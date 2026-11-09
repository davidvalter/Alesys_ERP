// public/js/importar_ofx.js

$(function () {

    const inputFile = $('#arquivoOfx');
    const btnImportar = $('#btnImportar');
    const nomeArquivo = $('#nomeArquivo');
    const resultado = $('#resultado');

    let transacoesImportadas = [];
    let dadosOfx = {}; // ← guarda banco, conta, agencia

    // ==============================
    // Seleção do arquivo
    // ==============================
    inputFile.on('change', function () {
        resultado.html('');
        const file = this.files[0];

        if (!file) {
            btnImportar.prop('disabled', true);
            nomeArquivo.text('');
            $('.ofx-upload-box').removeClass('active');
            return;
        }

        if (!file.name.toLowerCase().endsWith('.ofx')) {
            mostrarErro('Selecione um arquivo OFX válido.');
            inputFile.val('');
            btnImportar.prop('disabled', true);
            nomeArquivo.text('');
            return;
        }

        nomeArquivo.text(file.name);
        btnImportar.prop('disabled', false);
        $('.ofx-upload-box').addClass('active');
    });

    // ==============================
    // Submit do formulário (Etapa 1)
    // ==============================
    $('#formImportarOfx').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        btnImportar
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-1"></i> Processando...');

        $.ajax({
            url: '/api/log_importar_ofx.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            success: function (res) {
                console.log('Resposta da importação OFX:', res);

                if (!res.success) {
                    mostrarErro(res.message || 'Erro ao importar OFX');
                    return;
                }

                if (!Array.isArray(res.transacoes) || res.transacoes.length === 0) {
                    mostrarErro('Nenhuma transação encontrada no arquivo OFX.');
                    return;
                }

                // 🔐 guarda dados do OFX
                dadosOfx = {
                    banco: res.banco,
                    conta: res.conta,
                    agencia: res.agencia
                };

                transacoesImportadas = res.transacoes;

                resultado.html(`
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        ${res.transacoes.length} lançamentos carregados. Processando...
                    </div>
                `);

                // 🔗 Etapa 2
                processarImportacao();
            },

            error: function (xhr) {
                mostrarErro(xhr.responseJSON?.message || 'Erro ao importar OFX');
            },

            complete: function () {
                btnImportar
                    .prop('disabled', false)
                    .html('<i class="fas fa-upload"></i> Importar OFX');
            }
        });
    });

    // ==============================
    // Etapa 2 - Processamento
    // ==============================
    function processarImportacao() {

        const payload = {
            nome_arquivo: nomeArquivo.text(),
            banco: dadosOfx.banco,
            conta: dadosOfx.conta,
            transacoes: transacoesImportadas
        };

        $.ajax({
            url: '/api/importar_ofx_processar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),

            success: function (res) {
                console.log('Resultado do processamento:', res);

                if (!res.success) {
                    mostrarErro(res.message || 'Erro ao processar importação');
                    return;
                }

                // 🚀 Redireciona corretamente
                window.location.href =
                    `/financeiro/importacao_ofx_conferencia.php?id=${res.resumo.importacao_id}`;
            },

            error: function (xhr) {
                mostrarErro(xhr.responseJSON?.message || 'Erro ao processar importação');
            }
        });
    }

    // ==============================
    // Helper de erro
    // ==============================
    function mostrarErro(msg) {
        resultado.html(`
            <div class="alert alert-danger">
                ${msg}
            </div>
        `);
    }

});
