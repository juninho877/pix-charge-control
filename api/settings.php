
<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../config/database.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'save_settings':
            // Criar tabela se não existir
            $conn->exec("CREATE TABLE IF NOT EXISTS user_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                mp_access_token VARCHAR(255),
                mp_public_key VARCHAR(255),
                mp_sandbox BOOLEAN DEFAULT 1,
                evolution_url VARCHAR(255),
                evolution_token VARCHAR(255),
                evolution_instance VARCHAR(255),
                dark_mode BOOLEAN DEFAULT 0,
                notifications BOOLEAN DEFAULT 1,
                auto_backup BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_id (user_id)
            )");
            
            // Verificar se já existe configuração para o usuário
            $stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Atualizar configurações existentes
                $query = "UPDATE user_settings SET 
                    mp_access_token = ?, mp_public_key = ?, mp_sandbox = ?,
                    evolution_url = ?, evolution_token = ?, evolution_instance = ?,
                    dark_mode = ?, notifications = ?, auto_backup = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $input['mp_access_token'] ?? '',
                    $input['mp_public_key'] ?? '',
                    isset($input['mp_sandbox']) ? 1 : 0,
                    $input['evolution_url'] ?? '',
                    $input['evolution_token'] ?? '',
                    $input['evolution_instance'] ?? '',
                    isset($input['dark_mode']) ? 1 : 0,
                    isset($input['notifications']) ? 1 : 0,
                    isset($input['auto_backup']) ? 1 : 0,
                    $user_id
                ]);
            } else {
                // Inserir nova configuração
                $query = "INSERT INTO user_settings (
                    user_id, mp_access_token, mp_public_key, mp_sandbox,
                    evolution_url, evolution_token, evolution_instance,
                    dark_mode, notifications, auto_backup
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $user_id,
                    $input['mp_access_token'] ?? '',
                    $input['mp_public_key'] ?? '',
                    isset($input['mp_sandbox']) ? 1 : 0,
                    $input['evolution_url'] ?? '',
                    $input['evolution_token'] ?? '',
                    $input['evolution_instance'] ?? '',
                    isset($input['dark_mode']) ? 1 : 0,
                    isset($input['notifications']) ? 1 : 0,
                    isset($input['auto_backup']) ? 1 : 0
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);
            break;
            
        case 'save_preference':
            // Salvar preferência específica (ex: dark mode)
            $stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                $stmt = $conn->prepare("UPDATE user_settings SET dark_mode = ? WHERE user_id = ?");
                $stmt->execute([isset($input['dark_mode']) && $input['dark_mode'] ? 1 : 0, $user_id]);
            } else {
                $stmt = $conn->prepare("INSERT INTO user_settings (user_id, dark_mode) VALUES (?, ?)");
                $stmt->execute([$user_id, isset($input['dark_mode']) && $input['dark_mode'] ? 1 : 0]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'test_connections':
            $results = [];
            
            // Testar Mercado Pago
            $mp_token = $input['mp_access_token'] ?? '';
            if ($mp_token) {
                $results['mercadopago'] = 'Configurado';
            } else {
                $results['mercadopago'] = 'Não configurado';
            }
            
            // Testar Evolution API
            $evolution_url = $input['evolution_url'] ?? '';
            $evolution_token = $input['evolution_token'] ?? '';
            if ($evolution_url && $evolution_token) {
                $results['evolution'] = 'Configurado';
            } else {
                $results['evolution'] = 'Não configurado';
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Testes realizados',
                'results' => $results
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro na API de configurações: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
?>
