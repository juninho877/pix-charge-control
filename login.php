
<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Se já está logado, redirecionar para dashboard
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_POST) {
    $auth = new Auth();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $email = sanitize($_POST['email']);
                $password = $_POST['password'];
                
                if (empty($email) || empty($password)) {
                    $error = 'Preencha todos os campos';
                } else {
                    $result = $auth->login($email, $password);
                    if ($result['success']) {
                        redirect('index.php');
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'register':
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $password = $_POST['password'];
                $password_confirm = $_POST['password_confirm'];
                $phone = sanitize($_POST['phone']);
                
                if (empty($name) || empty($email) || empty($password)) {
                    $error = 'Preencha todos os campos obrigatórios';
                } elseif ($password !== $password_confirm) {
                    $error = 'Senhas não conferem';
                } elseif (strlen($password) < 6) {
                    $error = 'Senha deve ter pelo menos 6 caracteres';
                } elseif (!isValidEmail($email)) {
                    $error = 'Email inválido';
                } else {
                    $result = $auth->register($name, $email, $password, $phone);
                    if ($result['success']) {
                        $success = 'Conta criada com sucesso! Faça login para continuar.';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'forgot_password':
                $email = sanitize($_POST['email']);
                
                if (empty($email)) {
                    $error = 'Digite seu email';
                } elseif (!isValidEmail($email)) {
                    $error = 'Email inválido';
                } else {
                    $result = $auth->forgotPassword($email);
                    if ($result['success']) {
                        $success = 'Se o email existir, você receberá as instruções de recuperação.';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold"><?php echo APP_NAME; ?></h2>
                            <p class="text-muted">Sistema de Gestão de Clientes</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs nav-justified mb-4" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">
                                    <i class="bi bi-person-plus"></i> Cadastrar
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="authTabsContent">
                            <!-- Login Tab -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="action" value="login">
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Senha</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" class="text-decoration-none">
                                            Esqueceu sua senha?
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- Register Tab -->
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="action" value="register">
                                    
                                    <div class="mb-3">
                                        <label for="reg_name" class="form-label">Nome Completo</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="reg_name" name="name" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_email" class="form-label">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="reg_email" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_phone" class="form-label">Telefone/WhatsApp</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                            <input type="text" class="form-control" id="reg_phone" name="phone" placeholder="(11) 99999-9999">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_password" class="form-label">Senha</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="reg_password" name="password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_password_confirm" class="form-label">Confirmar Senha</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="reg_password_confirm" name="password_confirm" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-person-plus"></i> Criar Conta
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Esqueci Senha -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Recuperar Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="forgot_password">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="forgot_email" class="form-label">Digite seu email</label>
                            <input type="email" class="form-control" id="forgot_email" name="email" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
