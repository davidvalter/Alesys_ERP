// public/js/financeiro_categoriaslancamentos.js

$(document).ready(function () {

    let modalCategoria;
    let tabela;
    let categoriaAtual = null;
    let salvandoCategoria = false;   // trava de envio duplicado

    // Fallback de notificação (usa o showToast global se existir)
    function showToast(msg, tipo) {
        if (typeof window.showToast === 'function') return window.showToast(msg, tipo);
        alert(msg);
    }

    const tabelaBody = $("#tabelaCategorias tbody");
    const filtroTipo = $("#filtro-tipo");
    const filtroAtivo = $("#filtro-ativo");
    const modal = $("#modalCategoria");
    const form = $("#formCategoria");
    const selDreLinha = $("#dreLinha");


    modalCategoria = new bootstrap.Modal(document.getElementById('modalCategoria'));

    // ==============================
    // 1. CARREGAR LISTA
    // ==============================
    async function carregarCategorias() {

        if ($.fn.DataTable.isDataTable('#tabelaCategorias')) {
            $('#tabelaCategorias').DataTable().destroy();
        }

        tabelaBody.html(`<tr><td colspan="6" class="text-center py-3">Carregando...</td></tr>`);

        const tipo = filtroTipo.val();
        const ativo = filtroAtivo.val();

        try {

            const res = await fetch(`../api/get_categoriaslancamentos.php?tipo=${encodeURIComponent(tipo)}&ativo=${encodeURIComponent(ativo)}`);
            const json = await res.json();

            if (!json.success) throw new Error(json.message);

            // Popula o select de linhas da DRE (preserva seleção atual)
            const selDreValor = selDreLinha.val();
            selDreLinha.find("option:not(:first)").remove();
            (json.linhasDre || []).forEach(l => {
                selDreLinha.append(`<option value="${l.Id}">${l.Nome}</option>`);
            });
            if (selDreValor) selDreLinha.val(selDreValor);

            tabelaBody.empty();

            if (!json.data || json.data.length === 0) {
                tabelaBody.html(`<tr><td colspan="6" class="text-center py-3">Nenhuma categoria encontrada.</td></tr>`);
                return;
            }

            json.data.forEach(cat => {

                const ativoBadge = cat.Ativo == 1
                    ? `<span class="badge rounded-pill bg-success">Ativo</span>`
                    : `<span class="badge rounded-pill bg-secondary">Inativo</span>`;

                let tipoBadge;

                if (cat.Tipo === 'entrada')
                    tipoBadge = `<span class="badge bg-success">Entrada</span>`;
                else if (cat.Tipo === 'saida')
                    tipoBadge = `<span class="badge bg-danger">Saída</span>`;
                else
                    tipoBadge = `<span class="badge bg-secondary">Nenhum</span>`;

                const dreBadge = cat.DreLinhaNome
                    ? `<span class="badge bg-info text-dark">${cat.DreLinhaNome}</span>`
                    : `<span class="badge bg-light text-muted border">—</span>`;

                const btnToggle = `
                    <button class="btn btn-sm btn-outline-secondary btn-toggle" data-id="${cat.Id}" title="Ativar/Inativar">
                        <i class="fas fa-power-off"></i>
                    </button>`;

                const btnEdit = `
                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${cat.Id}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>`;

                tabelaBody.append(`
                    <tr>
                        <td>${cat.Nome}</td>
                        <td>${tipoBadge}</td>
                        <td>${cat.Grupo || '-'}</td>
                        <td>${dreBadge}</td>
                        <td>${ativoBadge}</td>
                        <td>${btnEdit} ${btnToggle}</td>
                    </tr>
                `);
            });

            // ==============================
            // ATIVAR DATATABLE
            // ==============================

            tabela = $('#tabelaCategorias').DataTable({
                pageLength: 20,
                ordering: true,
                responsive: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
                }
            });

        } catch (err) {

            console.error(err);

            tabelaBody.html(`<tr><td colspan="6" class="text-center py-3">Erro ao carregar categorias.</td></tr>`);

        }
    }

    // ==============================
    // 2. EVENTOS DOS FILTROS
    // ==============================

    filtroTipo.on("change", carregarCategorias);
    filtroAtivo.on("change", carregarCategorias);

    // ==============================
    // 3. NOVA CATEGORIA
    // ==============================

    $("#btnNovaCategoria").on("click", function () {

        categoriaAtual = null;

        form[0].reset();

        selDreLinha.val("");

        modal.find(".modal-title").text("Nova Categoria de Lançamento");

        modalCategoria.show();

    });

    // ==============================
    // 4. EDITAR CATEGORIA
    // ==============================

    tabelaBody.on("click", ".btn-edit", async function () {

        const id = $(this).data("id");

        try {

            const res = await fetch(`../api/get_categoriaslancamentos.php`);

            const json = await res.json();

            if (!json.success) throw new Error(json.message);

            const categoria = json.data.find(c => parseInt(c.Id) === parseInt(id));

            if (!categoria) throw new Error("Categoria não encontrada.");

            categoriaAtual = categoria.Id;

            $("#nome").val(categoria.Nome);
            $("#tipo").val(categoria.Tipo);
            $("#grupo").val(categoria.Grupo);
            selDreLinha.val(categoria.DreLinhaId || "");

            modal.find(".modal-title").text("Editar Categoria de Lançamento");

            modalCategoria.show();

        } catch (err) {

            console.error(err);

            showToast("Erro ao carregar dados da categoria.", "error");

        }

    });

    // ==============================
    // 5. SALVAR CATEGORIA
    // ==============================

    form.on("submit", async function (e) {

        e.preventDefault();
        if (salvandoCategoria) return;   // envio em andamento: ignora
        salvandoCategoria = true;

        // Impede envio duplicado
        const btnSalvar = form.find('button[type="submit"]');
        if (btnSalvar.prop('disabled')) return;
        btnSalvar.prop('disabled', true);

        const payload = {
            Id: categoriaAtual,
            Nome: $("#nome").val().trim(),
            Tipo: $("#tipo").val(),
            Grupo: $("#grupo").val().trim(),
            DreLinhaId: selDreLinha.val()
        };

        try {

            const res = await fetch(`../api/save_categorialancamentos.php`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const json = await res.json();

            if (!json.success) throw new Error(json.message || 'Erro ao salvar categoria.');

            // Fecha o modal ANTES de qualquer notificação — garante que não reenvia
            modalCategoria.hide();

            showToast(json.message, "success");

            carregarCategorias();

        } catch (err) {

            console.error(err);
            showToast(err.message || "Erro ao salvar categoria.", "error");

        } finally {

            salvandoCategoria = false;

            // Reabilita o botão em qualquer cenário
            btnSalvar.prop('disabled', false);

        }

    });

    // ==============================
    // 6. ATIVAR / INATIVAR
    // ==============================

    tabelaBody.on("click", ".btn-toggle", async function () {

        const id = $(this).data("id");

        if (!confirm("Deseja alterar o status desta categoria?")) return;

        try {

            const res = await fetch(`../api/toggle_categorialancamentos.php`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ Id: id })
            });

            const json = await res.json();

            if (!json.success) throw new Error(json.message);

            showToast(json.message, "success");

            carregarCategorias();

        } catch (err) {

            console.error(err);

            showToast("Erro ao alterar status.", "error");

        }

    });

    // ==============================
    // 7. INICIALIZAÇÃO
    // ==============================

    carregarCategorias();

});