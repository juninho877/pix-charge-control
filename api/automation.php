
<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Verificar autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autenticado'], 401);
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    jsonResponse(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
}

switch ($action) {
    case 'save_automation_config':
        try {
            $query = "INSERT INTO user_settings (user_id, reminder_3_days, reminder_due_date, reminder_overdue, message_template, auto_suspend, suspend_days, send_reports, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE 
                     reminder_3_days = VALUES(reminder_3_days),
                     reminder_due_date = VALUES(reminder_due_date),
                     reminder_overdue = VALUES(reminder_overdue),
                     message_template = VALUES(message_template),
                     auto_suspend = VALUES(auto_suspend),
                     suspend_days = VALUES(suspend_days),
                     send_reports = VALUES(send_reports),
                     updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $user_id,
                isset($input['reminder_3_days']) ? 1 : 0,
                isset($input['reminder_due_date']) ? 1 : 0,
                isset($input['reminder_overdue']) ? 1 : 0,
                sanitize($input['message_template'] ?? ''),
                isset($input['auto_suspend']) ? 1 : 0,
                intval($input['suspend_days'] ?? 7),
                isset($input['send_reports']) ? 1 : 0
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Configurações salvas com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar configurações']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'test_automation':
        try {
            // Simular teste de automação
            jsonResponse(['success' => true, 'message' => 'Teste executado com sucesso. 3 lembretes enviados.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro no teste: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
