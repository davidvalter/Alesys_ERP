// public/js/importacao_ofx_conferencia.js

$(function () {

    // IMPORTACAO_ID vem do PHP (script inline)
    const importacaoId = typeof IMPORTACAO_ID !== 'undefined'
        ? IMPORTACAO_ID
        : new URLSearchParams(window.location.search).get('id');

    const tabela = $('#tabelaConferencia tbody');
    const btnConfirmar = $('#btnConfirmar');

    // Marcar / desmarcar todos
    $('#checkAll').on('change', function () {
        $('.check-linha').prop('checked', this.checked);
    });

    // Categoria global
    $('#categoriaGlobal').on('change', function () {
        const valor = $(this).val();
        if (!valor) return;

        $('#tabelaConferencia tbody tr').each(function () {
            const select = $(this).find('select.categoria');
            if (!select.val()) {
                select.val(valor);
            }
        });
    });

    // Forma de pagamento global
    $('#formaPagamentoGlobal').on('change', function () {
        const valor = $(this).val();
        if (!valor) return;

        $('#tabelaConferencia tbody tr').each(function () {
            const select = $(this).find('select.forma-pagamento');
            if (!select.val()) {
                select.val(valor);
            }
        });
    });



    // ==============================
    // Carrega dados da importação
    // ==============================
    $.getJSON('/api/importar_ofx_detalhes.php', { id: importacaoId }, function (res) {

        if (!res.success) {
            alert(res.message || 'Erro ao carregar importação');
            return;
        }

        tabela.empty();

        res.lancamentos.forEach(l => {

            tabela.append(`
                <tr data-id="${l.Id}">
                    <td>${formatarData(l.DataLancamento)}</td>

                    <td>
                        <span class="badge ${l.Tipo === 'entrada' ? 'bg-success' : 'bg-danger'}">
                            ${l.Tipo}
                        </span>
                    </td>

                    <td>
                        <input type="text"
                               class="form-control form-control-sm descricao"
                               value="${escapeHtml(l.Descricao || '')}">
                    </td>

                    <td>
                        <select class="form-select form-select-sm categoria">
                            <option value="">Selecione</option>
                            ${renderOptions(res.categorias)}
                        </select>
                    </td>

                    <td>
                        <select class="form-select form-select-sm forma-pagamento">
                            <option value="">Selecione</option>
                            ${renderOptions(res.formas_pagamento)}
                        </select>
                    </td>

                    <td class="text-end">
                        ${Number(l.Valor).toFixed(2)}
                    </td>

                    <td>
                        <button class="btn btn-sm btn-outline-danger btnIgnorar">
                            Ignorar
                        </button>
                    </td>
                </tr>
            `);
        });

    });

    // ==============================
    // Ignorar lançamento (remove da tela)
    // ==============================
    $(document).on('click', '.btnIgnorar', function () {
        $(this).closest('tr').remove();
    });

    // ==============================
    // Confirmar lançamentos
    // ==============================
    btnConfirmar.on('click', function () {

        const lancamentos = [];
        let erro = false;

        tabela.find('tr').each(function () {

            const tr = $(this);

            // 🔎 Verifica se o lançamento está selecionado
            const selecionado = tr.find('.chk-lancamento').is(':checked');

            // ❌ Ignora linhas não selecionadas
            if (!selecionado) {
                tr.removeClass('table-danger');
                return;
            }

            const id = tr.data('id');
            const descricao = tr.find('.descricao').val()?.trim();
            const categoriaId = tr.find('.categoria').val();
            const formaPagamentoId = tr.find('.forma-pagamento').val();

            // 🔐 Validação SOMENTE dos selecionados
            if (!categoriaId || !formaPagamentoId) {
                tr.addClass('table-danger');
                erro = true;
                return;
            } else {
                tr.removeClass('table-danger');
            }

            lancamentos.push({
                id: id,
                descricao: descricao,
                categoria_id: categoriaId,
                forma_pagamento_id: formaPagamentoId
            });
        });

        if (lancamentos.length === 0) {
            alert('Selecione ao menos um lançamento para confirmar.');
            return;
        }

        if (erro) {
            alert('Existem lançamentos selecionados sem categoria ou forma de pagamento.');
            return;
        }

        if (!confirm('Deseja confirmar esses lançamentos?')) {
            return;
        }

        btnConfirmar
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Processando...');

        $.ajax({
            url: '/api/importacao_ofx_confirmar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                importacao_id: importacaoId,
                lancamentos: lancamentos
            }),

            success: function (res) {

                if (!res.success) {
                    alert(res.message || 'Erro ao confirmar importação');
                    return;
                }

                alert(res.message || 'Lançamentos confirmados com sucesso.');

                // Ajuste conforme seu fluxo
                window.location.href = '/financeiro/';
            },

            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Erro ao confirmar importação');
            },

            complete: function () {
                btnConfirmar
                    .prop('disabled', false)
                    .html('<i class="fas fa-check"></i> Confirmar lançamentos');
            }
        });

    });


    // ==============================
    // Cancelar lançamentos
    // ==============================
    $('#btnCancelar').on('click', function () {

        if (!confirm('Tem certeza que deseja CANCELAR esta importação? Todos os lançamentos serão removidos.')) {
            return;
        }

        $.ajax({
            url: '/api/importacao_ofx_cancelar.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                importacao_id: IMPORTACAO_ID
            }),
            success: function (res) {
                alert(res.message || 'Importação cancelada com sucesso');
                window.location.href = '/financeiro/importar_ofx.php';
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Erro ao cancelar importação');
            }
        });
    });


    // ==============================
    // Helpers
    // ==============================
    function renderOptions(lista) {
        return lista.map(i =>
            `<option value="${i.Id}">${escapeHtml(i.Nome)}</option>`
        ).join('');
    }

    function formatarData(data) {
        if (!data) return '';
        const p = data.split('-');
        return `${p[2]}/${p[1]}/${p[0]}`;
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

});
