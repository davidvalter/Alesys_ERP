/* /js/empresas.js — Configuração da Empresa e da integração ALENFE */
const Empresas = {
    config: {
        urlGetEmpresa: '/api/get_empresa.php',
        urlSaveEmpresa: '/api/save_empresa.php',
    },

    init() {
        this.bindFormularios();
        this.bindMascaras();
    },

    bindFormularios() {
        document.getElementById('formEmpresa').addEventListener('submit', (e) => {
            e.preventDefault();
            this.salvarEmpresa();
        });
        document.getElementById('formAlenfe').addEventListener('submit', (e) => {
            e.preventDefault();
            this.salvarAlenfe();
        });
    },

    bindMascaras() {
        // Máscaras simples (sem dependência externa)
        const cnpj = document.getElementById('cnpj');
        if (cnpj) cnpj.addEventListener('input', () => cnpj.value = this.maskCnpj(cnpj.value));

        const ceps = document.querySelectorAll('.cep-mask');
        ceps.forEach(el => el.addEventListener('input', () => el.value = this.maskCep(el.value)));

        const tel = document.getElementById('telefone');
        if (tel) tel.addEventListener('input', () => tel.value = this.maskTelefone(tel.value));
    },

    /* ========== Salvar Empresa ========== */
    async salvarEmpresa() {
        const dados = {
            id: document.getElementById('empresaId').value,
            razaoSocial: document.getElementById('razaoSocial').value,
            nomeFantasia: document.getElementById('nomeFantasia').value,
            cnpj: document.getElementById('cnpj').value.replace(/\D/g, ''),
            inscricaoEstadual: document.getElementById('inscricaoEstadual').value,
            logradouro: document.getElementById('logradouro').value,
            numero: document.getElementById('numero').value,
            bairro: document.getElementById('bairro').value,
            cidade: document.getElementById('cidade').value,
            uf: document.getElementById('uf').value.toUpperCase(),
            cep: document.getElementById('cep').value.replace(/\D/g, ''),
            telefone: document.getElementById('telefone').value,
            email: document.getElementById('email').value,
        };
        await this.enviar('/api/save_empresa.php', { tipo: 'empresa', dados });
    },

    /* ========== Salvar Configurações ALENFE ========== */
    async salvarAlenfe() {
        const dados = {
            alenfeUrl: document.getElementById('alenfeUrl').value.trim(),
            alenfeApiKey: document.getElementById('alenfeApiKey').value.trim(),
            alenfeWebhookToken: document.getElementById('alenfeWebhookToken').value.trim(),
            alenfeCpfPadrao: document.getElementById('alenfeCpfPadrao').value.trim(),
            alenfeConsumidorPadrao: document.getElementById('alenfeConsumidorPadrao').value.trim(),
            alenfeEndLogradouro: document.getElementById('alenfeEndLogradouro').value.trim(),
            alenfeEndNumero: document.getElementById('alenfeEndNumero').value.trim(),
            alenfeEndBairro: document.getElementById('alenfeEndBairro').value.trim(),
            alenfeEndCidade: document.getElementById('alenfeEndCidade').value.trim(),
            alenfeEndUf: document.getElementById('alenfeEndUf').value.trim().toUpperCase(),
            alenfeEndCep: document.getElementById('alenfeEndCep').value.replace(/\D/g, ''),
        };
        await this.enviar('/api/save_empresa.php', { tipo: 'alenfe', dados });
    },

    /* ========== Envio genérico ========== */
    async enviar(url, body) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.success) {
                this.toast(json.message || 'Erro ao salvar', 'danger');
                return;
            }
            this.toast(json.message || 'Salvo com sucesso!', 'success');
        } catch (err) {
            this.toast(err.message || 'Erro de rede', 'danger');
        }
    },

    /* ========== Recarregar dados ========== */
    carregar() {
        location.reload();
    },

    /* ========== Toast ========== */
    toast(mensagem, tipo = 'success') {
        const container = document.getElementById('toastContainer');
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${tipo} border-0 show`;
        el.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${esc(mensagem)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;
        container.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    },

    /* ========== Máscaras ========== */
    maskCnpj(v) {
        v = v.replace(/\D/g, '').slice(0, 14);
        if (v.length <= 2) return v;
        if (v.length <= 5) return v.replace(/^(\d{2})(\d{0,3})/, '$1.$2');
        if (v.length <= 8) return v.replace(/^(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
        if (v.length <= 12) return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
        return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
    },
    maskCep(v) {
        v = v.replace(/\D/g, '').slice(0, 8);
        return v.length > 5 ? v.replace(/^(\d{5})(\d{0,3})/, '$1-$2') : v;
    },
    maskTelefone(v) {
        v = v.replace(/\D/g, '').slice(0, 11);
        if (v.length <= 2) return v;
        if (v.length <= 6) return v.replace(/^(\d{2})(\d{0,4})/, '($1) $2');
        if (v.length <= 10) return v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        return v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    },
};

/* ---------- Utilitário ---------- */
function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, c =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

document.addEventListener('DOMContentLoaded', () => Empresas.init());