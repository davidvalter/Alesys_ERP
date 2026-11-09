<?php
/* /admin/empresas/index.php — Configuração da Empresa e da integração ALENFE */

session_start();

$moduloAtual = 'empresas';
$tituloPagina = 'Empresas';
$tituloConteudo = 'Cadastro da Empresa';

require_once __DIR__ . '/../../conf/autenticacao.php';
require_once __DIR__ . '/../../../configs/database.php';

// =========================
// Valida módulo
// =========================
if (!isset($moduloAtual)) {
    die('Módulo não definido na página');
}

// =========================
// Verifica permissão
// =========================
verificarPermissao($moduloAtual);

// =========================
// Valida usuário
// =========================
$usuarioId = $_SESSION['Usuario']['id'] ?? null;
if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

$db = getDB();

/* ---------- Busca a empresa ativa ---------- */
$empresa = null;
try {
    $stmt = $db->query("SELECT * FROM Empresa WHERE Ativo = 1 ORDER BY Id LIMIT 1");
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* tabela ainda não criada */
}

/* ---------- Busca as configurações ALENFE ---------- */
$configs = [];
try {
    $stmt = $db->query("SELECT Chave, Valor FROM Configuracoes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['Chave']] = $row['Valor'];
    }
} catch (Throwable $e) { /* tabela ainda não criada */
}

$empresa = $empresa ?: [
    'Id' => 0,
    'RazaoSocial' => '',
    'NomeFantasia' => '',
    'Cnpj' => '',
    'InscricaoEstadual' => '',
    'Logradouro' => '',
    'Numero' => '',
    'Bairro' => '',
    'Cidade' => '',
    'Uf' => '',
    'Cep' => '',
    'Telefone' => '',
    'Email' => '',
];

$cfg = fn($chave, $padrao) => $configs[$chave] ?? $padrao;

/* ---------- Helpers de máscara (usados no PHP para pré-preencher) ---------- */
function mascaraCnpj(string $v): string
{
    $v = preg_replace('/\D/', '', $v);
    if (strlen($v) !== 14)
        return $v;
    return substr($v, 0, 2) . '.' . substr($v, 2, 3) . '.' . substr($v, 5, 3)
        . '/' . substr($v, 8, 4) . '-' . substr($v, 12, 2);
}
function mascaraCep(string $v): string
{
    $v = preg_replace('/\D/', '', $v);
    if (strlen($v) !== 8)
        return $v;
    return substr($v, 0, 5) . '-' . substr($v, 5, 3);
}

// =========================
// CONTEÚDO
// =========================
ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Configurações</h1>
        <a href="/index.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs" id="configTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="tabEmpresa" data-bs-toggle="tab" data-bs-target="#paneEmpresa"
                type="button" role="tab">
                🏢 Dados da Empresa
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tabAlenfe" data-bs-toggle="tab" data-bs-target="#paneAlenfe" type="button"
                role="tab">
                ⚙️ Integração ALENFE
            </button>
        </li>
    </ul>

    <div class="tab-content pt-4" id="configTabsContent">

        <!-- ============ ABA EMPRESA ============ -->
        <div class="tab-pane fade show active" id="paneEmpresa" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Dados do Emitente</h5>
                    <small class="text-muted">Estes dados aparecem no cabeçalho do cupom fiscal NFC-e.</small>
                </div>
                <div class="card-body">
                    <form id="formEmpresa">
                        <input type="hidden" id="empresaId" value="<?= (int) $empresa['Id'] ?>">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Razão Social *</label>
                                <input type="text" class="form-control" id="razaoSocial" maxlength="200"
                                    value="<?= htmlspecialchars($empresa['RazaoSocial']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nome Fantasia</label>
                                <input type="text" class="form-control" id="nomeFantasia" maxlength="200"
                                    value="<?= htmlspecialchars($empresa['NomeFantasia']) ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">CNPJ *</label>
                                <input type="text" class="form-control cnpj-mask" id="cnpj" maxlength="18"
                                    value="<?= htmlspecialchars(mascaraCnpj($empresa['Cnpj'])) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inscrição Estadual</label>
                                <input type="text" class="form-control" id="inscricaoEstadual" maxlength="20"
                                    value="<?= htmlspecialchars($empresa['InscricaoEstadual']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control telefone-mask" id="telefone" maxlength="15"
                                    value="<?= htmlspecialchars($empresa['Telefone']) ?>">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="logradouro" maxlength="200"
                                    value="<?= htmlspecialchars($empresa['Logradouro']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" maxlength="20"
                                    value="<?= htmlspecialchars($empresa['Numero']) ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" maxlength="100"
                                    value="<?= htmlspecialchars($empresa['Bairro']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" maxlength="100"
                                    value="<?= htmlspecialchars($empresa['Cidade']) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">UF</label>
                                <input type="text" class="form-control" id="uf" maxlength="2"
                                    style="text-transform:uppercase;" value="<?= htmlspecialchars($empresa['Uf']) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">CEP</label>
                                <input type="text" class="form-control cep-mask" id="cep" maxlength="9"
                                    value="<?= htmlspecialchars(mascaraCep($empresa['Cep'])) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" maxlength="150"
                                    value="<?= htmlspecialchars($empresa['Email']) ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" onclick="Empresas.carregar()">Descartar</button>
                            <button type="submit" class="btn btn-primary">💾 Salvar Empresa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============ ABA ALENFE ============ -->
        <div class="tab-pane fade" id="paneAlenfe" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Integração ALENFE</h5>
                    <small class="text-muted">Parâmetros de conexão com o emissor de notas fiscais.</small>
                </div>
                <div class="card-body">
                    <form id="formAlenfe">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">URL Base do ALENFE</label>
                                <input type="text" class="form-control" id="alenfeUrl"
                                    placeholder="http://localhost:3001"
                                    value="<?= htmlspecialchars($cfg('ALENFE_URL', 'http://localhost:3001')) ?>">
                                <small class="text-muted">Endereço do serviço ALENFE (NodeJS).</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Chave de API (x-api-key)</label>
                                <input type="password" class="form-control" id="alenfeApiKey"
                                    value="<?= htmlspecialchars($cfg('ALENFE_API_KEY', '')) ?>">
                                <small class="text-muted">Enviada no header <code>x-api-key</code>.</small>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Token do Webhook (x-webhook-token)</label>
                                <input type="password" class="form-control" id="alenfeWebhookToken"
                                    value="<?= htmlspecialchars($cfg('ALENFE_WEBHOOK_TOKEN', '')) ?>">
                                <small class="text-muted">
                                    Mesmo valor configurado no ALENFE. O webhook recebe em
                                    <code>/api/webhook_nfce.php</code> e valida este token.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">CPF/CNPJ padrão do consumidor</label>
                                <input type="text" class="form-control" id="alenfeCpfPadrao"
                                    value="<?= htmlspecialchars($cfg('ALENFE_CPF_PADRAO', '')) ?>">
                                <small class="text-muted">Deixe vazio para NFC-e sem documento (consumidor
                                    final).</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nome padrão do consumidor</label>
                                <input type="text" class="form-control" id="alenfeConsumidorPadrao"
                                    value="<?= htmlspecialchars($cfg('ALENFE_CONSUMIDOR_PADRAO', 'CONSUMIDOR FINAL')) ?>">
                            </div>

                            <div class="col-12">
                                <hr class="my-1"><small class="text-muted">Endereço padrão do consumidor (usado quando
                                    não há cadastro)</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="alenfeEndLogradouro"
                                    value="<?= htmlspecialchars($cfg('ALENFE_ENDERECO_LOGRADOURO', 'NAO INFORMADO')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" id="alenfeEndNumero"
                                    value="<?= htmlspecialchars($cfg('ALENFE_ENDERECO_NUMERO', '0')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="alenfeEndBairro"
                                    value="<?= htmlspecialchars($cfg('ALENFE_ENDERECO_BAIRRO', 'NAO INFORMADO')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="alenfeEndCidade"
                                    value="<?= htmlspecialchars($cfg('ALENFE_ENDERECO_CIDADE', 'NAO INFORMADO')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">UF</label>
                                <input type="text" class="form-control" id="alenfeEndUf" maxlength="2"
                                    style="text-transform:uppercase;"
                                    value="<?= htmlspecialchars($cfg('ALENFE_ENDERECO_UF', 'DF')) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">CEP</label>
                                <input type="text" class="form-control cep-mask" id="alenfeEndCep"
                                    value="<?= htmlspecialchars(mascaraCep($cfg('ALENFE_ENDERECO_CEP', '00000000'))) ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" onclick="Empresas.carregar()">Descartar</button>
                            <button type="submit" class="btn btn-success">Salvar Configurações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Toast de feedback -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<?php
$content = ob_get_clean();

// Renderiza layout
include __DIR__ . '/../../templates/base_protected_v2.php';

// Footer (fecha HTML)
require __DIR__ . '/../../templates/footer_protected_v2.php';

?>
<script src="/js/empresas.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/empresas.js') ?>"></script>
