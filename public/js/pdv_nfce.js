/* /js/pdv_nfce.js — integração ALENFE no PDV */
const PDVNFCE = {
    pedidoId: null,
    pollingTimer: null,

    config: {
        intervaloMs: 2000,    // polling
        timeoutMs: 120000,    // tempo máximo esperando a SEFAZ (2 min)
        urlEmitir: '/api/emitir_nfce.php',
        urlStatus: '/api/status_nfce.php',
        urlConsultar: '/api/consultar_nfce.php',
    },

    init() {
        this.injetarModal();
        this.injetarQrLib();
    },

    injetarModal() {
        if (document.getElementById('modalNotaFiscal')) return;
        const div = document.createElement('div');
        div.innerHTML = `
        <div class="modal fade" id="modalNotaFiscal" tabindex="-1" data-bs-backdrop="static">
          <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
            <div class="modal-content">
              <div class="modal-header nao-imprimir">
                <h5 class="modal-title" id="notaTitulo">Nota Fiscal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body p-2" id="notaCorpo">
                <div id="notaConteudo" class="cupom"></div>
              </div>
              <div class="modal-footer nao-imprimir" id="notaAcoes"></div>
            </div>
          </div>
        </div>`;
        document.body.appendChild(div.firstElementChild);
    },

    injetarQrLib() {
        if (typeof qrcode !== 'undefined') return;
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js';
        document.head.appendChild(s);
    },

    /* ========== 1. Emissão ========== */
    async emitir(pedidoId) {
        this.pedidoId = pedidoId;
        this.abrirAguardando('Enviando nota à SEFAZ...');
        try {
            const res = await fetch(this.config.urlEmitir, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pedidoId })
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.success) {
                this.abrirErro(json.message || 'Falha ao enviar a nota para o ALENFE');
                return;
            }
            this.iniciarPolling();
        } catch (err) {
            this.abrirErro(err.message || 'Erro de rede ao enviar a nota');
        }
    },

    /* ========== 2. Polling leve ========== */
    iniciarPolling() {
        const inicio = Date.now();
        clearInterval(this.pollingTimer);
        this.abrirAguardando('Aguardando autorização da SEFAZ...');
        this.pollingTimer = setInterval(async () => {
            if (Date.now() - inicio > this.config.timeoutMs) {
                clearInterval(this.pollingTimer);
                this.abrirPendente();
                return;
            }
            try {
                const res = await fetch(`${this.config.urlStatus}?pedido_id=${this.pedidoId}`, { cache: 'no-store' });
                const json = await res.json();
                if (json.success && !['PENDENTE', 'ENVIADA'].includes(json.status)) {
                    clearInterval(this.pollingTimer);
                    this.processarStatus(json);
                }
            } catch (e) { /* segue tentando */ }
        }, this.config.intervaloMs);
    },

    /* ========== 3. Roteamento por status ========== */
    processarStatus(json) {
        switch (json.status) {
            case 'AUTORIZADA':
                this.abrirNota(json.dados);
                break;
            case 'CONTINGENCIA':
                this.abrirContingencia(json.dados, json.motivoRejeicao);
                break;
            default:
                this.abrirErro(json.motivoRejeicao || 'Nota ' + json.status);
        }
    },

    /* ========== Telas ========== */
    abrirAguardando(mensagem) {
        document.getElementById('notaTitulo').textContent = 'Nota Fiscal';
        document.getElementById('notaConteudo').innerHTML =
            `<div class="text-center p-4"><div class="spinner-border text-primary mb-3"></div><p>${esc(mensagem)}</p></div>`;
        document.getElementById('notaAcoes').innerHTML =
            `<button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>`;
        new bootstrap.Modal(document.getElementById('modalNotaFiscal')).show();
    },

    abrirNota(dados) {
        dados = dados || {};
        const e = dados.emitente || {};
        const itens = (dados.itens || []).map((i, idx) => `
            <tr>
                <td class="col-item">${idx + 1}</td>
                <td class="col-cod">${esc(i.codigo || '')}</td>
                <td class="col-desc">${esc(i.descricao)}
                    <div class="cupom-valores">${fmtQtd(i.quantidade)} x ${fmt(i.valorUnitario)}</div>
                </td>
                <td class="col-valor">${fmt(i.valorTotal)}</td>
            </tr>`).join('');
        const pag = (dados.pagamentos || []).map(p =>
            `<div class="d-flex justify-content-between">
                <span>${esc(p.descricao || p.formaPagamento)}</span>
                <span>${fmt(p.valor)}</span>
             </div>`).join('');
        const total = dados.totais?.vNF ?? dados.totais?.vProd;

        document.getElementById('notaTitulo').textContent = 'NFC-e Autorizada';
        document.getElementById('notaConteudo').innerHTML = `
            <div class="cupom">
                <div class="text-center fw-bold fs-6">${esc(e.nomeFantasia || e.razaoSocial || '')}</div>
                <div class="text-center">${esc(e.razaoSocial || '')}</div>
                <div class="text-center">${esc(e.logradouro || '')}, ${esc(e.numero || '')} - ${esc(e.bairro || '')}</div>
                <div class="text-center">${esc(e.cidade || '')}/${esc(e.uf || '')} - CEP ${esc(e.cep || '')}</div>
                <div class="d-flex justify-content-between cupom-linha">
                    <span>CNPJ: ${esc(e.cnpj || '')} IE: ${esc(e.ie || '')} IM: ${esc(e.im || '')}</span>
                    <span>${fmtDataHora(dados.dataEmissao)}</span>
                </div>
                <hr class="my-1">
                <div class="text-center fw-bold">Documento auxiliar da nota fiscal de Consumidor Eletronica</div>
                <div class="d-flex justify-content-between">
                    <span>Nº ${String(dados.numero || '').padStart(6, '0')}</span>
                    <span>Série ${esc(dados.serie || '')}</span>
                </div>
                ${dados.ambiente === '2'
                ? '<div class="text-center text-danger fw-bold" style="font-size:10px;">AMBIENTE DE HOMOLOGAÇÃO - SEM VALOR FISCAL</div>'
                : ''}
                <hr class="my-1">
                <table class="cupom-tabela">
                    <thead>
                        <tr><th>ITEM</th><th>CÓD.</th><th>DESC.</th><th>VALOR</th></tr>
                    </thead>
                    <tbody>${itens}</tbody>
                </table>
                <hr class="my-1">
                ${pag}
                ${(dados.troco || 0) > 0
                ? `<div class="d-flex justify-content-between"><span>Troco</span><span>${fmt(dados.troco)}</span></div>`
                : ''}
                ${(dados.totais?.vDesc || 0) > 0
                ? `<div class="d-flex justify-content-between"><span>Desconto</span><span>${fmt(dados.totais.vDesc)}</span></div>`
                : ''}
                <div class="d-flex justify-content-between fw-bold cupom-total">
                    <span>TOTAL</span><span>${fmt(total)}</span>
                </div>
                <hr class="my-1">
                    <div id="qrNota" class="d-flex justify-content-center my-2"></div>
                    <div class="text-center text-break" style="font-size:9px;">Chave: ${esc(dados.chaveAcesso || '')}</div>
                    <div class="text-center" style="font-size:9px;">Protocolo: ${esc(dados.protocolo || '')}</div>
                    <div class="text-center text-break" style="font-size:9px;">Consulta: ${esc(dados.urlChave || '')}</div>
                    <hr class="my-1">
                    <!-- Espaço reservado para a senha do cliente (a implementar) -->
                    <div class="senha-cliente">
                        <div class="d-flex justify-content-between">
                            <span>Senha do cliente</span>
                            <span>________</span>
                        </div>
                    </div>
                    <hr class="my-1">
                    <div class="text-center" style="font-size:9px;">NFC-e emitida pelo sistema ALESYS desenvolvido pela empresa Gouvtec Informática</div>
            </div>`;
        this.gerarQr(document.getElementById('qrNota'), dados.qrCode);
        document.getElementById('notaAcoes').innerHTML = `
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
            <button class="btn btn-primary" onclick="PDVNFCE.imprimir()">🖨 Imprimir</button>`;
        new bootstrap.Modal(document.getElementById('modalNotaFiscal')).show();
    },

    abrirContingencia(dados, motivo) {
        dados = dados || {};
        const e = dados.emitente || {};
        const itens = (dados.itens || []).map((i, idx) => `
            <tr>
                <td class="col-item">${idx + 1}</td>
                <td class="col-cod">${esc(i.codigo || '')}</td>
                <td class="col-desc">${esc(i.descricao)}
                    <div class="cupom-valores">${fmtQtd(i.quantidade)} x ${fmt(i.valorUnitario)}</div>
                </td>
                <td class="col-valor">${fmt(i.valorTotal)}</td>
            </tr>`).join('');
        const pag = (dados.pagamentos || []).map(p =>
            `<div class="d-flex justify-content-between"><span>${esc(p.descricao || p.formaPagamento)}</span><span>${fmt(p.valor)}</span></div>`).join('');
        const total = dados.totais?.vNF ?? dados.totais?.vProd;

        document.getElementById('notaTitulo').textContent = 'Nota em Contingência';
        document.getElementById('notaConteudo').innerHTML = `
            <div class="cupom">
                <div class="text-center fw-bold fs-6">${esc(e.nomeFantasia || e.razaoSocial || '')}</div>
                <div class="text-center">${esc(e.razaoSocial || '')}</div>
                <div class="text-center">CNPJ: ${esc(e.cnpj || '')} IE: ${esc(e.ie || '')}</div>
                <hr class="my-1">
                <div class="text-center fw-bold text-danger">NOTA FISCAL EM CONTINGÊNCIA</div>
                <div class="text-center" style="font-size:10px;">Nota Auxiliar em Contingência — sem valor fiscal até regularização</div>
                ${motivo ? `<div class="text-center" style="font-size:10px;">Motivo: ${esc(motivo)}</div>` : ''}
                <hr class="my-1">
                <table class="cupom-tabela">
                    <thead>
                        <tr><th>ITEM</th><th>CÓD.</th><th>DESC.</th><th>VALOR</th></tr>
                    </thead>
                    <tbody>${itens}</tbody>
                </table>
                <hr class="my-1">
                ${pag}
                <div class="d-flex justify-content-between fw-bold cupom-total">
                    <span>TOTAL</span><span>${fmt(total)}</span>
                </div>
                ${dados.chaveAcesso
                ? `<div class="text-center text-break" style="font-size:9px;">Chave: ${esc(dados.chaveAcesso)}</div>`
                : ''}
            </div>`;
        document.getElementById('notaAcoes').innerHTML = `
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
            <button class="btn btn-outline-primary" onclick="PDVNFCE.consultarSituacao()">Consultar situação</button>
            <button class="btn btn-primary" onclick="PDVNFCE.imprimir()">🖨 Imprimir</button>`;
        new bootstrap.Modal(document.getElementById('modalNotaFiscal')).show();
    },

    abrirErro(motivo) {
        document.getElementById('notaTitulo').textContent = 'Erro na Nota Fiscal';
        document.getElementById('notaConteudo').innerHTML = `
            <div class="alert alert-danger m-0">
                <div class="fw-bold mb-1">Não foi possível emitir a NFC-e</div>
                <div>${esc(motivo || 'Erro desconhecido. Tente novamente.')}</div>
            </div>`;
        document.getElementById('notaAcoes').innerHTML = `
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
            <button class="btn btn-primary" onclick="PDVNFCE.consultarSituacao()">Consultar novamente</button>`;
        new bootstrap.Modal(document.getElementById('modalNotaFiscal')).show();
    },

    abrirPendente() {
        document.getElementById('notaTitulo').textContent = 'Nota Fiscal';
        document.getElementById('notaConteudo').innerHTML = `
            <div class="alert alert-warning m-0">
                <div class="fw-bold mb-1">Aguardando retorno da SEFAZ</div>
                <div>A venda foi finalizada, mas ainda não recebemos o retorno da autorização. Você pode consultar a situação da nota a qualquer momento.</div>
            </div>`;
        document.getElementById('notaAcoes').innerHTML = `
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
            <button class="btn btn-primary" onclick="PDVNFCE.consultarSituacao()">Consultar situação</button>`;
        new bootstrap.Modal(document.getElementById('modalNotaFiscal')).show();
    },

    /* ========== Reconsulta manual ========== */
    async consultarSituacao() {
        this.abrirAguardando('Consultando situação da nota...');
        try {
            const res = await fetch(this.config.urlConsultar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pedidoId: this.pedidoId })
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.success) {
                this.abrirErro(json.message || 'Falha na consulta');
                return;
            }
            if (json.status && !['PENDENTE', 'ENVIADA'].includes(json.status)) {
                this.processarStatus(json);
            } else {
                this.abrirErro(json.message || 'Nota ainda pendente de processamento');
            }
        } catch (err) {
            this.abrirErro(err.message || 'Falha na consulta');
        }
    },

    /* ========== Helpers ========== */
    gerarQr(el, texto) {
        if (!el || !texto) return;
        el.innerHTML = '';
        if (typeof qrcode !== 'undefined') {
            const qr = qrcode(0, 'M');
            qr.addData(texto);
            qr.make();
            el.innerHTML = qr.createImgTag(6, 12);
        } else {
            el.innerHTML = `<small class="text-break">${esc(texto)}</small>`;
        }
    },

    imprimir() {
        window.print();
    },
};

/* ---------- Utilitários ---------- */
function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, c =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
function fmt(v) {
    const n = parseFloat(v);
    return isNaN(n) ? '0,00' : n.toFixed(2).replace('.', ',');
}
function fmtQtd(v) {
    const n = parseFloat(v);
    return isNaN(n) ? '0' : String(n).replace('.', ',');
}
function fmtDataHora(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    const p = (x) => String(x).padStart(2, '0');
    return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

document.addEventListener('DOMContentLoaded', () => PDVNFCE.init());

/* ============ Ao fechar o modal da NFC-e ============ */
document.addEventListener('DOMContentLoaded', function () {
    const modalNota = document.getElementById('modalNotaFiscal');
    if (!modalNota) return;

    modalNota.addEventListener('hidden.bs.modal', function () {
        // Remove backdrops órfãos que deixam a tela esmaecida
        document.querySelectorAll('.modal-backdrop').forEach(function (b) {
            b.remove();
        });
        // Limpa a classe que trava o scroll/escurece o fundo
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        // Recarrega o PDV para zerar o carrinho após a venda
        window.location.reload();
    });
});