
<?php
// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'config/database.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Buscar dados do usuário
$database = new Database();
$conn = $database->getConnection();
$user = ['name' => 'Usuário', 'dark_mode' => 0];

if ($conn) {
    try {
        $query = "SELECT u.name, COALESCE(us.dark_mode, 0) as dark_mode FROM users u LEFT JOIN user_settings us ON u.id = us.user_id WHERE u.id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            if ($result) {
                $user = $result;
            }
        }
    } catch (Exception $e) {
        // Manter valores padrão em caso de erro
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body <?php echo ($user['dark_mode'] ?? 0) ? 'data-bs-theme="dark"' : ''; ?>>
    
    <!-- Navbar -->
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
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['name'] ?? 'Usuário'); ?>
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

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="javascript:void(0)" onclick="loadPage('dashboard')">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('clients')">
                                <i class="bi bi-people"></i> Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('payments')">
                                <i class="bi bi-credit-card"></i> Pagamentos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('whatsapp')">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('automation')">
                                <i class="bi bi-robot"></i> Automação
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('reports')">
                                <i class="bi bi-graph-up"></i> Relatórios
                            </a>
                        </li>
                    </ul>
                    
                    <hr>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('settings')">
                                <i class="bi bi-gear"></i> Configurações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="javascript:void(0)" onclick="loadPage('profile')">
                                <i class="bi bi-person"></i> Perfil
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Atualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content -->
                <div id="dashboard-content">
                    <?php include 'pages/dashboard.php'; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
