<?php

$moduloAtual = 'admin.usuarios';

require_once __DIR__ . '/../../templates/base_protected_v2.php';

$db = getDB();

$id = $_GET['id'];

$stmt = $db->prepare("
SELECT u.*,p.nome perfil_nome
FROM Usuarios u
JOIN Perfis p ON p.id=u.perfil_id
WHERE u.Id=?
");

$stmt->execute([$id]);

$u = $stmt->fetch();

?>

<div class="row">

    <div class="col-lg-6">

        <div class="card shadow-sm">

            <div class="card-header">

                <h5 class="mb-0">

                    Informações do Usuário

                </h5>

            </div>

            <div class="card-body">

                <dl class="row mb-0">

                    <dt class="col-sm-4">ID</dt>
                    <dd class="col-sm-8">
                        <?= $u['Id'] ?>
                    </dd>

                    <dt class="col-sm-4">Usuário</dt>
                    <dd class="col-sm-8">
                        <?= $u['Username'] ?>
                    </dd>

                    <dt class="col-sm-4">Nome</dt>
                    <dd class="col-sm-8">
                        <?= $u['NomeCompleto'] ?>
                    </dd>

                    <dt class="col-sm-4">Perfil</dt>
                    <dd class="col-sm-8">
                        <?= $u['perfil_nome'] ?>
                    </dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">

                        <span class="badge <?= $u['Ativo'] ? 'bg-success' : 'bg-danger' ?>">

                            <?= $u['Ativo'] ? 'Ativo' : 'Inativo' ?>

                        </span>

                    </dd>

                </dl>

            </div>

        </div>

    </div>

</div>