/* /public/js/mercados.js */

// Verifica se jQuery está carregado antes de executar o código
if (typeof jQuery == 'undefined') {
    console.error('jQuery não foi carregado corretamente');
} else {
    console.log('jQuery carregado com sucesso - versão:', jQuery.fn.jquery);
}

$(document).ready(function () {
    //modal
    const modalEl = document.getElementById('modalMercado');
    const modalMercado = modalEl ? new bootstrap.Modal(modalEl) : null;
    let fluxoFornecedorAleNFE = null;

    window.abrirModalNovoFornecedor = function (dados = {}, aoSalvar = null, aoCancelar = null) {
        if (!modalMercado) return false;
        fluxoFornecedorAleNFE = { aoSalvar, aoCancelar };
        $('#modalTitle').text('Novo Fornecedor');
        $('#formMercado')[0].reset();
        $('#mercadoCodigo').val('');
        $('#nome').val(dados.Nome || '');
        $('#razaoSocial').val(dados.RazaoSocial || '');
        $('#cnpj').val(dados.CNPJ || '').trigger('input');
        $('#inscricaoEstadual').val(dados.InscricaoEstadual || '');
        $('#email').val(dados.Email || '');
        $('#telefone').val(dados.Telefone || '');
        $('#endereco').val(dados.Endereco || '');
        $('#cep').val(dados.CEP || '').trigger('input');
        $('#municipio').val(dados.Municipio || '');
        $('#estado').val(dados.Estado || '');
        modalMercado.show();
        return true;
    };

    $('#modalMercado').on('hidden.bs.modal', function () {
        if (fluxoFornecedorAleNFE) {
            const fluxo = fluxoFornecedorAleNFE;
            fluxoFornecedorAleNFE = null;
            if (typeof fluxo.aoCancelar === 'function') fluxo.aoCancelar();
        }
    });

    // Verifica se InputMask está disponível
    if (typeof $.fn.inputmask == 'undefined') {
        console.error('InputMask não foi carregado corretamente');
    } else {
        console.log('InputMask carregado com sucesso');
        // Aplica máscaras
        $('.cnpj-mask').inputmask('99.999.999/9999-99');
        $('.cep-mask').inputmask('99999-999');
        $('.phone-mask').inputmask('(99) [9]9999-9999');
    }

    // A listagem e exportação só existem na página de mercados.
    // O modal é compartilhado também pela importação AleNFE.
    var table = null;
    if ($('#tabelaMercados').length) {
        table = $('#tabelaMercados').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            },
            scrollX: true,
            autoWidth: false,
            columnDefs: [
                { width: "5%", targets: 0 },
                { width: "25%", targets: 1 },
                { width: "20%", targets: 2 },
                { width: "15%", targets: 3 },
                { width: "20%", targets: 4 },
                { width: "10%", targets: -1, orderable: false }
            ],
            initComplete: function () {
                this.api().columns.adjust();
            }
        });

        $('#btnExportar').click(function () {
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += table.columns().header().to$().map(function () {
                return $(this).text();
            }).get().join(",") + "\n";
            table.rows().data().each(function (row) {
                csvContent += row.join(",") + "\n";
            });
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "mercados_exportados.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        $(window).on('resize', function () {
            table.columns.adjust();
        });

        $('#btnExportar').click(function () {
            $('.buttons-excel').click();
        });
    }

    // Máscaras para os campos
    $('.cnpj-mask').inputmask('99.999.999/9999-99');
    $('.cep-mask').inputmask('99999-999');
    $('.phone-mask').inputmask('(99) [9]9999-9999');

    // Controle do modal - versão com verificação de erros
    $('#btnNovoMercado').on('click', function () {
        try {
            $('#modalTitle').text('Novo Mercado');
            $('#formMercado')[0].reset();
            $('#mercadoCodigo').val('');
            modalMercado.show();
            console.log('Modal aberto com sucesso');
        } catch (e) {
            console.error('Erro ao abrir modal:', e);
        }
    });

    // Editar mercado
    $(document).on('click', '.btn-editar', function () {
        var codigo = $(this).data('id');

        $.ajax({
            url: '/api/get_mercado.php',
            type: 'GET',
            data: { Codigo: codigo },
            dataType: 'json',
            success: function (mercado) {
                $('#modalTitle').text('Editar Mercado');
                $('#mercadoCodigo').val(mercado.Codigo);
                $('#nome').val(mercado.Nome);
                $('#razaoSocial').val(mercado.RazaoSocial);
                $('#cnpj').val(mercado.CNPJ);
                $('#inscricaoEstadual').val(mercado.InscricaoEstadual);
                $('#email').val(mercado.Email);
                $('#endereco').val(mercado.Endereco);
                $('#cep').val(mercado.CEP);
                $('#municipio').val(mercado.Municipio);
                $('#estado').val(mercado.Estado);
                $('#pais').val(mercado.Pais);
                $('#telefone').val(mercado.Telefone);
                $('#matrizFilial').val(mercado.MatrizFilial.toString());

                modalMercado.show();
            },
            error: function (xhr, status, error) {
                console.error("Erro ao carregar mercado:", error);
                alert('Erro ao carregar dados do mercado. Verifique o console para detalhes.');
            }
        });
    });

    // Adicione esta função antes do submit handler
    function validarFormMercado() {
        var isValid = true;
        var $form = $('#formMercado');

        // Limpa erros anteriores
        $form.find('.error-text').remove();
        $form.find('.has-error').removeClass('has-error');

        // Valida campos obrigatórios
        var camposObrigatorios = ['nome', 'razaoSocial', 'cnpj']; // IDs reais dos campos
        camposObrigatorios.forEach(function (campoId) {
            var $input = $('#' + campoId);

            // Verifica se o elemento existe
            if ($input.length === 0) {
                console.error('Campo não encontrado no DOM:', campoId);
                isValid = false;
                return;
            }

            // Obtém o valor e verifica se está preenchido
            var valor = $input.val();
            if (typeof valor !== 'string' || !valor.trim()) {
                $input.parent().addClass('has-error');
                $input.after('<div class="error-text" style="color:#dc3545;font-size:12px;">Este campo é obrigatório</div>');
                isValid = false;
            }
        });

        // Validação específica para CNPJ
        var $cnpj = $('#cnpj');
        if ($cnpj.length > 0) {
            var cnpj = $cnpj.val().replace(/[^\d]/g, '');
            if (cnpj.length !== 14) {
                $cnpj.parent().addClass('has-error');
                $cnpj.after('<div class="error-text" style="color:#dc3545;font-size:12px;">CNPJ inválido (deve ter 14 dígitos)</div>');
                isValid = false;
            }
        } else {
            console.error('Campo CNPJ não encontrado no DOM');
            isValid = false;
        }

        return isValid;
    }


    // Salvar mercado
    $('#formMercado').submit(function (e) {
        e.preventDefault();

        // Garante que MatrizFilial tenha um valor
        if (!$('#matrizFilial').val()) {
            $('#matrizFilial').val('1'); // Valor padrão para Matriz
        }

        if (!validarFormMercado()) {
            showAlert('Por favor, corrija os erros no formulário', 'error', 5000);
            return;
        }

        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');

        // Coleta os dados do formulário
        var formData = {
            Codigo: $('#mercadoCodigo').val(),
            Nome: $('#nome').val(),
            RazaoSocial: $('#razaoSocial').val(),
            CNPJ: $('#cnpj').val(),
            InscricaoEstadual: $('#inscricaoEstadual').val(),
            Email: $('#email').val(),
            Endereco: $('#endereco').val(),
            CEP: $('#cep').val(),
            Municipio: $('#municipio').val(),
            Estado: $('#estado').val(),
            Pais: $('#pais').val(),
            MatrizFilial: $('#matrizFilial').val(),
            Telefone: $('#telefone').val()
        };

        console.log('Dados a serem enviados:', formData);

        var method = 'POST';

        $.ajax({
            url: '/api/save_mercado.php',
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    if (fluxoFornecedorAleNFE) {
                        const fluxo = fluxoFornecedorAleNFE;
                        fluxoFornecedorAleNFE = null;
                        $('#modalMercado').one('hidden.bs.modal', function () {
                            if (typeof fluxo.aoSalvar === 'function') {
                                fluxo.aoSalvar({ ...formData, Codigo: response.Codigo });
                            }
                        });
                        modalMercado.hide();
                    } else {
                        modalMercado.hide();
                        location.reload();
                    }
                } else {
                    showAlert('Erro: ' + response.message, 'error', 5000);
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
                }
            },
            error: function (xhr) {
                var errorMsg = 'Erro ao salvar mercado';

                if (xhr.status === 409) {
                    // 🚨 Tratamento especial para duplicidade (ex: CNPJ já cadastrado)
                    var json = xhr.responseJSON || {};
                    errorMsg = json.message || 'Já existe um mercado cadastrado com este CNPJ.';

                    // Destaca o campo CNPJ
                    var $cnpj = $('#cnpj');
                    $cnpj.parent().addClass('has-error');
                    if ($cnpj.next('.error-text').length === 0) {
                        $cnpj.after('<div class="error-text" style="color:#dc3545;font-size:12px;">' + errorMsg + '</div>');
                    }
                    $cnpj.focus();
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else {
                    try {
                        var json = JSON.parse(xhr.responseText);
                        if (json && json.message) {
                            errorMsg = json.message;
                        }
                    } catch (e) { }
                }

                showAlert(errorMsg, 'error', 6000);
                $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
            }
        });
    });


    // Desativar mercado (soft delete)
    $(document).on('click', '.btn-desativar', function () {
        if (confirm('Tem certeza que deseja desativar este mercado? O registro será mantido mas não aparecerá nas listagens.')) {
            var codigo = $(this).data('id');
            var $row = $(this).closest('tr');

            $.ajax({
                url: '/api/desativar_mercado.php',
                type: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify({ Codigo: codigo }),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(400, function () {
                            $(this).remove();
                        });
                        showAlert(response.message, 'success');
                    } else {
                        showAlert(response.message, 'error');
                    }
                },
                error: function () {
                    showAlert('Erro ao desativar mercado', 'error');
                }
            });
        }
    });

    // Excluir mercado fisicamente
    $(document).on('click', '.btn-excluir', function () {
        if (confirm('ATENÇÃO: Esta ação irá REMOVER PERMANENTEMENTE o mercado do banco de dados. Todos os registros associados serão perdidos. Deseja continuar?')) {
            var codigo = $(this).data('id');
            var $row = $(this).closest('tr');
            var $btn = $(this);

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '/api/delete_mercado.php',  // Atualizado para delete_mercado.php
                type: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ Codigo: codigo }),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(400, function () {
                            $(this).remove();
                        });
                        showAlert(response.message, 'success');
                    } else {
                        showAlert(response.message, 'error', 6000);
                        $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                },
                error: function (xhr, status, error) {
                    var errorMsg = 'Erro ao excluir mercado';
                    try {
                        var json = xhr.responseJSON;
                        if (json) {
                            errorMsg = json.message || errorMsg;
                            if (json.error_details) {
                                console.error('Detalhes do erro:', json.error_details);
                            }
                        }
                    } catch (e) {
                        console.error('Erro ao processar resposta:', e);
                    }

                    showAlert(errorMsg + ' (Verifique o console para detalhes)', 'error', 6000);
                    $btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');

                    console.log('Resposta completa:', xhr.responseText);
                }
            });
        }
    });

    // Função para validar CNPJ
    function validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/[^\d]+/g, '');

        if (cnpj == '') return false;
        if (cnpj.length != 14) return false;

        // Elimina CNPJs inválidos conhecidos
        if (/^(\d)\1+$/.test(cnpj)) return false;

        // Valida DVs
        var tamanho = cnpj.length - 2;
        var numeros = cnpj.substring(0, tamanho);
        var digitos = cnpj.substring(tamanho);
        var soma = 0;
        var pos = tamanho - 7;

        for (var i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }

        var resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(0)) return false;

        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;

        for (var i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }

        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos.charAt(1)) return false;

        return true;
    }

    // Função para mostrar alerta
    function showAlert(message, type) {
        var $alert = $('<div class="alert-message ' + type + '">' + message + '</div>');
        $('body').append($alert);
        $alert.fadeIn();

        setTimeout(function () {
            $alert.fadeOut(function () {
                $(this).remove();
            });
        }, 3000);
    }

});