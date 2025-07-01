
<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Verificar autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autenticado'], 401);
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'generate_qr':
        try {
            // Buscar configurações do WhatsApp
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if (!$settings || empty($settings['api_key']) || empty($settings['instance_name'])) {
                jsonResponse(['success' => false, 'message' => 'Configure primeiro as credenciais do WhatsApp']);
                break;
            }
            
            $base_url = $settings['base_url'];
            $instance_name = $settings['instance_name'];
            $api_key = $settings['api_key'];
            
            // Fazer requisição para gerar QR Code
            $url = $base_url . "instance/connect/" . $instance_name;
            
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $api_key
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['webhook' => BASE_URL . 'webhook/whatsapp.php']));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                $data = json_decode($response, true);
                if (isset($data['qrcode'])) {
                    jsonResponse(['success' => true, 'qr_code' => $data['qrcode']]);
                } else {
                    jsonResponse(['success' => false, 'message' => 'QR Code não encontrado na resposta']);
                }
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao conectar com a API: HTTP ' . $http_code]);
            }
            
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'send_message':
        try {
            $phone = sanitize($_POST['phone']);
            $message = sanitize($_POST['message']);
            
            // Buscar configurações do WhatsApp
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if (!$settings || empty($settings['api_key']) || empty($settings['instance_name'])) {
                jsonResponse(['success' => false, 'message' => 'Configure primeiro as credenciais do WhatsApp']);
                break;
            }
            
            $base_url = $settings['base_url'];
            $instance_name = $settings['instance_name'];
            $api_key = $settings['api_key'];
            
            // Formatar número
            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
            if (!str_starts_with($clean_phone, '55')) {
                $clean_phone = '55' . $clean_phone;
            }
            
            // Fazer requisição para enviar mensagem
            $url = $base_url . "message/sendText/" . $instance_name;
            
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $api_key
            ];
            
            $data = [
                'number' => $clean_phone,
                'textMessage' => [
                    'text' => $message
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                jsonResponse(['success' => true, 'message' => 'Mensagem enviada com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao enviar mensagem: HTTP ' . $http_code]);
            }
            
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
