
<?php
// Configurações gerais do sistema
define('APP_NAME', 'SaaS Gestão de Clientes');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://apiteste.streamingplay.site/saas-clientes/');
define('UPLOAD_PATH', 'uploads/');

// Configurações de segurança
define('JWT_SECRET', 'sua_chave_secreta_jwt_aqui_123456789');
define('ENCRYPTION_KEY', 'sua_chave_de_criptografia_aqui_987654321');

// Configurações de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'seu_email@gmail.com');
define('SMTP_PASSWORD', 'sua_senha_app');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// Configurações do Mercado Pago
define('MP_BASE_URL', 'https://api.mercadopago.com');
define('MP_WEBHOOK_URL', BASE_URL . 'webhook/mercadopago.php');

// Configurações Evolution API
define('EVOLUTION_DEFAULT_URL', 'https://evov2.duckdns.org/');
define('EVOLUTION_DEFAULT_KEY', '79Bb4lpu2TzxrSMu3SDfSGvB3MIhkur7');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Iniciar sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função para sanitizar dados
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para gerar token aleatório
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Função para verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Função para redirecionar
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Função para retornar JSON
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>
