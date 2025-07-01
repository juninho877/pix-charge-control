
<?php
$current_user = $auth->getCurrentUser();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-building"></i> <?php echo APP_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="javascript:void(0)" onclick="loadPage('clients')">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="javascript:void(0)" onclick="loadPage('settings')">
                        <i class="bi bi-gear"></i> Configurações
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $current_user['name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadPage('profile')">
                            <i class="bi bi-person"></i> Perfil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm ms-2" onclick="toggleDarkMode()" id="darkModeToggle">
                        <i class="bi bi-moon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>
