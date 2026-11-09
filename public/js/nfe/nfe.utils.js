/**
 * Módulo de Gerenciamento de Notas Fiscais
 * 
 * @file /js/nfe/nfe.utils.js
 * @version 1.0.0
 */

const NFEUtils = {

    initMasks() {

        if ($.fn.inputmask) {
            $('.money-mask').inputmask('currency', {
                prefix: 'R$ ',
                rightAlign: false
            });
        }

    },

    formatarChaveAcesso(valor) {
        if (!valor) return '';

        return valor
            .replace(/\D/g, '')
            .substring(0, 44)
            .replace(/(.{4})/g, '$1 ')
            .trim();
    },

    limparChaveAcesso(valor) {
        if (!valor) return '';
        return valor.replace(/\D/g, '');
    }

};

const ModalHelper = {

    abrir(id) {
        const el = document.getElementById(id);
        if (!el) return;

        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
    },

    fechar(id) {
        const el = document.getElementById(id);
        if (!el) return;

        const modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.hide();
    }

};

function showToast(message, type = 'info') {

    console.log(`[${type.toUpperCase()}] ${message}`);

    // fallback simples (pode evoluir depois)
    const bg = {
        success: '#198754',
        error: '#dc3545',
        info: '#0d6efd',
        warning: '#ffc107'
    }[type] || '#0d6efd';

    const toast = $(`
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${bg};
            color: #fff;
            padding: 10px 15px;
            border-radius: 6px;
            z-index: 9999;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        ">
            ${message}
        </div>
    `);

    $('body').append(toast);

    setTimeout(() => {
        toast.fadeOut(300, () => toast.remove());
    }, 2500);
}

// NORMALIZAR NOMES PARA MATCH DO FORNECEDOR
function normalizarNome(str) {
    if (!str) return '';

    return str
        .toLowerCase()
        .normalize("NFD").replace(/[\u0300-\u036f]/g, '') // remove acento
        .replace(/[^a-z0-9\s]/g, '') // remove símbolos
        .replace(/\b(sa|s a|ltda|eireli)\b/g, '') // remove tipo empresa
        .replace(/\s+/g, ' ')
        .trim();
}

//CALCULAR SCORE PARA MATCH DO FORNECEDOR
function calcularScore(a, b) {
    if (!a || !b) return 0;

    let score = 0;

    const palavrasA = a.split(' ');
    const palavrasB = b.split(' ');

    palavrasA.forEach(p => {
        if (palavrasB.includes(p)) score++;
    });

    return score;
}