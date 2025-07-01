
<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/MercadoPago.php';

// Verificar autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autenticado'], 401);
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'save_mercadopago':
        $mercadoPago = new MercadoPago();
        $data = [
            'access_token' => $input['access_token'],
            'valor_base' => floatval($input['valor_base'] ?? 0),
            'desconto_3_meses' => floatval($input['desconto_3_meses'] ?? 0),
            'desconto_6_meses' => floatval($input['desconto_6_meses'] ?? 0)
        ];
        
        $result = $mercadoPago->saveSettings($user_id, $data);
        jsonResponse($result);
        break;
        
    case 'save_whatsapp':
        // Implementar salvamento das configurações do WhatsApp
        $data = [
            'instance_name' => sanitize($input['instance_name']),
            'api_key' => $input['api_key'],
            'base_url' => $input['base_url'] ?? EVOLUTION_DEFAULT_URL
        ];
        
        // Salvar no banco de dados
        $database = new Database();
        $conn = $database->getConnection();
        
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
                $data['instance_name'],
                $data['api_key'],
                $data['base_url']
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
        
    case 'save_preference':
        $key = sanitize($input['key']);
        $value = $input['value'];
        
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            $query = "UPDATE user_settings SET {$key} = ?, updated_at = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$value, $user_id]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Preferência salva']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar preferência']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
