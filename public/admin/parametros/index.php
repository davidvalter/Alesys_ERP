<?php
/* public/admin/parametros/index.php */

session_start();

$moduloAtual = 'admin.parametros';
$tituloPagina = 'Parâmetros';
$tituloConteudo = 'Configurações do Sistema';

require_once __DIR__ . '/../../conf/autenticacao.php';
require_once __DIR__ . '/../../../configs/database.php';
require_once __DIR__ . '/../../templates/base_protected_v2.php';

$db = getDB();

/* =========================
   VERIFICA PERMISSÃO
========================= */
verificarPermissao($moduloAtual);

/* =========================
   VALIDA USUÁRIO
========================= */
$usuarioId = $_SESSION['Usuario']['id'] ?? null;

if (!$usuarioId) {
    header('Location: /login.php');
    exit;
}

/* =========================
   SALVAR PARÂMETROS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $db->beginTransaction();

        $stmt = $db->query("SELECT Chave FROM Parametros");
        $todas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($todas as $chave) {

            $valor = isset($_POST['parametros'][$chave]) ? '1' : '0';

            $stmtUpdate = $db->prepare("
                UPDATE Parametros
                SET Valor = ?
                WHERE Chave = ?
            ");

            $stmtUpdate->execute([$valor, $chave]);
        }

        $db->commit();

        $msgSucesso = "Configurações atualizadas com sucesso.";

    } catch (Exception $e) {

        $db->rollBack();

        $msgErro = "Erro ao salvar parâmetros: " . $e->getMessage();
    }
}

/* =========================
   BUSCAR PARÂMETROS
========================= */

$stmt = $db->query("
    SELECT *
    FROM Parametros
    ORDER BY Grupo, Descricao
");

$parametros = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   AGRUPAR
========================= */

$grupos = [];

foreach ($parametros as $p) {
    // Este parâmetro afeta a rotina de estoque e fica agrupado no card Estoque,
    // mesmo que uma instalação já tenha criado o registro em outro grupo.
    $grupoExibicao = $p['Chave'] === 'ALENFE_COMPRA_ATUALIZAR_ESTOQUE_AUTOMATICAMENTE'
        ? 'Estoque'
        : ($p['Grupo'] ?: 'Outros');
    $grupos[$grupoExibicao][] = $p;
}

?>

<div class="container-fluid parametros-page">

    <!-- HEADER -->
    <div class="page-header mb-4">

        <div>
            <i class="fas fa-sliders-h me-2"></i>
            <small class="text-muted">
                Configurações globais do sistema
            </small>
        </div>

    </div>

    <?php if (!empty($msgSucesso)): ?>
        <div class="alert alert-success alert-modern">
            <?= $msgSucesso ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($msgErro)): ?>
        <div class="alert alert-danger alert-modern">
            <?= $msgErro ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <div class="row">

            <?php foreach ($grupos as $grupo => $lista): ?>

                <div class="col-xl-4 col-lg-6">

                    <div class="card card-default mb-4">

                        <div class="card-header">
                            <?= htmlspecialchars($grupo) ?>
                        </div>

                        <div class="card-body">

                            <?php foreach ($lista as $p): ?>

                                <div class="form-check form-switch parametro-item">

                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="<?= $p['Chave'] ?>"
                                           name="parametros[<?= $p['Chave'] ?>]"
                                           value="1"
                                           <?= $p['Valor'] == '1' ? 'checked' : '' ?>>

                                    <label class="form-check-label"
                                           for="<?= $p['Chave'] ?>">

                                        <?= htmlspecialchars($p['Descricao']) ?>

                                    </label>
                                    <?php if ($p['Chave'] === 'ALENFE_COMPRA_ATUALIZAR_ESTOQUE_AUTOMATICAMENTE'): ?>
                                        <small class="text-muted d-block mt-1">O controle está preparado para uso futuro. No momento, a importação do AleNFE não movimenta o estoque, mesmo que esta opção esteja ativa.</small>
                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="mt-3">

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>
                Salvar Configurações
            </button>

        </div>

    </form>

</div>

<link rel="stylesheet" href="/styles/admin_parametros.css">

<?php require __DIR__ . '/../../templates/footer_protected_v2.php'; ?>