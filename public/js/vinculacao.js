/* /public/js/vinculacao.js
 * Vínculo FinanceiroLancamentos x NFe (FinanceiroLancamentoNFe).
 * Aba 1: parte do lançamento -> mostra NF-e candidatas.
 * Aba 2: parte da NF-e -> mostra lançamentos candidatos (vice-versa).
 * Reaproveita /styles/conciliacao.css (classes conc-*).
 */

(function () {
    'use strict';

    const API = '/api/vinculacao_nfe.php';

    const state = {
        // Aba 1: por lançamento
        lancamentos: [],
        lancamentoSelecionado: null,
        candNfe: [],
        // Aba 2: por NF-e
        nfes: [],
        nfeSelecionada: null,
        candLanc: [],
        // Modal
        vinculoAlvo: null, // { lancamento_id, nfe_id }
    };

    const $listaLanc = $('#vinc-lista-lancamentos');
    const $listaCandNfe = $('#vinc-lista-cand-nfe');
    const $listaNfes = $('#vinc-lista-nfes');
    const $listaCandLanc = $('#vinc-lista-cand-lancamentos');

    /* ================= UTILITÁRIOS ================= */

    function brl(v) {
        return (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function dataBR(iso) {
        if (!iso) return '—';
        // Formatação por STRING, sem new Date: strings só de data
        // ('2025-12-11') são interpretadas como meia-noite UTC e, no
        // fuso de São Paulo (UTC-3), viravam o dia anterior (10/12).
        const s = String(iso).slice(0, 10);
        const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return String(iso);
        return m[3] + '/' + m[2] + '/' + m[1];
    }

    function fmtData(d) {
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    /** Preenche os filtros com o padrão: últimos 30 dias até hoje. */
    function aplicarDatasPadrao() {
        const hoje = new Date();
        const ha30 = new Date();
        ha30.setDate(hoje.getDate() - 30);
        $('#vinc-data-ini-lanc').val(fmtData(ha30));
        $('#vinc-data-fim-lanc').val(fmtData(hoje));
        $('#vinc-data-ini-nfe').val(fmtData(ha30));
        $('#vinc-data-fim-nfe').val(fmtData(hoje));
    }

    function normalizar(r) {
        if (r && r.success === undefined && r.sucesso !== undefined) r.success = r.sucesso;
        if (r && r.message === undefined && r.mensagem !== undefined) r.message = r.mensagem;
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

    function badgeConfianca(confianca) {
        const cor = confianca === 'alta' ? 'success' : (confianca === 'media' ? 'warning' : 'secondary');
        return '<span class="badge bg-' + cor + ' ms-1">' + confianca + '</span>';
    }

    /* ================= ABA 1: POR LANÇAMENTO ================= */

    function carregarLancamentos() {
        $('#vinc-loading-lancamentos').removeClass('d-none');
        apiGet({
            acao: 'lancamentos_sem_vinculo',
            data_ini_lanc: $('#vinc-data-ini-lanc').val() || '',
            data_fim_lanc: $('#vinc-data-fim-lanc').val() || '',
        }).done(function (r) {
            state.lancamentos = r.success ? r.lancamentos : [];
            if (state.lancamentoSelecionado &&
                !state.lancamentos.some(function (l) { return l.Id === state.lancamentoSelecionado; })) {
                state.lancamentoSelecionado = null;
                state.candNfe = [];
                renderCandNfe();
                $('#vinc-badge-selec-lanc').text('Nenhum lançamento');
            }
            renderLancamentos();
            $('#vinc-badge-lancamentos').text(state.lancamentos.length);
        }).fail(function () {
            $listaLanc.html('<div class="text-center text-danger py-4">Erro ao carregar lançamentos.</div>');
        }).always(function () {
            $('#vinc-loading-lancamentos').addClass('d-none');
        });
    }

    function renderLancamentos() {
        $listaLanc.empty();
        if (!state.lancamentos.length) {
            $listaLanc.append('<div class="text-center text-muted py-4">Nenhum lançamento sem vínculo neste período.</div>');
            return;
        }
        state.lancamentos.forEach(function (l) {
            const sel = state.lancamentoSelecionado === l.Id;

            // Datas: mostra a baixa quando existir e divergir do lançamento
            const dataLanc = String(l.DataLancamento || '').slice(0, 10);
            const dataBaixa = l.DataBaixa ? String(l.DataBaixa).slice(0, 10) : null;
            const partesData = [dataBR(dataLanc)];
            if (dataBaixa && dataBaixa !== dataLanc) {
                partesData.push('baixa em ' + dataBR(dataBaixa));
            }

            // Valor por módulo: saídas podem estar gravadas com sinal negativo
            const valorAbs = Math.abs(Number(l.ValorLiquido) || 0);

            const $card = $(
                '<div class="conc-item ' + (sel ? 'conc-item-selecionado' : '') + '" data-id="' + l.Id + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                '<div class="fw-semibold">' + (l.Descricao || 'Sem descrição') + '</div>' +
                '<div class="small text-muted">' + partesData.join(' · ') +
                (l.Origem ? ' · <span class="badge bg-light text-dark border">' + l.Origem + '</span>' : '') +
                '</div>' +
                '</div>' +
                '<div class="text-end"><div class="fw-bold">' + brl(valorAbs) + '</div>' +
                (sel ? '<span class="badge bg-primary mt-1">Selecionado</span>' : '') +
                '</div>' +
                '</div>' +
                '</div>'
            );
            $card.on('click', function () { selecionarLancamento(l.Id); });
            $listaLanc.append($card);
        });
    }

    function selecionarLancamento(id) {
        state.lancamentoSelecionado = id;
        state.candNfe = [];
        renderLancamentos();
        renderCandNfe();
        $('#vinc-badge-selec-lanc').text('Lançamento #' + id);

        $('#vinc-loading-cand-nfe').removeClass('d-none');
        apiGet({ acao: 'candidatos_nfe', lancamento_id: id }).done(function (r) {
            state.candNfe = r.success ? r.candidatos : [];
            renderCandNfe();
        }).fail(function () {
            $listaCandNfe.html('<div class="text-center text-danger py-4">Erro ao buscar candidatos.</div>');
        }).always(function () {
            $('#vinc-loading-cand-nfe').addClass('d-none');
        });
    }

    function renderCandNfe() {
        $listaCandNfe.empty();
        if (!state.lancamentoSelecionado) {
            $listaCandNfe.append('<div class="text-center text-muted py-4">Selecione um lançamento à esquerda para ver as NF-e candidatas.</div>');
            return;
        }
        if (!state.candNfe.length) {
            $listaCandNfe.append('<div class="text-center text-muted py-4">Nenhuma NF-e com compatibilidade suficiente para este lançamento.</div>');
            return;
        }
        state.candNfe.forEach(function (n) {
            const $card = $(
                '<div class="conc-item conc-item-sugestao-' + n.confianca + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                '<div class="fw-semibold">NF ' + (n.numero || '—') + (n.serie ? '/' + n.serie : '') +
                badgeConfianca(n.confianca) + '</div>' +
                '<div class="small text-muted">Emissão: ' + dataBR(n.emissao) +
                (n.fornecedor ? ' · ' + n.fornecedor : '') + '</div>' +
                '<div class="small text-success mt-1">' + n.motivos.map(function (m) { return '✓ ' + m; }).join('<br>') + '</div>' +
                '</div>' +
                '<div class="text-end">' +
                '<div class="fw-bold">' + brl(n.valor) + '</div>' +
                '<button class="btn btn-sm btn-primary mt-1 vinc-btn-vincular-nfe" data-id="' + n.nfe_id + '">' +
                '<i class="fas fa-link me-1"></i>Vincular</button>' +
                '</div>' +
                '</div>' +
                '</div>'
            );
            $listaCandNfe.append($card);
        });

        $listaCandNfe.find('.vinc-btn-vincular-nfe').on('click', function () {
            const nfeId = $(this).data('id');
            const n = state.candNfe.find(function (c) { return c.nfe_id === nfeId; });
            // ASSINATURA CORRETA: (lancamentoId, nfeId, exigeJustificativa)
            abrirModal(state.lancamentoSelecionado, nfeId, !n || n.confianca !== 'alta');
        });
    }

    /* ================= ABA 2: POR NF-E ================= */

    function carregarNfes() {
        $('#vinc-loading-nfes').removeClass('d-none');
        apiGet({
            acao: 'nfes_sem_vinculo',
            data_ini_nfe: $('#vinc-data-ini-nfe').val() || '',
            data_fim_nfe: $('#vinc-data-fim-nfe').val() || '',
        }).done(function (r) {
            state.nfes = r.success ? r.nfes : [];
            if (state.nfeSelecionada &&
                !state.nfes.some(function (n) { return n.Id === state.nfeSelecionada; })) {
                state.nfeSelecionada = null;
                state.candLanc = [];
                renderCandLanc();
                $('#vinc-badge-selec-nfe').text('Nenhuma NF-e');
            }
            renderNfes();
            $('#vinc-badge-nfes').text(state.nfes.length);
        }).fail(function () {
            $listaNfes.html('<div class="text-center text-danger py-4">Erro ao carregar NF-e.</div>');
        }).always(function () {
            $('#vinc-loading-nfes').addClass('d-none');
        });
    }

    function renderNfes() {
        $listaNfes.empty();
        if (!state.nfes.length) {
            $listaNfes.append('<div class="text-center text-muted py-4">Nenhuma NF-e sem vínculo neste período.</div>');
            return;
        }
        state.nfes.forEach(function (n) {
            const sel = state.nfeSelecionada === n.Id;
            const fornecedor = n.Nome || n.RazaoSocial || '';
            const $card = $(
                '<div class="conc-item ' + (sel ? 'conc-item-selecionado' : '') + '" data-id="' + n.Id + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                '<div class="fw-semibold">NF ' + (n.Numero || '—') + (n.Serie ? '/' + n.Serie : '') + '</div>' +
                '<div class="small text-muted">Emissão: ' + dataBR(n.DataEmissao) +
                (fornecedor ? ' · ' + fornecedor : '') + '</div>' +
                '</div>' +
                '<div class="text-end"><div class="fw-bold">' + brl(n.ValorTotal) + '</div>' +
                (sel ? '<span class="badge bg-primary mt-1">Selecionado</span>' : '') +
                '</div>' +
                '</div>' +
                '</div>'
            );
            $card.on('click', function () { selecionarNfe(n.Id); });
            $listaNfes.append($card);
        });
    }

    function selecionarNfe(id) {
        state.nfeSelecionada = id;
        state.candLanc = [];
        renderNfes();
        renderCandLanc();
        $('#vinc-badge-selec-nfe').text('NF-e #' + id);

        $('#vinc-loading-cand-lanc').removeClass('d-none');
        apiGet({ acao: 'candidatos_lancamento', nfe_id: id }).done(function (r) {
            state.candLanc = r.success ? r.candidatos : [];
            renderCandLanc();
        }).fail(function () {
            $listaCandLanc.html('<div class="text-center text-danger py-4">Erro ao buscar candidatos.</div>');
        }).always(function () {
            $('#vinc-loading-cand-lanc').addClass('d-none');
        });
    }

    function renderCandLanc() {
        $listaCandLanc.empty();
        if (!state.nfeSelecionada) {
            $listaCandLanc.append('<div class="text-center text-muted py-4">Selecione uma NF-e à esquerda para ver os lançamentos candidatos.</div>');
            return;
        }
        if (!state.candLanc.length) {
            $listaCandLanc.append('<div class="text-center text-muted py-4">Nenhum lançamento com compatibilidade suficiente para esta NF-e.</div>');
            return;
        }
        state.candLanc.forEach(function (l) {
            // Datas: lança baixa quando existir e divergir do lançamento
            const dataLanc = String(l.data || '').slice(0, 10);
            const dataBaixa = l.data_baixa ? String(l.data_baixa).slice(0, 10) : null;
            const partesData = [dataBR(dataLanc)];
            if (dataBaixa && dataBaixa !== dataLanc) {
                partesData.push('baixa em ' + dataBR(dataBaixa));
            }

            const $card = $(
                '<div class="conc-item conc-item-sugestao-' + l.confianca + '">' +
                '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                '<div class="fw-semibold">' + (l.descricao || 'Sem descrição') + '</div>' +
                '<div class="small text-muted">' + partesData.join(' · ') + '</div>' +
                '<div class="small text-success mt-1">' + l.motivos.map(function (m) { return '✓ ' + m; }).join('<br>') + '</div>' +
                '</div>' +
                '<div class="text-end">' +
                '<div class="fw-bold">' + brl(l.valor) + '</div>' +
                badgeConfianca(l.confianca) +
                '<br><button class="btn btn-sm btn-primary mt-1 vinc-btn-vincular-lanc" data-id="' + l.lancamento_id + '">' +
                '<i class="fas fa-link me-1"></i>Vincular</button>' +
                '</div>' +
                '</div>' +
                '</div>'
            );
            $listaCandLanc.append($card);
        });

        $listaCandLanc.find('.vinc-btn-vincular-lanc').on('click', function () {
            const lancId = $(this).data('id');
            const l = state.candLanc.find(function (c) { return c.lancamento_id === lancId; });
            abrirModal(lancId, state.nfeSelecionada, !l || l.confianca !== 'alta');
        });
    }

    /* ================= MODAL DE VÍNCULO ================= */

    function abrirModal(lancamentoId, nfeId, exigeJustificativa) {
        state.vinculoAlvo = { lancamento_id: lancamentoId, nfe_id: nfeId };

        // Localiza os dados nas listas e nos candidatos para montar o resumo
        const lanc = state.lancamentos.find(function (l) { return l.Id === lancamentoId; });
        const nfe = state.nfes.find(function (n) { return n.Id === nfeId; });
        const candNfe = state.candNfe.find(function (c) { return c.nfe_id === nfeId; });
        const candLanc = state.candLanc.find(function (c) { return c.lancamento_id === lancamentoId; });

        const descLanc = lanc ? (lanc.Descricao || 'sem descrição') :
            (candLanc ? (candLanc.descricao || 'sem descrição') : '#' + lancamentoId);
        const valorLanc = lanc ? lanc.ValorLiquido : (candLanc ? candLanc.valor : 0);
        const rotNfe = nfe ? ('NF ' + nfe.Numero + (nfe.Serie ? '/' + nfe.Serie : '')) :
            (candNfe ? ('NF ' + candNfe.numero + (candNfe.serie ? '/' + candNfe.serie : '')) : '#' + nfeId);
        const fornecedorNfe = nfe ? (nfe.Nome || nfe.RazaoSocial || '') : (candNfe ? (candNfe.fornecedor || '') : '');
        const valorNfe = nfe ? nfe.ValorTotal : (candNfe ? candNfe.valor : 0);

        $('#vinc-modal-resumo').html(
            '<div><strong>Lançamento:</strong> #' + lancamentoId + ' — ' + descLanc +
            ' · ' + brl(valorLanc) + '</div>' +
            '<div><strong>NF-e:</strong> ' + rotNfe +
            (fornecedorNfe ? ' (' + fornecedorNfe + ')' : '') +
            ' · ' + brl(valorNfe) + '</div>' +
            (exigeJustificativa ? '<div class="text-warning mt-2">⚠️ Sugestão de confiança baixa/média ou valores divergentes: justifique na observação.</div>' : '')
        );
        $('#vinc-modal-obs').val('');
        $('#vinc-modal-erro').addClass('d-none');
        $('#vinc-modal').modal('show');
    }

    function confirmarVinculo() {
        const $btn = $('#vinc-modal-confirmar').prop('disabled', true);
        apiPost({
            acao: 'vincular',
            lancamento_id: state.vinculoAlvo.lancamento_id,
            nfe_id: state.vinculoAlvo.nfe_id,
            observacoes: $('#vinc-modal-obs').val(),
        }).done(function (r) {
            if (r.success) {
                $('#vinc-modal').modal('hide');
                // Limpa as duas abas e recarrega
                state.lancamentoSelecionado = null;
                state.candNfe = [];
                state.nfeSelecionada = null;
                state.candLanc = [];
                renderCandNfe();
                renderCandLanc();
                $('#vinc-badge-selec-lanc').text('Nenhum lançamento');
                $('#vinc-badge-selec-nfe').text('Nenhuma NF-e');
                carregarLancamentos();
                carregarNfes();
            } else {
                $('#vinc-modal-erro').text(r.message).removeClass('d-none');
            }
        }).fail(function (xhr) {
            const r = normalizar(xhr.responseJSON || {});
            $('#vinc-modal-erro').text(r.message || 'Erro ao vincular.').removeClass('d-none');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    /* ================= EVENTOS ================= */

    $(function () {
        $('#vinc-badge-selec-lanc').text('Nenhum lançamento');
        $('#vinc-badge-selec-nfe').text('Nenhuma NF-e');
        renderCandNfe();
        renderCandLanc();

        // Padrão: últimos 30 dias — deve rodar ANTES da primeira carga
        aplicarDatasPadrao();
        carregarLancamentos();
        carregarNfes();

        // Filtros de data por aba
        $('#vinc-btn-filtrar-lanc').on('click', function () {
            state.lancamentoSelecionado = null;
            state.candNfe = [];
            renderCandNfe();
            $('#vinc-badge-selec-lanc').text('Nenhum lançamento');
            carregarLancamentos();
        });

        $('#vinc-btn-filtrar-nfe').on('click', function () {
            state.nfeSelecionada = null;
            state.candLanc = [];
            renderCandLanc();
            $('#vinc-badge-selec-nfe').text('Nenhuma NF-e');
            carregarNfes();
        });

        $('#vinc-modal-confirmar').on('click', confirmarVinculo);
    });
})();