/* /public/js/dre.js */
$(function () {

    var $modo = $('#dre-modo'),
        $mes = $('#dre-mes'),
        $ano = $('#dre-ano'),
        $conta = $('#dre-conta'),
        $status = $('#dre-status'),
        $tbody = $('#dre-tbody'),
        $thead = $('#dre-thead');

    var fmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    var fmtPct = function (v) {
        return (v === null || v === undefined || !isFinite(v)) ? '—'
            : v.toFixed(1).replace('.', ',') + '%';
    };
    var fmtCell = function (v) {
        var cls = v < 0 ? ' dre-valor-neg' : '';
        return '<td class="text-end' + cls + '">' + fmt.format(v) + '</td>';
    };

    /* ---------- Popula anos ---------- */
    (function () {
        var atual = new Date().getFullYear();
        for (var a = atual + 1; a >= 2018; a--) {
            $ano.append('<option value="' + a + '">' + a + '</option>');
        }
        $ano.val(atual);
        $mes.val(atual + '-' + String(new Date().getMonth() + 1).padStart(2, '0'));
    })();

    /* ---------- Alternância mensal/anual ---------- */
    function alternarModo() {
        var mensal = $modo.val() === 'mensal';
        $('#dre-grupo-mes').toggleClass('d-none', !mensal);
        $('#dre-grupo-ano').toggleClass('d-none', mensal);
        carregar();
    }

    /* ---------- Consulta ---------- */
    function carregar() {
        $('#dre-loading').removeClass('d-none');
        $tbody.empty();

        $.getJSON('/api/get_dre.php', {
            modo: $modo.val(),
            mes: $mes.val(),
            ano: $ano.val(),
            conta_id: $conta.val(),
            status: $status.val()
        })
            .done(function (r) {
                if (!r.ok) {
                    alert(r.erro || 'Erro ao carregar a DRE.');
                    return;
                }
                popularContas(r.contas || []);
                exibirDiagnostico(r.diagnostico);

                // Cabeçalho executivo de impressão (A4)
                $('#dre-print-rotulo').text(r.periodo.rotulo || '');
                var contaTexto = $conta.find('option:selected').text() || 'Todas as contas';
                var statusTexto = $status.find('option:selected').text();
                $('#dre-print-filtros').text(
                    'Período: ' + (r.periodo.rotulo || '') +
                    ' · Conta: ' + contaTexto +
                    ' · Status: ' + statusTexto
                );

                if (r.modo === 'anual') {
                    renderAnual(r);
                } else {
                    renderMensal(r);
                }
            })
            .fail(function (xhr) {
                var msg = 'Falha de comunicação com o servidor.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    if (r && (r.message || r.erro)) {
                        msg = r.message || r.erro;
                    } else if (xhr.responseText) {
                        msg = 'HTTP ' + xhr.status + ': ' + xhr.responseText.substring(0, 300);
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        msg = 'HTTP ' + xhr.status + ': ' + xhr.responseText.substring(0, 300);
                    }
                }
                alert(msg);
            })
            .always(function () {
                $('#dre-loading').addClass('d-none');
            });
    }

    /* ---------- Contas (não sobrescreve seleção) ---------- */
    var contasCarregadas = false;
    function popularContas(contas) {
        if (contasCarregadas || !contas.length) return;
        var atual = $conta.val();
        contas.forEach(function (c) {
            $conta.append('<option value="' + c.Id + '">' + $('<i>').text(c.Nome).html() + '</option>');
        });
        $conta.val(atual);
        contasCarregadas = true;
    }

    /* ---------- Diagnóstico de não classificados ---------- */
    function exibirDiagnostico(d) {
        var $alerta = $('#dre-alerta-sem-classe');
        if (!d || !d.total) {
            $alerta.addClass('d-none');
            return;
        }
        $('#dre-alerta-total').text(' ' + fmt.format(d.total) + ' no período.');
        var lista = (d.categorias || []).map(function (c) {
            return '<span class="badge bg-secondary me-1 mb-1">' +
                $('<i>').text(c.Nome + ': ' + fmt.format(c.Valor)).html() + '</span>';
        }).join('');
        $('#dre-alerta-categorias').html(
            'Categorias sem mapeamento (cadastre em DreCategoriaMap): ' + lista
        );
        $alerta.removeClass('d-none');
    }

    /* ---------- Linha da tabela ---------- */
    function trLinha(l) {
        var classe = '';
        if (l.tipo === 'subtotal') classe = ' dre-subtotal';
        if (l.tipo === 'total') classe = ' dre-total';
        if (l.codigo === 'RES_LIQUIDO') classe += ' dre-res-liquido';

        return '<tr class="' + classe.trim() + '">' +
            '<td class="' + (l.tipo === 'linha' ? 'dre-linha ps-4' : '') + '">' +
            $('<i>').text(l.nome).html() + '</td>';
    }

    /* ---------- Visão mensal ---------- */
    function renderMensal(r) {
        $('#dre-periodo-rotulo').text(r.periodo.rotulo);
        $thead.html('<tr>' +
            '<th style="width:60%">Linha</th>' +
            '<th class="text-end">Valor</th>' +
            '<th class="text-end" style="width:15%">% Receita Bruta</th>' +
            '</tr>');

        $tbody.empty();
        r.linhas.forEach(function (l) {
            var linha = trLinha(l) + fmtCell(l.valor) +
                '<td class="text-end text-muted">' + fmtPct(l.pct) + '</td></tr>';
            $tbody.append(linha);
        });

        atualizarKpis(r.linhas);
    }

    /* ---------- Visão anual ---------- */
    function renderAnual(r) {
        var meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $('#dre-periodo-rotulo').text(r.periodo.rotulo);
        $thead.html('<tr><th>Linha</th>' +
            meses.map(function (m) { return '<th class="text-end">' + m + '</th>'; }).join('') +
            '<th class="text-end">Total</th><th class="text-end">% RB</th></tr>');

        $tbody.empty();
        r.linhas.forEach(function (l) {
            var tr = trLinha(l);
            l.meses.forEach(function (v) { tr += fmtCell(v); });
            tr += '<td class="text-end fw-bold">' + fmt.format(l.total) + '</td>' +
                '<td class="text-end text-muted">' + fmtPct(l.pct) + '</td></tr>';
            $tbody.append(tr);
        });

        atualizarKpis(r.linhas);
    }

    /* ---------- KPIs ---------- */
    function atualizarKpis(linhas) {
        var busca = {};
        linhas.forEach(function (l) { busca[l.codigo] = l; });
        $('#dre-kpi-receita').text(fmt.format(busca.REC_BRUTA?.total ?? 0));
        $('#dre-kpi-lucro').text(fmt.format(busca.LUCRO_BRUTO?.total ?? 0));
        $('#dre-kpi-resultado').text(fmt.format(busca.RES_LIQUIDO?.total ?? 0));
    }

    /* ---------- Eventos ---------- */
    $modo.on('change', alternarModo);
    $('#dre-btn-atualizar').on('click', carregar);
    $mes.on('change', carregar);
    $ano.on('change', carregar);
    $conta.on('change', carregar);
    $status.on('change', carregar);
    $('#dre-btn-imprimir').on('click', function () { window.print(); });

    carregar();
});