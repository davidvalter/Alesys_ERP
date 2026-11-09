/**
 * Autocomplete de Produtos com navegação por teclado
 * 
 * @file /js/nfe/nfe.modal.visualizar.js
 */

let notaAtualId = null;
let notaAtualPdf = null;

const NFEModalVisualizar = {

    init() {
        this.bindEvents();
    },

    abrir(response) {

        console.log('DATA NOTA:', response);

        // 🔥 AJUSTE AQUI
        const data = response.nota;
        const itens = response.itens || [];

        notaAtualId = data.Id;
        notaAtualPdf = data.pdf_path || null;

        let itensHtml = itens.length
            ? itens.map(i => `
                <tr>
                    <td>${i.ProdutoNome}</td>
                    <td class="text-end">${i.Quantidade}</td>
                    <td class="text-end">R$ ${i.ValorUnitario}</td>
                </tr>
            `).join('')
            : `
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        Nenhum item encontrado
                    </td>
                </tr>
            `;

        $('#modalVisualizar .modal-body').html(`

            <!-- HEADER INFO -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1">${data.MercadoNome}</h6>
                    <small class="text-muted">Data: ${data.DataEmissao}</small><br>
                    <strong>Total: R$ ${data.ValorTotal}</strong>
                </div>

                <!-- AÇÕES -->
                <div class="d-flex gap-2">

                    <button id="btnVerPdf" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf"></i>
                    </button>

                    <button id="btnGerarLancamento" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-dollar-sign"></i>
                    </button>

                    <button id="btnCancelarNota" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-ban"></i>
                    </button>

                </div>
            </div>

            <!-- TABELA -->
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th class="text-end">Qtd</th>
                        <th class="text-end">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    ${itensHtml}
                </tbody>
            </table>

        `);

        ModalHelper.abrir('modalVisualizar');
    },

    bindEvents() {

        // FECHAR MODAL (botão footer)
        $(document).on('click', '.btn-fechar-visualizar', () => {
            ModalHelper.fechar('modalVisualizar');
        });

        // 📄 PDF
        $(document).on('click', '#btnVerPdf', () => {
            if (!notaAtualPdf) {
                showToast('PDF não encontrado para esta nota', 'error');
                return;
            }

            window.open('/' + notaAtualPdf, '_blank');
        });

        // 💰 GERAR LANÇAMENTO
        $(document).on('click', '#btnGerarLancamento', () => {
            if (!notaAtualId) return;
            if (!confirm('Deseja gerar o lançamento financeiro desta nota?')) return;
            NFEService.gerarLancamento(notaAtualId)
                .done(res => {
                    if (res.success) {
                        showToast('Lançamento gerado com sucesso', 'success');
                        if (NFETable?.table) {
                            NFETable.table.ajax.reload(null, false);
                        }
                    } else {
                        alert(res.message);
                    }
                })
                .fail(() => {
                    alert('Erro ao gerar lançamento');
                });

        });

        // ❌ CANCELAR NOTA
        $(document).on('click', '#btnCancelarNota', () => {
            if (!notaAtualId) return;
            if (!confirm('Deseja cancelar esta nota fiscal?')) return;
            NFEService.cancelar(notaAtualId)
                .done(res => {
                    if (res.success) {
                        showToast('Nota cancelada', 'success');
                        ModalHelper.fechar('modalVisualizar');
                        if (NFETable?.table) {
                            NFETable.table.ajax.reload(null, false);
                        }
                    } else {
                        alert(res.message);
                    }
                })
                .fail(() => {
                    alert('Erro ao cancelar nota');
                });

        });

    }

};