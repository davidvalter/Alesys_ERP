<?php
/* /public/admin/permissoes/gerenciar.php */

require_once __DIR__ . '/../../../configs/database.php';

$moduloAtual = 'admin.permissoes';
$tituloPagina = 'Gerenciar Permissões';

require_once __DIR__ . '/../../conf/autenticacao.php';

$perfilId = $_GET['perfil_id'] ?? null;

if (!$perfilId || !is_numeric($perfilId)) {
    $_SESSION['erro'] = 'ID de perfil inválido';
    header('Location: index.php');
    exit();
}

$db = getDB();

/* PERFIL */

$stmt = $db->prepare("SELECT * FROM Perfis WHERE id = ?");
$stmt->execute([$perfilId]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    $_SESSION['erro'] = 'Perfil não encontrado';
    header('Location: index.php');
    exit();
}

$tituloConteudo = 'Permissões para: ' . htmlspecialchars($perfil['nome']);

require_once __DIR__ . '/../../templates/base_protected_v2.php';

/* MÓDULOS */

$modulos = $db->query("
SELECT *
FROM Modulos
ORDER BY ordem, nome
")->fetchAll(PDO::FETCH_ASSOC);

/* PERMISSÕES ATUAIS */

$stmt = $db->prepare("
SELECT modulo_id
FROM Permissoes
WHERE perfil_id = ?
");

$stmt->execute([$perfilId]);

$permissoesAtuais = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* ORGANIZA ESTRUTURA */

$estrutura = [];

foreach ($modulos as $m) {
    if ($m['tipo'] == 'modulo') {
        $estrutura[$m['identificador']] = [
            'info' => $m,
            'submodulos' => []
        ];
    }
}

foreach ($modulos as $m) {
    if ($m['tipo'] == 'submodulo') {
        $partes = explode('.', $m['identificador']);
        $pai = $partes[0];
        if (isset($estrutura[$pai])) {
            $estrutura[$pai]['submodulos'][] = $m;
        }
    }
}

?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-user-shield me-2"></i>
            Gerenciar Permissões
        </h5>
        <span class="badge bg-primary-subtle text-primary">
            Perfil: <?= htmlspecialchars($perfil['nome']) ?>
        </span>

    </div>

    <?php if (!empty($_SESSION['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_SESSION['sucesso'];
            unset($_SESSION['sucesso']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= $_SESSION['erro'];
            unset($_SESSION['erro']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" action="salvar_permissoes.php">
        <input type="hidden" name="perfil_id" value="<?= $perfilId ?>">
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($estrutura as $modulo): ?>
                    <div class="col-lg-4">
                        <div class="card permissao-card h-100">
                            <div class="card-header">
                                <i class="fas <?= $modulo['info']['icone'] ?? 'fa-folder' ?> me-2"></i>
                                <?= htmlspecialchars($modulo['info']['nome']) ?>
                            </div>
                            <div class="card-body">
                                <?php foreach ($modulo['submodulos'] as $sub): ?>
                                    <?php $checked = in_array($sub['id'], $permissoesAtuais) ? 'checked' : ''; ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="modulos[]"
                                            value="<?= $sub['id'] ?>" id="mod_<?= $sub['id'] ?>" <?= $checked ?>>
                                        <label class="form-check-label" for="mod_<?= $sub['id'] ?>">
                                            <?= htmlspecialchars($sub['nome']) ?>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($sub['identificador']) ?>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>
                Salvar Permissões
            </button>
        </div>
    </form>
</div>

<script src="/js/admin.js"></script>
<?php require_once __DIR__ . '/../../templates/footer_protected_v2.php'; ?>