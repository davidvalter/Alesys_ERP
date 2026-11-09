/**
 * Modal de Notas Fiscais
 * 
 * @file /js/nfe/nfe.modal.js
 * @version 2.0.0
 */

const NFEModal = {

    modal: null,
    form: null,
    itensContainer: null,
    arquivoOCRAtual: null,
    tmpFileOCR: null,

    init() {
        this.modal = $('#modalNota');
        this.form = $('#formNota');
        this.itensContainer = $('#itensContainer');

        this.bindEvents();
    },

    bindEvents() {

        // Formatar Chave de Acesso
        $('#chaveAcesso').on('input', function () {
            const cursor = this.selectionStart;
            const valor = NFEUtils.formatarChaveAcesso($(this).val());

            $(this).val(valor);

            this.setSelectionRange(valor.length, valor.length);
        });

        // Preview OCR
        $('#btnRemoverPreview').on('click', () => {
            $('#ocrPreview').hide();
            $('#btnRemoverPreview').hide();
        });

        // 🔥 OCR UNIFICADO (PDF + IMAGEM + CAMERA)
        $('#inputNotaOCR').on('change', async (e) => {

            const file = e.target.files[0];
            if (!file) return;

            console.log('Selecionado para OCR:', file.name);

            await this.processarOCR(file);

            // limpa input pra permitir reuso
            $('#inputNotaOCR').val('');
        });

        // FECHAR MODAL
        $('.close, .close-modal').on('click', () => this.fechar());

        // SUBMIT
        this.form.on('submit', (e) => {
            e.preventDefault();
            this.salvar();
        });

        // ADICIONAR ITEM
        $('#btnAdicionarItem').on('click', () => {
            this.adicionarItem();
        });

        // REMOVER ITEM
        $(document).on('click', '.btn-remover-item', function () {
            $(this).closest('.item-nota').remove();
            NFEModal.calcularTotal();
        });

        // CALCULAR TOTAL ITEM
        $(document).on('input', '.quantidade, .valor-unitario', function () {
            const item = $(this).closest('.item-nota');
            NFEModal.calcularItem(item);
        });

        // DESCONTO
        $('#descontoNota').on('input', () => {
            this.calcularTotal();
        });
    },

    abrirNova() {
        this.reset();
        $('#modalTitle').text('Nova Nota Fiscal');
        ModalHelper.abrir('modalNota');
    },

    abrir(data) {
        this.reset();

        const nota = data.nota;
        const itens = data.itens;

        $('#modalTitle').text('Editar Nota Fiscal');

        // Preencher campos
        $('#notaId').val(nota.Id);
        $('#numero').val(nota.Numero);
        $('#serie').val(nota.Serie);
        $('#tipo').val(nota.Tipo);
        $('#mercado').val(nota.fk_Mercados_Codigo);
        $('#dataEmissao').val(nota.DataEmissao);
        $('#dataRecebimento').val(nota.DataRecebimento);
        $('#naturezaOperacao').val(nota.NaturezaOperacao);
        $('#chaveAcesso').val(nota.ChaveAcesso);
        $('#valorTotal').val(this.formatMoney(nota.ValorTotal));
        $('#descontoNota').val(this.formatMoney(nota.Desconto || 0));
        $('#observacoes').val(nota.Observacoes);
        $('#pdfCaminhoExistente').val(nota.pdf_path);

        // Itens
        itens.forEach(item => {
            this.adicionarItem(item);
        });

        ModalHelper.abrir('modalNota');
    },

    fechar() {
        if (this.tmpFileOCR) {
            fetch('/api/nfe_cleanup_tmp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tmp_file: this.tmpFileOCR })
            });
        }

        ModalHelper.fechar('modalNota');
    },

    reset() {
        this.form[0].reset();
        this.itensContainer.html('');
        this.arquivoOCRAtual = null;
        this.tmpFileOCR = null;
        $('#notaId').val('');

        $('#pdfFileName').text('Nenhum arquivo');
        $('#pdfUploadError').hide();

        // OCR UI reset
        $('#ocrPreview').hide();
        $('#btnRemoverPreview').hide();
        $('#ocrLoader').hide();
        $('#ocrStatus').hide().text('');
    },

    adicionarItem(data = null) {

        const template = $('#itemTemplate').html();
        const item = $(template);

        if (data) {
            item.find('.produto-id').val(data.fk_Produtos_Codigo);
            item.find('.produto-search-input').val(data.ProdutoNome);
            item.find('.quantidade').val(data.Quantidade);
            item.find('.valor-unitario').val(this.formatMoney(data.ValorUnitario));
            item.find('.unidade-medida').val(data.UnidadeMedida);
        }

        this.itensContainer.append(item);

        if (data) {
            this.calcularItem(item);
        }
    },

    calcularItem(item) {

        const qtd = this.parseMoney(item.find('.quantidade').val());
        const vlr = this.parseMoney(item.find('.valor-unitario').val());

        const total = qtd * vlr;

        item.find('.valor-total').val(this.formatMoney(total));

        this.calcularTotal();
    },

    calcularTotal() {

        let total = 0;

        $('.item-nota').each(function () {
            const val = NFEModal.parseMoney($(this).find('.valor-total').val());
            total += val;
        });

        const desconto = this.parseMoney($('#descontoNota').val());

        total = total - desconto;
        if (total < 0) total = 0;

        $('#valorTotal').val(this.formatMoney(total));
    },

    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = () => {
                const base64 = reader.result.split(',')[1];
                resolve(base64);
            };

            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    },

    async salvar() {

        if ($('.item-nota').length === 0) {
            showToast('Adicione pelo menos 1 item na nota', 'error');
            return;
        }

        const erros = this.validarItensAntesSalvar();

        if (erros.length > 0) {
            alert(erros.join('\n'));
            return;
        }

        const dados = await this.getFormData();

        try {

            const response = await fetch('/api/save_nota_fiscal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });

            const res = await response.json();

            if (!res.success) {
                alert(res.message);
                return;
            }

            alert(res.message);

            this.fechar();
            NFETable.table.ajax.reload(null, false);

        } catch (e) {
            console.error(e);
            alert('Erro ao salvar nota');
        }
    },

    async getFormData() {

        const itens = [];

        $('.item-nota').each(function () {
            const item = $(this);

            itens.push({
                fk_Produtos_Codigo: item.find('.produto-id').val(),
                ProdutoNome: item.find('.produto-search-input').val(),
                Quantidade: NFEModal.parseMoney(item.find('.quantidade').val()),
                ValorUnitario: NFEModal.parseMoney(item.find('.valor-unitario').val()),
                UnidadeMedida: item.find('.unidade-medida').val(),
                DescricaoComplementar: item.find('.descricao-complementar').val()
            });
        });

        return {
            Id: $('#notaId').val() || null,
            Numero: $('#numero').val(),
            Serie: $('#serie').val(),
            Tipo: $('#tipo').val(),
            DataEmissao: $('#dataEmissao').val(),
            DataRecebimento: $('#dataRecebimento').val(),
            NaturezaOperacao: $('#naturezaOperacao').val(),
            ChaveAcesso: NFEUtils.limparChaveAcesso($('#chaveAcesso').val()),
            fk_Mercados_Codigo: $('#mercado').val(),
            Observacoes: $('#observacoes').val(),
            Desconto: this.parseMoney($('#descontoNota').val()),
            ValorTotal: this.parseMoney($('#valorTotal').val()),
            pdfCaminhoExistente: $('#pdfCaminhoExistente').val(),
            tmp_file: this.tmpFileOCR,
            itens
        };
    },

    // =========================
    // UTILS
    // =========================

    parseMoney(valor) {
        if (!valor) return 0;
        return parseFloat(
            valor.toString()
                .replace('R$', '')
                .replace(/\./g, '')
                .replace(',', '.')
        ) || 0;
    },

    formatMoney(valor) {
        if (!valor) valor = 0;

        return valor.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    },

    // PROCESSA O OCR
    async processarOCR(file) {

        if (!file) return;

        this.arquivoOCRAtual = file;
        console.log('Arquivo OCR:', file);
        $('#pdfCaminhoExistente').val('');

        // 🔒 validação
        if (file.size > 5 * 1024 * 1024) {
            showToast('Arquivo maior que 5MB', 'error');
            return;
        }

        const reader = new FileReader();

        reader.onload = async (e) => {

            try {

                // 🔥 UI START
                $('#ocrLoader').show();
                $('#ocrStatus').text('Enviando arquivo...').show();
                this.toggleForm(false);

                // 📸 preview imagem
                if (file.type.startsWith('image/')) {
                    $('#ocrPreviewImg').attr('src', e.target.result);
                    $('#ocrPreview').show();
                    $('#btnRemoverPreview').show();
                } else {
                    $('#ocrPreview').hide();
                    $('#btnRemoverPreview').hide();
                }

                const res = await fetch('/api/nfe_ocr_upload.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        fileBase64: e.target.result
                    })
                });

                $('#ocrStatus').text('Processando texto...');

                const text = await res.text();

                console.log('RAW RESPONSE:', text);

                let json;

                try {
                    json = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON válido:', text);
                    throw new Error('Resposta inválida do servidor');
                }

                console.log('OCR RESULT:', json);

                this.tmpFileOCR = json.tmp_file || null;

                if (!json.success) {
                    $('#ocrStatus').text('Erro ao processar OCR');
                    showToast(json.message || 'Erro no OCR', 'error');
                    return;
                }

                // 🔥 PREENCHIMENTO
                preencherCamposOCR(json.data);

                // 🔥 feedback UI
                $('#pdfFileName').text(file.name);
                $('#ocrStatus').text('Dados extraídos com sucesso!');

                showToast('Nota preenchida automaticamente', 'success');

                // 🔥 scroll automático
                document.querySelector('#numero').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

            } catch (err) {

                console.error('Erro OCR:', err);
                $('#ocrStatus').text('Erro ao processar OCR');
                showToast('Erro ao processar OCR', 'error');

            } finally {

                // 🔥 UI END
                $('#ocrLoader').hide();
                this.toggleForm(true);

                // remove highlight depois de um tempo
                setTimeout(() => {
                    $('.ocr-highlight').removeClass('ocr-highlight');
                }, 2000);
            }
        };

        reader.readAsDataURL(file);
    },

    // METODO PARA VALIDAR ITENS ANTES DE SALVAR
    validarItensAntesSalvar() {
        let erros = [];

        $('.item-nota').each(function (index) {
            const item = $(this);
            const produtoId = item.find('.produto-id').val();
            const produtoNome = item.find('.produto-search-input').val() || `Item ${index + 1}`;

            if (!produtoId || produtoId === '' || produtoId === '0') {
                erros.push(`O produto "${produtoNome}" não foi cadastrado previamente.`);
                item.addClass('border border-danger');
            } else {
                item.removeClass('border border-danger');
            }
        });

        return erros;
    },

    toggleForm(enable) {
        $('#formNota :input').prop('disabled', !enable);

        // mantém OCR sempre habilitado
        $('#inputNotaOCR').prop('disabled', false);
    }

};

function preencherCamposOCR(data) {

    if (!data) return;

    function setField(selector, value) {
        if (!value) return;
        $(selector).val(value).addClass('ocr-highlight');
    }

    setField('#numero', data.Numero);
    setField('#serie', data.Serie);

    setField('#dataEmissao', data.DataEmissao);
    setField('#dataRecebimento', data.DataRecebimento);

    if (data.ValorTotal) {
        $('#valorTotal')
            .val(NFEModal.formatMoney(data.ValorTotal))
            .addClass('ocr-highlight');
    }

    if (data.ChaveAcesso) {
        $('#chaveAcesso')
            .val(NFEUtils.formatarChaveAcesso(data.ChaveAcesso))
            .addClass('ocr-highlight');;
    }

    setField('#naturezaOperacao', data.NaturezaOperacao);

    // 🧠 Mercado (match inteligente simples)
    if (data.MercadoNome) {

        const nomeOCR = normalizarNome(data.MercadoNome);

        let melhorMatch = null;
        let melhorScore = 0;

        $("#mercado option").each(function () {

            const nomeOptionOriginal = $(this).text();
            const nomeOption = normalizarNome(nomeOptionOriginal);

            const score = calcularScore(nomeOCR, nomeOption);

            if (score > melhorScore) {
                melhorScore = score;
                melhorMatch = $(this);
            }
        });

        // 🔥 regra de confiança mínima
        if (melhorMatch && melhorScore >= 2) {
            melhorMatch.prop('selected', true).addClass('ocr-highlight');
        }
    }

    // 📦 ITENS AUTOMÁTICOS (se vier do backend)
    if (data.Itens && Array.isArray(data.Itens)) {

        NFEModal.itensContainer.html('');

        data.Itens.forEach(item => {
            NFEModal.adicionarItem({
                fk_Produtos_Codigo: item.fk_Produtos_Codigo || '',
                ProdutoNome: item.ProdutoNome || item.Descricao || '',
                Quantidade: item.Quantidade || 1,
                ValorUnitario: item.ValorUnitario || 0,
                UnidadeMedida: item.Unidade || 'UN'
            });
        });

        NFEModal.calcularTotal();
    }
}