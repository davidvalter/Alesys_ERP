/* /public/js/conciliacao.js
 * Conciliação: FinanceiroLancamentos (tabela central) x NFe.
 * Base de valores: FinanceiroLancamentos.ValorLiquido x NFe.ValorTotal (ou saldo parcial).
 * A conciliação é camada de registro/auditoria: não altera o lançamento.
 */

(function () {
    'use strict';

    const API = '/api/conciliacao.php';

    const state = {
        lancamentos: [],
        nfes: [],
        sugestoes: [],
        lancamentoSelecionado: null,
        nfesSelecionadas: new Map(), // nfeId -> valor alocado
        estornoId: null,
    };

    const $listaLanc = $('#conc-lista-lancamento');
    const $listaNfe = $('#conc-lista-nfe');

    /* ================= UTILITÁRIOS ================= */

    function brl(v) {
        return (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function dataBR(iso) {
        if (!iso) return '—';
        const d = new Date(String(iso).replace(' ', 'T'));
        return isNaN(d) ? iso : d.toLocaleDateString('pt-BR');
    }

    function normalizar(r) {
        if (r && r.sucesso === undefined && r.success !== undefined) r.sucesso = r.success;
        if (r && r.mensagem === undefined && r.message !== undefined) r.mensagem = r.message;
        return r;
    }

    function apiGet(params) {
        return $.getJSON(API, params).then(normalizar);
    }

    function apiPost(payload) {
        // A acao vai na query string: com contentType JSON, o PHP
        // não popula o $_POST, e a API lê a ação via $_GET.
        const url = payload.acao
            ? API + '?acao=' + encodeURIComponent(payload.acao)
            : API;
        return $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
        }).then(normalizar);
    }

    /* ================= CARGA ================= */

    function carregarTudo() {
        carregarResumo();
        carregarContas();
        carregarLancamentos();
        carregarNfes();
        carregarHistorico();
    }

    function carregarResumo() {
        apiGet({ acao: 'resumo' }).done(function (r) {
            if (!r.sucesso) return;
            $('#conc-kpi-lancamentos').text(r.resumo.lancamentos_conciliados);
            $('#conc-kpi-nfe').text(r.resumo.nfe_conciliadas);
            $('#conc-kpi-conciliado').text(brl(r.resumo.conciliado_mes));
            $('#conc-kpi-vinculos').text(r.resumo.vinculos);
        });
    }

    function carregarContas() {
        apiGet({ acao: 'contas' }).done(function (r) {
            if (!r.sucesso) return;
            const $sel = $('#conc-conta');
            $sel.find('option:not(:first)').remove();
            r.contas.forEach(function (c) {
                $sel.append($('<option>').val(c.Id).text(c.Nome));
            });
        });
    }

    function carregarLancamentos() {
        $('#conc-loading-lancamento').removeClass('d-none');
        apiGet({
            acao: 'lancamentos',
            conta_id: $('#conc-conta').val() || '',
            data_ini: $('#conc-data-ini').val() || '',
            data_fim: $('#conc-data-fim').val() || '',
        }).done(function (r) {
            state.lancamentos = r.sucesso ? r.lancamentos : [];
            renderLancamentos();
            $('#conc-badge-lancamentos').text(state.lancamentos.length + ' pendentes');
        }).fail(function () {
            $listaLanc.html('<div class="text-center text-danger py-4">Erro ao carregar lançamentos.</div>');
        }).always(function () {
            $('#conc-loading-lancamento').addClass('d-none');
        });
    }

    function carregarNfes() {
        $('#conc-loading-nfe').removeClass('d-none');
        apiGet({ acao: 'nfe' }).done(function (r) {
            state.nfes = r.sucesso ? r.nfes : [];
            renderNfes();
            $('#conc-badge-nfe').text(state.nfes.length + ' notas');
        }).fail(function () {
            $listaNfe.html('<div class="text-center text-danger py-4">Erro ao carregar NF-e.</div>');
        }).always(function () {
            $('#conc-loading-nfe').addClass('d-none');
        });
    }

    function carregarHistorico() {
        apiGet({ acao: 'historico' }).done(function (r) {
            if (!r.sucesso) return;
            const $tb = $('#conc-historico-tbody').empty();
            if (!r.historico.length) {
                $tb.append('<tr><td colspan="7" class="text-center text-muted py-3">Nenhuma conciliação registrada.</td></tr>');
                return;
            }
            r.historico.forEach(function (h) {
                const badge = h.Status === 'confirmada'
                    ? '<span class="badge bg-success">Confirmada</span>'
                    : '<span class="badge bg-secondary">Estornada</span>';
                const acao = h.Status === 'confirmada'
                    ? '<button class="btn btn-sm btn-outline-danger conc-btn-estornar" data-id="' + h.Id + '" title="Estornar"><i class="fas fa-undo"></i></button>'
                    : '';
                $tb.append(
                    '<tr>' +
                    '<td>' + h.Id + '</td>' +
                    '<td>' + dataBR(h.DataConciliacao) + '</td>' +
                    '<td>' + (h.Usuario || '—') + '</td>' +
                    '<td class="text-center">' + (h.Itens || 0) + '</td>' +
                    '<td class="text-end">' + brl(h.Total) + '</td>' +
                    '<td>' + badge + '</td>' +
                    '<td class="text-end">' + acao + '</td>' +
                    '</tr>'
                );
            });
        }).fail(function () {
            $('#conc-historico-tbody').html(
                '<tr><td colspan="7" class="text-center text-muted py-3">Histórico indisponível no momento.</td></tr>');
        });
    }

    /* ================= RENDER: LANÇAMENTOS ================= */

    function renderLancamentos() {
        $listaLanc.empty();
        if (!state.lancamentos.length) {
            $listaLanc.append('<div class="text-center text-muted py-4">Nenhum lançamento com saldo a conciliar.</div>');
            return;
        }
        state.lancamentos.forEach(function (l) {
            const sel = state.lancamentoSelecionado === l.Id;
            const $card = $(
                '<div class="conc-item ' + (sel ? 'conc-item-selecionado' : '') + '" data-id="' + l.Id + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                '<div class="fw-semibold">' + (l.Descricao || 'Sem descrição') + '</div>' +
                '<div class="small text-muted">' + dataBR(l.DataLancamento) +
                (l.ContaNome ? ' · ' + l.ContaNome : '') +
                (l.Origem ? ' · <span class="badge bg-light text-dark border">' + l.Origem + '</span>' : '') +
                '</div>' +
                '</div>' +
                '<div class="text-end">' +
                '<div class="fw-bold text-danger">' + brl(l.SaldoDisponivel) + '</div>' +
                (sel ? '<span class="badge bg-primary mt-1">Selecionado</span>' : '') +
                '</div>' +
                '</div>' +
                '</div>'
            );
            $card.on('click', function () { selecionarLancamento(l.Id); });
            $listaLanc.append($card);
        });
    }

    /* ================= RENDER: NF-E ================= */

    function renderNfes() {
        $listaNfe.empty();
        if (!state.nfes.length) {
            $listaNfe.append('<div class="text-center text-muted py-4">Nenhuma NF-e com saldo em aberto.</div>');
            return;
        }
        state.nfes.forEach(function (n) {
            const sug = state.sugestoes.find(function (s) { return s.nfe_id === n.Id; });
            const alocado = state.nfesSelecionadas.get(n.Id);
            const classes = ['conc-item'];
            if (sug) classes.push('conc-item-sugestao-' + sug.confianca);
            if (alocado) classes.push('conc-item-selecionado');

            const badges = [];
            if (sug) {
                const cor = sug.confianca === 'alta' ? 'success' : (sug.confianca === 'media' ? 'warning' : 'secondary');
                badges.push('<span class="badge bg-' + cor + '">Sugestão ' + sug.confianca + '</span>');
            }
            if (alocado) badges.push('<span class="badge bg-primary">Alocado ' + brl(alocado) + '</span>');

            const fornecedor = n.Nome || n.RazaoSocial || '';

            const $card = $(
                '<div class="' + classes.join(' ') + '" data-id="' + n.Id + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div class="form-check">' +
                '<input class="form-check-input conc-check-nfe" type="checkbox" data-id="' + n.Id + '"' + (alocado ? ' checked' : '') + '>' +
                '<div>' +
                '<div class="fw-semibold">NF ' + (n.Numero || '—') + (n.Serie ? '/' + n.Serie : '') +
                (fornecedor ? ' <span class="text-muted">· ' + fornecedor + '</span>' : '') + '</div>' +
                '<div class="small text-muted">Emissão: ' + dataBR(n.DataEmissao) + '</div>' +
                (sug ? '<div class="small text-success mt-1">' + sug.motivos.map(function (m) { return '✓ ' + m; }).join('<br>') + '</div>' : '') +
                '</div>' +
                '</div>' +
                '<div class="text-end">' +
                '<div class="fw-bold">' + brl(n.SaldoAberto) + '</div>' +
                (badges.length ? '<div class="mt-1">' + badges.join(' ') + '</div>' : '') +
                '</div>' +
                '</div>' +
                '</div>'
            );

            $card.on('click', function (ev) {
                if ($(ev.target).is('input')) return;
                toggleNfe(n.Id, !state.nfesSelecionadas.has(n.Id));
            });
            $listaNfe.append($card);
        });
    }

    /* ================= SELEÇÃO / SUGESTÕES ================= */

    function selecionarLancamento(id) {
        state.lancamentoSelecionado = id;
        state.nfesSelecionadas.clear();
        state.sugestoes = [];
        renderLancamentos();
        renderNfes();
        atualizarBarra();

        apiGet({ acao: 'sugestoes', lancamento_id: id }).done(function (r) {
            if (!r.sucesso) {
                // Lançamento já conciliado ou sem sugestões: apenas atualiza a barra
                state.sugestoes = [];
                renderNfes();
                atualizarBarra();
                return;
            }
            state.sugestoes = r.sugestoes || [];

            // Pré-seleção segura: apenas sugestões de alta confiança que fecham
            // com o valor do lançamento (exato ou com acréscimo até 5% — multa/mora)
            const alta = state.sugestoes.filter(function (s) { return s.confianca === 'alta'; });
            const soma = alta.reduce(function (t, s) { return t + s.saldo; }, 0);
            const valor = r.lancamento ? Number(r.lancamento.valor) : 0;
            if (alta.length && (
                Math.abs(soma - valor) < 0.005 ||
                (soma >= valor * 0.95 && soma <= valor * 1.05)
            )) {
                alta.forEach(function (s) { state.nfesSelecionadas.set(s.nfe_id, s.saldo); });
            }
            renderNfes();
            atualizarBarra();
        }).fail(function () {
            renderNfes();
            atualizarBarra();
        });
    }

    function toggleNfe(id, selecionar) {
        if (!state.lancamentoSelecionado) {
            alert('Selecione primeiro um lançamento financeiro.');
            return;
        }
        if (selecionar) {
            const nfe = state.nfes.find(function (n) { return n.Id === id; });
            if (!nfe) return;
            state.nfesSelecionadas.set(id, Number(nfe.SaldoAberto));
        } else {
            state.nfesSelecionadas.delete(id);
        }
        renderNfes();
        atualizarBarra();
    }

    function atualizarBarra() {
        const $barra = $('#conc-barra');
        if (!state.lancamentoSelecionado) {
            $barra.addClass('d-none');
            return;
        }
        $barra.removeClass('d-none');

        const lanc = state.lancamentos.find(function (l) { return l.Id === state.lancamentoSelecionado; });
        const total = Array.from(state.nfesSelecionadas.values()).reduce(function (t, v) { return t + v; }, 0);
        const valorLanc = lanc ? Number(lanc.SaldoDisponivel) : 0;
        const dif = valorLanc - total;

        $('#conc-barra-lancamento').text((lanc ? dataBR(lanc.DataLancamento) + ' · ' : '') + brl(valorLanc));
        $('#conc-barra-total').text(brl(total));

        const $dif = $('#conc-barra-diferenca');
        if (Math.abs(dif) < 0.005) {
            $dif.html('<span class="badge bg-success">Valores fecham</span>');
        } else if (dif > 0) {
            $dif.html('<span class="badge bg-warning text-dark">Faltam alocar: ' + brl(dif) + '</span>');
        } else {
            $dif.html('<span class="badge bg-warning text-dark">Alocado acima em: ' + brl(Math.abs(dif)) + '</span>');
        }
        $('#conc-btn-confirmar').prop('disabled', state.nfesSelecionadas.size === 0);
    }

    /* ================= CONFIRMAÇÃO ================= */

    function abrirModalConfirmacao() {
        const lanc = state.lancamentos.find(function (l) { return l.Id === state.lancamentoSelecionado; });
        const total = Array.from(state.nfesSelecionadas.values()).reduce(function (t, v) { return t + v; }, 0);
        const linhas = Array.from(state.nfesSelecionadas.entries()).map(function (par) {
            const n = state.nfes.find(function (x) { return x.Id === par[0]; });
            const rot = n ? ('NF ' + (n.Numero || par[0]) + (n.Nome ? ' · ' + n.Nome : '')) : ('NF #' + par[0]);
            return '<li>' + rot + ' — ' + brl(par[1]) + '</li>';
        }).join('');

        $('#conc-modal-resumo').html(
            '<div class="small text-muted mb-1">Lançamento financeiro</div>' +
            '<div class="mb-2"><strong>' + (lanc ? (lanc.Descricao || 'Sem descrição') : '—') + '</strong> · ' +
            brl(lanc ? lanc.SaldoDisponivel : 0) + '</div>' +
            '<div class="small text-muted mb-1">NF-e a conciliar</div>' +
            '<ul class="mb-2 small">' + linhas + '</ul>' +
            '<div class="small text-muted mb-1">Total alocado</div>' +
            '<div><strong>' + brl(total) + '</strong></div>'
        );
        $('#conc-modal-obs').val('');
        $('#conc-modal-erro').addClass('d-none');
        $('#conc-modal').modal('show');
    }

    function confirmarConciliacao() {
        const $btn = $('#conc-modal-confirmar').prop('disabled', true);
        const itens = Array.from(state.nfesSelecionadas.entries()).map(function (par) {
            return { nfe_id: par[0], valor: par[1] };
        });

        apiPost({
            acao: 'confirmar',
            lancamento_id: state.lancamentoSelecionado,
            itens: itens,
            observacoes: $('#conc-modal-obs').val(),
        }).done(function (r) {
            if (r.sucesso) {
                $('#conc-modal').modal('hide');
                limparSelecao();
                carregarTudo();
            } else {
                $('#conc-modal-erro').text(r.mensagem).removeClass('d-none');
            }
        }).fail(function (xhr) {
            const r = normalizar(xhr.responseJSON || {});
            $('#conc-modal-erro').text(r.mensagem || 'Erro ao confirmar conciliação.').removeClass('d-none');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function limparSelecao() {
        state.lancamentoSelecionado = null;
        state.nfesSelecionadas.clear();
        state.sugestoes = [];
        renderLancamentos();
        renderNfes();
        atualizarBarra();
    }

    /* ================= ESTORNO ================= */

    function abrirModalEstorno(id) {
        state.estornoId = id;
        $('#conc-estorno-motivo').val('');
        $('#conc-estorno-erro').addClass('d-none');
        $('#conc-modal-estorno').modal('show');
    }

    function confirmarEstorno() {
        const $btn = $('#conc-estorno-confirmar').prop('disabled', true);
        apiPost({
            acao: 'estornar',
            conciliacao_id: state.estornoId,
            motivo: $('#conc-estorno-motivo').val(),
        }).done(function (r) {
            if (r.sucesso) {
                $('#conc-modal-estorno').modal('hide');
                carregarTudo();
            } else {
                $('#conc-estorno-erro').text(r.mensagem).removeClass('d-none');
            }
        }).fail(function (xhr) {
            const r = normalizar(xhr.responseJSON || {});
            $('#conc-estorno-erro').text(r.mensagem || 'Erro ao estornar.').removeClass('d-none');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    /* ================= EVENTOS ================= */

    $(function () {
        carregarTudo();

        $('#conc-btn-atualizar').on('click', function () {
            limparSelecao();
            carregarLancamentos();
            carregarNfes();
            carregarResumo();
        });

        $('#conc-btn-limpar').on('click', limparSelecao);
        $('#conc-btn-confirmar').on('click', abrirModalConfirmacao);
        $('#conc-modal-confirmar').on('click', confirmarConciliacao);
        $('#conc-estorno-confirmar').on('click', confirmarEstorno);

        $(document).on('click', '.conc-btn-estornar', function () {
            abrirModalEstorno($(this).data('id'));
        });

        $(document).on('change', '.conc-check-nfe', function () {
            toggleNfe($(this).data('id'), $(this).is(':checked'));
        });
    });
})();