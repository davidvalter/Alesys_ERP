<?php
/* /public/components/topbar_v2.php */
function renderTopbar()
{
    $nomeUsuario = $_SESSION['Usuario']['nome'] ?? 'Usuário';
    $perfilUsuario = $_SESSION['Usuario']['perfil_nome'] ?? '';
    ?>
    <header class="topbar-saas">

        <div class="d-flex align-items-center gap-3">

            <!-- Toggle Sidebar -->
            <button class="btn btn-light btn-sm shadow-sm toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Breadcrumb / Título -->
            <div class="d-none d-md-block">
                <span class="topbar-title">Alesys ERP</span>
            </div>

        </div>

        <div class="d-flex align-items-center gap-3">

            <!-- Notificações (futuro) -->
            <button class="btn btn-light btn-sm shadow-sm position-relative">
                <i class="far fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    3
                </span>
            </button>

            <!-- Perfil -->
            <div class="dropdown position-relative">
                <button type="button" class="btn btn-light shadow-sm d-flex align-items-center gap-2 dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar">
                        <?= strtoupper(substr($nomeUsuario, 0, 1)) ?>
                    </span>

                    <span class="d-none d-md-block text-start">
                        <span class="user-name d-block">
                            <?= htmlspecialchars($nomeUsuario) ?>
                        </span>
                        <small class="text-muted d-block">
                            <?= htmlspecialchars($perfilUsuario) ?>
                        </small>
                    </span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li>
                        <a class="dropdown-item" href="/perfil.php">
                            <i class="fas fa-user me-2"></i> Meu Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/configuracoes.php">
                            <i class="fas fa-gear me-2"></i> Configurações
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>

        </div>

    </header>
<?php } ?>