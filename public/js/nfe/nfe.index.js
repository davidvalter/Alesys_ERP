/**
 * Módulo de Gerenciamento de Notas Fiscais
 * 
 * @file /js/nfe/nfe.index.js
 * @version 2.0.0
 */

$(document).ready(function () {
    NFE.init();
});

const NFE = {

    init() {
        console.log('NFE módulo iniciado');

        if (typeof NFEUtils !== 'undefined') {
            NFEUtils.initMasks();
        }

        NFETable.init();

        if (typeof NFEModal !== 'undefined') {
            NFEModal.init();
        }

        // 🔥 NOVO
        if (typeof NFEProduto !== 'undefined') {
            NFEProduto.init();
        }

        if (typeof NFEModalVisualizar !== 'undefined') {
            NFEModalVisualizar.init();
        }

        this.bindUI();
    },

    bindUI() {

        // NOVA NOTA
        $('#btnNovaNota').on('click', () => {
            console.log('Nova nota');

            if (typeof NFEModal !== 'undefined') {
                NFEModal.abrirNova();
            }
        });

        // EXPORTAR (placeholder)
        $('#btnExportar').on('click', () => {
            alert('Exportação ainda não implementada');
        });

        // SCAN MOBILE (placeholder)
        $('#btnScanMobile').on('click', () => {
            alert('Scan mobile ainda não implementado');
        });
    }

};