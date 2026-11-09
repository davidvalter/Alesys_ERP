(function ($, window, document) {
    "use strict";
    const MODULE_SELECTOR = {
        form: '#formProduto',
        table: '#tabelaProdutos',
        modal: '#modalProduto',
        btnNovo: '#btnNovoProduto',
        filtroCategoria: '#filtroCategoria',
        filtroNome: '#filtroNome'
    };
    let dataTable;
    let modalProduto;
    let modalBuscaNcm;          // <-- FALTAVA ESTA DECLARAÇÃO
    let ncmPaginacao = { termo: '', pagina: 1, totalPaginas: 1 };

    function init() {
        modalProduto = new bootstrap.Modal(document.getElementById('modalProduto'));
        modalBuscaNcm = new bootstrap.Modal(document.getElementById('modalBuscaNcm'));
        setupDataTable();
        setupMasks();
        setupEventHandlers();
        console.log('Módulo de Produtos inicializado');

        // CORREÇÃO: modais empilhados (Produto + Busca NCM)
        // Ao abrir a busca, esconde o modal do produto; ao fechar, devolve.
        $('#modalBuscaNcm').on('show.bs.modal', function () {
            $('#modalProduto').modal('hide');
        });
        $('#modalBuscaNcm').on('hidden.bs.modal', function () {
            $('#modalProduto').modal('show');
        });

    }

    function setupDataTable() {
        if ($('#tabelaProdutos tbody tr').length === 0) {
            $('#tabelaProdutos tbody').append('<tr><td colspan="8" style="text-align:center">Nenhum produto encontrado</td></tr>');
        }
        dataTable = $(MODULE_SELECTOR.table).DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json' },
            responsive: true,
            columnDefs: [
                { responsivePriority: 1, targets: 1 },
                { responsivePriority: 2, targets: -1 },
                { targets: [0, 2, 3, 4, 5, 6, 7], orderable: true, searchable: true }
            ]
        });
        $(MODULE_SELECTOR.filtroCategoria).on('change', function () {
            dataTable.column(3).search(this.value).draw();
        });
        $(MODULE_SELECTOR.filtroNome).on('keyup', function () {
            dataTable.column(1).search(this.value).draw();
        });
    }

    function setupMasks() {
        $('.barcode-mask').inputmask('9999999999999', {
            placeholder: '',
            clearMaskOnLostFocus: false
        });
    }

    function setupEventHandlers() {
        $(MODULE_SELECTOR.btnNovo).on('click', showModal);
        $(MODULE_SELECTOR.form).on('submit', handleFormSubmit);
        $(document)
            .on('click', '.btn-editar', handleEdit)
            .on('click', '.btn-desativar', handleDeactivate)
            .on('click', '.btn-excluir', handleDelete);
        $('#tipoProduto').on('change', toggleCamposFiscais);

        // Modal de NCM
        $('#btnBuscarNcm').on('click', abrirModalNcm);
        $('#btnPesquisarNcm').on('click', pesquisarNcm);
        $('#ncmBusca').on('keypress', function (e) {
            if (e.which === 13) pesquisarNcm();
        });
        $('#ncmAnterior').on('click', function () { paginarNcm(-1); });
        $('#ncmProximo').on('click', function () { paginarNcm(1); });
        $(document).on('click', '.btn-selecionar-ncm', selecionarNcm);
    }

    function showModal() {
        try {
            $('#modalTitle').text('Novo Produto');
            $(MODULE_SELECTOR.form)[0].reset();
            $('#produtoCodigo').val('');
            $('#unidadeMedida').val('UN');
            $('#tipoProduto').val('insumo');
            $('#categoriaVenda').val('');
            $('#ativo').prop('checked', true);
            $('#ncm').val('');
            $('#ncmDescricao').text('');
            $('#cest').html('<option value="">— Selecione o NCM primeiro —</option>');
            $('#cestDescricao').text('');
            toggleCamposFiscais();
            modalProduto.show();
        } catch (e) {
            console.error('Erro ao abrir modal:', e);
            showAlert('Erro ao abrir formulário', 'error');
        }
    }

    function toggleCamposFiscais() {
        var isVendavel = $('#tipoProduto').val() === 'vendavel';
        $('#ncm, #cfop, #csosn, #cest').prop('disabled', !isVendavel);
        if (!isVendavel) {
            $('#cfop, #csosn').val('');
            $('#ncm').val('');
            $('#ncmDescricao').text('');
            $('#cest').val('');
            $('#cestDescricao').text('');
        }
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        if (!validarFormProduto()) {
            showAlert('Por favor, corrija os erros no formulário', 'error', 5000);
            return;
        }
        var $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Salvando...');
        var formData = {
            Codigo: $('#produtoCodigo').val(),
            Nome: $('#nome').val().trim(),
            Descricao: $('#descricao').val().trim(),
            CodigoBarras: $('#codigoBarras').val().replace(/[^\d]/g, ''),
            UnidadeMedida: $('#unidadeMedida').val(),
            Categoria: $('#categoria').val().trim() || null,
            TipoProduto: $('#tipoProduto').val(),
            CategoriaVenda: $('#categoriaVenda').val() || null,
            NCM: $('#ncm').val() || null,
            CFOP: $('#cfop').val() || null,
            CSOSN: $('#csosn').val() || null,
            CEST: $('#cest').val() || null,
            Ativo: $('#ativo').is(':checked') ? 1 : 0
        };
        var method = formData.Codigo ? 'PUT' : 'POST';
        $.ajax({
            url: '/api/save_produto.php',
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    modalProduto.hide();
                    location.reload();
                } else {
                    showAlert('Erro: ' + response.message, 'error');
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
                }
            },
            error: function (xhr) {
                var errorMsg = 'Erro na requisição';
                try {
                    var json = xhr.responseJSON;
                    if (json && json.error_details) errorMsg += '\nDetalhes: ' + json.error_details;
                } catch (e) { }
                showAlert(errorMsg, 'error');
                $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Salvar');
            }
        });
    }

    function validarFormProduto() {
        var isValid = true;
        var $form = $(MODULE_SELECTOR.form);
        $form.find('.error-text').remove();
        $form.find('.has-error').removeClass('has-error');
        if (!$('#nome').val().trim()) {
            $('#nome').parent().addClass('has-error');
            $('#nome').after('<div class="error-text">Nome é obrigatório</div>');
            isValid = false;
        }
        if (!$('#unidadeMedida').val()) {
            $('#unidadeMedida').parent().addClass('has-error');
            $('#unidadeMedida').after('<div class="error-text">Unidade é obrigatória</div>');
            isValid = false;
        }
        if (!$('#tipoProduto').val()) {
            $('#tipoProduto').parent().addClass('has-error');
            $('#tipoProduto').after('<div class="error-text">Tipo de produto é obrigatório</div>');
            isValid = false;
        }
        if ($('#tipoProduto').val() === 'vendavel') {
            if (!$('#ncm').val()) {
                $('#ncm').parent().addClass('has-error');
                $('#ncm').after('<div class="error-text">NCM é obrigatório para produtos vendáveis</div>');
                isValid = false;
            }
            if (!$('#cfop').val()) {
                $('#cfop').parent().addClass('has-error');
                $('#cfop').after('<div class="error-text">CFOP é obrigatório para produtos vendáveis</div>');
                isValid = false;
            }
            if (!$('#csosn').val()) {
                $('#csosn').parent().addClass('has-error');
                $('#csosn').after('<div class="error-text">CSOSN é obrigatório para produtos vendáveis (Simples Nacional)</div>');
                isValid = false;
            }
            if ($('#tipoProduto').val() === 'vendavel' && ['201', '202', '203'].indexOf($('#csosn').val()) !== -1) {
                if (!$('#cest').val()) {
                    $('#cest').parent().addClass('has-error');
                    $('#cest').after('<div class="error-text">CEST é obrigatório para produtos com ICMS-ST</div>');
                    isValid = false;
                }
            }
        }
        return isValid;
    }

    function handleEdit() {
        var codigo = $(this).data('id');
        $.ajax({
            url: '/api/get_produto.php',
            type: 'GET',
            data: { Codigo: codigo },
            dataType: 'json',
            success: function (produto) {
                $('#modalTitle').text('Editar Produto');
                $('#produtoCodigo').val(produto.Codigo);
                $('#nome').val(produto.Nome);
                $('#descricao').val(produto.Descricao);
                $('#codigoBarras').val(produto.CodigoBarras);
                $('#unidadeMedida').val(produto.UnidadeMedida);
                $('#categoria').val(produto.Categoria);
                $('#tipoProduto').val(produto.TipoProduto);
                $('#categoriaVenda').val(produto.CategoriaVenda || '');
                $('#ncm').val(produto.NCM);
                $('#ncmDescricao').text(produto.NCM_Descricao || '');
                $('#cfop').val(produto.CFOP);
                $('#csosn').val(produto.CSOSN);
                $('#cestDescricao').text(produto.CEST_Descricao || '');
                carregarCest(produto.NCM, produto.CEST);
                $('#ativo').prop('checked', produto.Ativo == 1);
                toggleCamposFiscais();
                modalProduto.show();
            },
            error: function (xhr, status, error) {
                console.error("Erro ao carregar produto:", error);
                showAlert('Erro ao carregar dados do produto. Verifique o console para detalhes.', 'error');
            }
        });
    }

    // ==================== MODAL DE BUSCA NCM ====================
    function abrirModalNcm() {
        $('#ncmBusca').val('');
        ncmPaginacao = { termo: '', pagina: 1, totalPaginas: 1 };
        $('#ncmResultados').html('<tr><td colspan="3" class="text-center text-muted">Digite um termo e clique em Pesquisar</td></tr>');
        $('#ncmContador').text('');
        $('#ncmAnterior, #ncmProximo').prop('disabled', true);
        modalBuscaNcm.show();
    }

    function pesquisarNcm() {
        ncmPaginacao.termo = $('#ncmBusca').val().trim();
        ncmPaginacao.pagina = 1;
        carregarNcm();
    }

    function carregarNcm() {
        $('#ncmResultados').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>');
        $.ajax({
            url: '/api/get_ncm.php',   // <-- endpoint renomeado
            type: 'GET',
            data: { termo: ncmPaginacao.termo, pagina: ncmPaginacao.pagina },
            dataType: 'json',
            success: function (res) {
                if (!res.success) {
                    $('#ncmResultados').html('<tr><td colspan="3" class="text-center text-danger">' + res.message + '</td></tr>');
                    return;
                }
                renderNcm(res.dados, res.total, res.pagina, res.totalPaginas);
            },
            error: function () {
                $('#ncmResultados').html('<tr><td colspan="3" class="text-center text-danger">Erro ao buscar NCM</td></tr>');
            }
        });
    }

    function renderNcm(lista, total, pagina, totalPaginas) {
        ncmPaginacao.pagina = pagina;
        ncmPaginacao.totalPaginas = totalPaginas;
        if (!lista.length) {
            $('#ncmResultados').html('<tr><td colspan="3" class="text-center text-muted">Nenhum NCM encontrado</td></tr>');
        } else {
            var html = '';
            $.each(lista, function (_, item) {
                html += '<tr>' +
                    '<td><strong>' + item.Codigo + '</strong></td>' +
                    '<td>' + item.Descricao + '</td>' +
                    '<td class="text-end"><button class="btn btn-sm btn-success btn-selecionar-ncm" data-codigo="' + item.Codigo + '" data-descricao="' + item.Descricao + '"><i class="fas fa-check"></i></button></td>' +
                    '</tr>';
            });
            $('#ncmResultados').html(html);
        }
        $('#ncmContador').text(total + ' resultado(s) - Página ' + pagina + ' de ' + (totalPaginas || 1));
        $('#ncmAnterior').prop('disabled', pagina <= 1);
        $('#ncmProximo').prop('disabled', pagina >= totalPaginas);
    }

    function paginarNcm(dir) {
        var novaPagina = ncmPaginacao.pagina + dir;
        if (novaPagina < 1 || novaPagina > ncmPaginacao.totalPaginas) return;
        ncmPaginacao.pagina = novaPagina;
        carregarNcm();
    }

    function selecionarNcm() {
        $('#ncm').val($(this).data('codigo'));
        $('#ncmDescricao').text($(this).data('descricao'));
        modalBuscaNcm.hide();
    }

    // ==================== AÇÕES ====================
    function handleDeactivate() {
        if (confirm('Tem certeza que deseja desativar este produto? O registro será mantido mas não aparecerá nas listagens.')) {
            var codigo = $(this).data('id');
            var $row = $(this).closest('tr');
            $.ajax({
                url: '/api/desativar_produto.php',
                type: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify({ Codigo: codigo }),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(400, function () { dataTable.row($(this)).remove().draw(); });
                        showAlert(response.message, 'success');
                    } else {
                        showAlert(response.message, 'error');
                    }
                },
                error: function () { showAlert('Erro ao desativar produto', 'error'); }
            });
        }
    }

    function handleDelete() {
        if (confirm('ATENÇÃO: Esta ação irá REMOVER PERMANENTEMENTE o produto do banco de dados. Todos os registros associados serão perdidos. Deseja continuar?')) {
            var codigo = $(this).data('id');
            var $row = $(this).closest('tr');
            $.ajax({
                url: '/api/delete_produto.php',
                type: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ Codigo: codigo }),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(400, function () { dataTable.row($(this)).remove().draw(); });
                        showAlert(response.message, 'success');
                    } else {
                        showAlert(response.message, 'error');
                    }
                },
                error: function () { showAlert('Erro ao excluir produto', 'error'); }
            });
        }
    }

    function showAlert(message, type, duration = 3000) {
        var $alert = $('<div class="alert-message ' + type + '">' + message + '</div>');
        $('body').append($alert);
        $alert.fadeIn();
        setTimeout(function () { $alert.fadeOut(function () { $(this).remove(); }); }, duration);
    }

    function selecionarNcm() {
        var codigo = $(this).data('codigo');
        var descricao = $(this).data('descricao');
        $('#ncm').val(codigo);
        $('#ncmDescricao').text(descricao);
        carregarCest(codigo);   // <-- carrega os CESTs válidos para este NCM
        modalBuscaNcm.hide();
    }

    function carregarCest(ncm, cestSelecionado) {
        $('#cest').html('<option value="">Carregando...</option>');
        $('#cestDescricao').text('');
        $.ajax({
            url: '/api/get_cest.php',
            type: 'GET',
            data: { ncm: ncm },
            dataType: 'json',
            success: function (res) {
                if (!res.success || !res.dados.length) {
                    $('#cest').html('<option value="">Nenhum CEST para este NCM</option>');
                    return;
                }
                var html = '<option value="">— Selecione —</option>';
                $.each(res.dados, function (_, item) {
                    var codigo = item.Codigo || item.CEST || item.codigo || item.cest || item.CodigoCEST || '';
                    var descricao = item.Descricao || item.descricao || item.DescricaoCEST || '';
                    html += '<option value="' + codigo + '">' + codigo + ' - ' + descricao + '</option>';
                });
                $('#cest').html(html);
                // Pré-seleciona o CEST salvo (usado na edição)
                if (cestSelecionado) {
                    $('#cest').val(cestSelecionado);
                }
            },
            error: function () {
                $('#cest').html('<option value="">Erro ao carregar CEST</option>');
            }
        });
    }



    $(document).ready(init);
})(jQuery, window, document);