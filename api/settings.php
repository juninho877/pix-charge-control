
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

switch ($action) {
    case 'save_mercadopago':
        try {
            $query = "INSERT INTO mercadopago_settings (user_id, access_token, valor_base, desconto_3_meses, desconto_6_meses) 
                     VALUES (?, ?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     access_token = VALUES(access_token),
                     valor_base = VALUES(valor_base),
                     desconto_3_meses = VALUES(desconto_3_meses),
                     desconto_6_meses = VALUES(desconto_6_meses),
                     updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $user_id,
                $input['access_token'],
                floatval($input['valor_base'] ?? 0),
                floatval($input['desconto_3_meses'] ?? 0),
                floatval($input['desconto_6_meses'] ?? 0)
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Configurações do Mercado Pago salvas com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar configurações']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'save_whatsapp':
        try {
            $query = "INSERT INTO whatsapp_settings (user_id, instance_name, api_key, base_url) 
                     VALUES (?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     instance_name = VALUES(instance_name),
                     api_key = VALUES(api_key),
                     base_url = VALUES(base_url),
                     updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $user_id,
                sanitize($input['instance_name']),
                $input['api_key'],
                $input['base_url'] ?? EVOLUTION_DEFAULT_URL
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Configurações do WhatsApp salvas com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar configurações']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'save_automation':
        try {
            $query = "INSERT INTO user_settings (user_id, auto_cobranca, dias_antecedencia, notification_email, notification_whatsapp, message_template) 
                     VALUES (?, ?, ?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     auto_cobranca = VALUES(auto_cobranca),
                     dias_antecedencia = VALUES(dias_antecedencia),
                     notification_email = VALUES(notification_email),
                     notification_whatsapp = VALUES(notification_whatsapp),
                     message_template = VALUES(message_template),
                     updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $user_id,
                isset($input['auto_cobranca']) ? 1 : 0,
                intval($input['dias_antecedencia'] ?? 3),
                isset($input['notification_email']) ? 1 : 0,
                isset($input['notification_whatsapp']) ? 1 : 0,
                sanitize($input['message_template'] ?? '')
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Configurações de automação salvas com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar configurações']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'save_preferences':
        try {
            $dark_mode = isset($input['dark_mode']) ? 1 : 0;
            $timezone = sanitize($input['timezone'] ?? 'America/Sao_Paulo');
            
            $query = "INSERT INTO user_settings (user_id, dark_mode, timezone) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE 
                     dark_mode = VALUES(dark_mode),
                     timezone = VALUES(timezone),
                     updated_at = NOW()";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$user_id, $dark_mode, $timezone]);
            
            if ($result) {
                jsonResponse([
                    'success' => true, 
                    'message' => 'Preferências salvas com sucesso',
                    'dark_mode' => $dark_mode
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar preferências']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
