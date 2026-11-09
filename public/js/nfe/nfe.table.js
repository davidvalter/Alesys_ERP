/**
 * Tabela de Notas Fiscais
 * 
 * @file /js/nfe/nfe.table.js
 * @version 2.0.0
 */

const NFETable = {

    table: null,

    init() {
        this.initDataTable();
        this.bindEvents();
    },

    initDataTable() {

        // ⚠️ Evita reinit do DataTable
        if ($.fn.DataTable.isDataTable('#tabelaNotas')) {
            $('#tabelaNotas').DataTable().destroy();
        }

        this.table = $('#tabelaNotas').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/api/list_notas_fiscais.php',
                type: 'GET'
            },
            columns: [
                { data: 'Numero' },
                { data: 'Serie' },
                { data: 'Tipo' },
                { data: 'DataEmissao' },
                { data: 'MercadoNome' },
                { data: 'ValorTotal' },
                {
                    data: 'StatusTexto',
                    render: function (statusTexto, type, row) {
                        return Number(row.Status) === 1
                            ? '<span class="badge rounded-pill bg-success">Ativa</span>'
                            : '<span class="badge rounded-pill bg-secondary">Inativa</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-end',
                    render: function (data) {
                        return `
                            <div class="acoes-tabela">

                                <button class="btn-action btn-editar"
                                    data-id="${data.Id}"
                                    title="Editar">
                                    <i class="fas fa-pen"></i>
                                </button>

                                <button class="btn-action btn-view"
                                    data-id="${data.Id}"
                                    title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <button class="btn-action btn-cancel"
                                    data-id="${data.Id}"
                                    data-status="${data.Status}"
                                    title="${data.Status == 1 ? 'Desativar' : 'Reativar'}">
                                    <i class="fas ${data.Status == 1 ? 'fa-ban' : 'fa-undo'}"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[3, 'desc']],
            pageLength: 10,
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            }
        });
    },

    bindEvents() {

        // EDITAR
        $(document).on('click', '.btn-editar', (e) => {
            const id = $(e.currentTarget).data('id');
            this.editarNota(id);
        });

        // VISUALIZAR
        $(document).on('click', '.btn-view', (e) => {
            const id = $(e.currentTarget).data('id');
            this.visualizarNota(id);
        });

        // CANCELAR
        $(document).on('click', '.btn-cancel', (e) => {
            const btn = $(e.currentTarget);
            const id = btn.data('id');
            const status = btn.data('status');

            this.cancelarNota(id, status);
        });
    },

    editarNota(id) {

        $.get('/api/get_nota_fiscal.php', { Id: id })
            .done((res) => {

                if (!res.success) {
                    alert(res.message);
                    return;
                }

                console.log('Nota carregada:', res.data);

                if (typeof NFEModal !== 'undefined') {
                    NFEModal.abrir(res.data);
                }

            })
            .fail(() => {
                alert('Erro ao carregar nota fiscal');
            });
    },

    visualizarNota(id) {

        NFEService.getNota(id)
            .done(res => {

                if (!res.success) {
                    alert(res.message);
                    return;
                }

                NFEModalVisualizar.abrir(res.data);

            })
            .fail(() => {
                alert('Erro ao carregar nota');
            });
    },

    cancelarNota(id, statusAtual) {

        const acao = statusAtual == 1 ? 'desativar' : 'reativar';

        if (!confirm(`Deseja realmente ${acao} esta nota fiscal?`)) return;

        $.ajax({
            url: '/api/cancelar_nota_fiscal.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ Id: id }),

            success: (res) => {
                if (res.success) {
                    showToast(res.message, 'success');
                    this.table.ajax.reload(null, false);
                } else {
                    alert(res.message);
                }
            },

            error: () => {
                alert(`Erro ao ${acao} nota fiscal`);
            }
        });
    }

};