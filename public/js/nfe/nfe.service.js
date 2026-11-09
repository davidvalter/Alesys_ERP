/**
 * Autocomplete de Produtos com navegação por teclado
 * 
 * @file /js/nfe/nfe.service.js
 */

const NFEService = {

    getNota(id) {
        return $.get('/api/get_nota_fiscal.php', { Id: id });
    },

    cancelar(id) {
        return $.ajax({
            url: '/api/cancelar_nota_fiscal.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ Id: id })
        });
    },

    gerarLancamento(id) {
        return $.post('/api/gerar_lancamento_nf.php', { Id: id });
    },

    getPdfUrl(id) {
        return `/api/gerar_pdf_nf.php?Id=${id}`;
    }

};