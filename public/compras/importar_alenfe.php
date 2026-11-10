<?php
session_start();
if (empty($_SESSION['alenfe_compras_csrf'])) {
    $_SESSION['alenfe_compras_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['alenfe_compras_csrf'];
$moduloAtual = 'compras.notasfiscais';
$tituloPagina = 'Importar compras do AleNFE';
$tituloConteudo = 'Importação manual do AleNFE';
require_once __DIR__ . '/../templates/base_protected_v2.php';
require_once __DIR__ . '/../../configs/database.php';

$notas = [];
$erroSchema = null;
try {
    $pdo = getDB();
    $notas = $pdo->query("
        SELECT i.Id, i.Numero, i.Serie, i.ChaveAcesso, i.FornecedorNome, i.FornecedorCNPJ,
               i.DataEmissao, i.ValorTotal, i.SituacaoAleNFE, i.StatusImportacao, i.NFeId,
               (SELECT COUNT(*) FROM AleNFEComprasImportacaoItens x WHERE x.ImportacaoId=i.Id) AS QuantidadeItens
        FROM AleNFEComprasImportacao i
        ORDER BY i.DataEmissao DESC, i.Id DESC
        LIMIT 250
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $erroSchema = 'A estrutura da caixa de entrada ainda não está instalada. Execute database/migrations/20261110_alenfe_importacao_compras.sql.';
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <p class="text-muted mb-0">Consulte as notas centralizadas no AleNFE, confira os vínculos e confirme a importação no Alesys.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="notas_fiscais.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Notas fiscais</a>
            <button type="button" id="btnConsultar" class="btn btn-success" <?= $erroSchema ? 'disabled' : '' ?>>
                <i class="fas fa-rotate me-1"></i>Consultar AleNFE
            </button>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-circle-info me-1"></i>
        A confirmação exige o XML completo para extrair <code>tpNF</code> e <code>natOp</code>. Os itens precisam estar vinculados a produtos e o fornecedor deve ser conferido. Esta importação não movimenta o estoque.
    </div>
    <?php if ($erroSchema): ?>
        <div class="alert alert-warning"><?= e($erroSchema) ?></div>
    <?php endif; ?>
    <div id="mensagemImportacao" class="alert d-none" role="alert"></div>

    <div class="card card-default">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>Número / série</th><th>Emissão</th><th>Fornecedor informado pelo AleNFE</th><th class="text-end">Valor</th><th>Itens</th><th>Status</th><th class="text-end">Ação</th></tr>
                    </thead>
                    <tbody id="listaNotas">
                    <?php if (!$notas): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma nota na caixa de entrada. Use “Consultar AleNFE” para buscar notas.</td></tr>
                    <?php else: foreach ($notas as $n): ?>
                        <tr>
                            <td><strong><?= e($n['Numero']) ?></strong><small class="text-muted d-block">Série <?= e($n['Serie'] ?: '—') ?></small></td>
                            <td><?= $n['DataEmissao'] ? e(date('d/m/Y H:i', strtotime($n['DataEmissao']))) : '—' ?></td>
                            <td><?= e($n['FornecedorNome'] ?: 'Fornecedor sem identificação') ?><small class="text-muted d-block"><?= e($n['FornecedorCNPJ'] ?: 'CNPJ indisponível') ?></small></td>
                            <td class="text-end">R$ <?= e(number_format((float)$n['ValorTotal'], 2, ',', '.')) ?></td>
                            <td><?= (int)$n['QuantidadeItens'] ?></td>
                            <td>
                                <?php if ($n['StatusImportacao'] === 'importada'): ?>
                                    <span class="badge bg-success">Importada #<?= (int)$n['NFeId'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= e($n['SituacaoAleNFE'] ?: 'Pendente de revisão') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($n['StatusImportacao'] === 'importada'): ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled>Concluída</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-success btnRevisar" data-id="<?= (int)$n['Id'] ?>">Revisar / importar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRevisaoAleNFE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title">Revisar nota de compra</h5><small id="subtituloNota" class="text-muted"></small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="revisaoErro" class="alert alert-warning d-none"></div>
                <div id="revisaoConteudo" class="d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><div class="small text-muted">Chave de acesso</div><code id="chaveNota"></code></div>
                        <div class="col-md-3"><div class="small text-muted">Data de emissão</div><strong id="dataNota"></strong></div>
                        <div class="col-md-3"><div class="small text-muted">Valor total</div><strong id="valorNota"></strong></div>
                        <div class="col-md-3"><div class="small text-muted">Tipo / natureza (XML)</div><strong id="tipoNota"></strong></div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <label for="fornecedorAlesys" class="form-label fw-semibold">Fornecedor no Alesys</label>
                            <select id="fornecedorAlesys" class="form-select"></select>
                            <div id="avisoCnpj" class="form-text"></div>
                        </div>
                    </div>
                    <h6>Vínculo dos produtos</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Item recebido</th><th>Qtd / valor unitário</th><th>Produto do Alesys</th></tr></thead>
                            <tbody id="itensNota"></tbody>
                        </table>
                    </div>
                    <div class="alert alert-secondary small mb-0">Os vínculos confirmados são memorizados por CNPJ do fornecedor, código do item e NCM para sugerir automaticamente nas próximas notas.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" id="btnConfirmarImportacao" class="btn btn-success" disabled><i class="fas fa-check me-1"></i>Confirmar importação</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer_protected_v2.php'; ?>
<script>
(() => {
    const csrfToken = <?= json_encode($csrfToken) ?>;
    const tbody = document.getElementById('listaNotas');
    const message = document.getElementById('mensagemImportacao');
    const modalEl = document.getElementById('modalRevisaoAleNFE');
    const modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;
    let activeNote = null;
    let activeItems = [];
    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const showMessage = (text, type='info') => {
        message.className = 'alert alert-' + type;
        message.textContent = text;
    };
    const loadList = () => location.reload();

    document.getElementById('btnConsultar')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consultando…';
        message.className = 'alert alert-info';
        message.textContent = 'Consultando o AleNFE…';
        try {
            const response = await fetch('/api/alenfe_compras_consultar.php', {method:'POST', credentials:'same-origin', headers:{'X-CSRF-Token':csrfToken}});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Falha ao consultar AleNFE.');
            showMessage(data.message + (data.ignoradas ? ' ' + data.ignoradas + ' registro(s) sem chave válida foram ignorados.' : ''), 'success');
            setTimeout(loadList, 800);
        } catch (error) {
            showMessage(error.message, 'danger');
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-rotate me-1"></i>Consultar AleNFE';
        }
    });

    async function review(id) {
        activeNote = null;
        activeItems = [];
        document.getElementById('revisaoErro').classList.add('d-none');
        document.getElementById('revisaoConteudo').classList.add('d-none');
        document.getElementById('btnConfirmarImportacao').disabled = true;
        document.getElementById('subtituloNota').textContent = 'Carregando XML e correspondências…';
        modal?.show();
        try {
            const response = await fetch('/api/alenfe_compras_detalhe.php?id=' + encodeURIComponent(id), {credentials:'same-origin'});
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Não foi possível carregar a nota.');
            activeNote = data.nota;
            activeItems = data.itens;
            document.getElementById('subtituloNota').textContent = 'Nota ' + activeNote.Numero + (activeNote.Serie ? ' · Série ' + activeNote.Serie : '');
            document.getElementById('chaveNota').textContent = activeNote.ChaveAcesso;
            document.getElementById('dataNota').textContent = activeNote.DataEmissao ? new Date(activeNote.DataEmissao.replace(' ', 'T')).toLocaleString('pt-BR') : '—';
            document.getElementById('valorNota').textContent = Number(activeNote.ValorTotal).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
            document.getElementById('tipoNota').textContent = activeNote.Tipo + ' · ' + activeNote.NaturezaOperacao;
            const supplier = document.getElementById('fornecedorAlesys');
            supplier.innerHTML = '<option value="">Selecione o fornecedor…</option>' + data.mercados.map(m => '<option value="'+esc(m.Codigo)+'">'+esc(m.Nome)+' · '+esc(m.CNPJ)+'</option>').join('');
            if (activeNote.MercadoCodigoSugerido) supplier.value = activeNote.MercadoCodigoSugerido;
            const sourceCnpj = (activeNote.FornecedorCNPJ || '').replace(/\D/g,'');
            const matched = data.mercados.find(m => (m.CNPJ || '').replace(/\D/g,'') === sourceCnpj);
            document.getElementById('avisoCnpj').textContent = matched ? 'Correspondência automática pelo CNPJ: ' + matched.CNPJ : 'Não há fornecedor cadastrado com este CNPJ. Selecione manualmente e confira o cadastro.';
            document.getElementById('itensNota').innerHTML = activeItems.map((item, index) => {
                const options = item.sugestoes.map(p => '<option value="'+esc(p.Codigo)+'">'+esc(p.Nome)+' · '+esc(p.UnidadeMedida || 'UN')+(p.NCM ? ' · NCM '+esc(p.NCM) : '')+'</option>').join('');
                return '<tr><td><strong>'+esc(item.Descricao)+'</strong><small class="text-muted d-block">Código '+esc(item.CodigoFornecedor || '—')+' · NCM '+esc(item.NCM || '—')+' · CFOP '+esc(item.CFOP || '—')+'</small></td><td>'+Number(item.Quantidade).toLocaleString('pt-BR')+' × '+Number(item.ValorUnitario).toLocaleString('pt-BR',{style:'currency',currency:'BRL'})+'</td><td><input type="search" class="form-control form-control-sm mb-1 produtoBusca" data-item="'+esc(item.Id)+'" placeholder="Buscar produto por nome, código ou NCM"><select class="form-select form-select-sm produtoMap" data-item="'+esc(item.Id)+'"><option value="">Selecione produto…</option>'+options+'</select></td></tr>';
            }).join('');
            document.querySelectorAll('.produtoMap').forEach((select, i) => {
                if (activeItems[i]?.ProdutoCodigo) {
                    const v = String(activeItems[i].ProdutoCodigo);
                    if (![...select.options].some(o => o.value === v)) {
                        const match = activeItems[i].sugestoes.find(p => String(p.Codigo) === v);
                        if (match) select.add(new Option(match.Nome, v));
                    }
                    select.value = v;
                }
                select.addEventListener('change', updateConfirmState);
            });
            supplier.onchange = updateConfirmState;
            document.querySelectorAll('.produtoBusca').forEach(input => {
                let timer;
                input.addEventListener('input', () => {
                    clearTimeout(timer);
                    const item = activeItems.find(x => String(x.Id) === input.dataset.item);
                    const select = document.querySelector('.produtoMap[data-item="'+input.dataset.item+'"]');
                    const query = input.value.trim();
                    if (query.length < 2) {
                        const suggestions = item?.sugestoes || [];
                        select.innerHTML = '<option value="">Selecione produto…</option>' + suggestions.map(p => '<option value="'+esc(p.Codigo)+'">'+esc(p.Nome)+' · '+esc(p.UnidadeMedida || 'UN')+(p.NCM ? ' · NCM '+esc(p.NCM) : '')+'</option>').join('');
                        if (item?.ProdutoCodigo) select.value = String(item.ProdutoCodigo);
                        updateConfirmState();
                        return;
                    }
                    timer = setTimeout(async () => {
                        try {
                            const response = await fetch('/api/alenfe_compras_produtos.php?q=' + encodeURIComponent(query), {credentials:'same-origin'});
                            const data = await response.json();
                            if (!response.ok || !data.success) throw new Error(data.message || 'Falha ao buscar produtos.');
                            const previous = select.value;
                            select.innerHTML = '<option value="">Selecione produto…</option>' + data.produtos.map(p => '<option value="'+esc(p.Codigo)+'">'+esc(p.Nome)+' · '+esc(p.UnidadeMedida || 'UN')+(p.NCM ? ' · NCM '+esc(p.NCM) : '')+'</option>').join('');
                            if ([...select.options].some(o => o.value === previous)) select.value = previous;
                            updateConfirmState();
                        } catch (error) {
                            showMessage(error.message, 'danger');
                        }
                    }, 250);
                });
            });
            document.getElementById('revisaoConteudo').classList.remove('d-none');
            if (activeNote.ImportadaNFeId) {
                throw new Error('Esta chave de acesso já existe no Alesys como nota #' + activeNote.ImportadaNFeId + '.');
            }
            updateConfirmState();
        } catch (error) {
            const box = document.getElementById('revisaoErro');
            box.textContent = error.message;
            box.classList.remove('d-none');
        }
    }
    function updateConfirmState() {
        const allSelected = document.querySelectorAll('.produtoMap').length > 0 && [...document.querySelectorAll('.produtoMap')].every(s => s.value);
        document.getElementById('btnConfirmarImportacao').disabled = !(activeNote && allSelected && document.getElementById('fornecedorAlesys').value);
    }
    tbody?.addEventListener('click', e => {
        const button = e.target.closest('.btnRevisar');
        if (button) review(button.dataset.id);
    });
    document.getElementById('btnConfirmarImportacao').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importando…';
        try {
            const mappings = {};
            document.querySelectorAll('.produtoMap').forEach(s => mappings[s.dataset.item] = s.value);
            const response = await fetch('/api/alenfe_compras_importar.php', {
                method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},
                body:JSON.stringify({importacaoId:activeNote.id, mercadoCodigo:document.getElementById('fornecedorAlesys').value, itens:mappings})
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Falha ao importar nota.');
            modal?.hide();
            showMessage(data.message + ' Número Alesys: ' + data.notaId + '.', 'success');
            setTimeout(loadList, 900);
        } catch (error) {
            const box = document.getElementById('revisaoErro');
            box.textContent = error.message;
            box.classList.remove('d-none');
        } finally {
            button.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar importação';
            updateConfirmState();
        }
    });
})();
</script>
