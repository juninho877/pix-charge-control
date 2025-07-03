
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

if (!$conn) {
    jsonResponse(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'save_config':
        try {
            // Verificar se já existe configuração
            $query = "SELECT id FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Atualizar
                $query = "UPDATE whatsapp_settings SET 
                         instance_name = ?, api_key = ?, base_url = ?, 
                         phone_number = ?, updated_at = NOW()
                         WHERE user_id = ?";
                
                $params = [
                    sanitize($input['instance_name']),
                    sanitize($input['api_key']),
                    sanitize($input['base_url']),
                    sanitize($input['phone_number'] ?? ''),
                    $user_id
                ];
            } else {
                // Inserir
                $query = "INSERT INTO whatsapp_settings 
                         (user_id, instance_name, api_key, base_url, phone_number, created_at) 
                         VALUES (?, ?, ?, ?, ?, NOW())";
                
                $params = [
                    $user_id,
                    sanitize($input['instance_name']),
                    sanitize($input['api_key']),
                    sanitize($input['base_url']),
                    sanitize($input['phone_number'] ?? '')
                ];
            }
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Configurações salvas com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao salvar configurações']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'create_instance':
        try {
            // Buscar configurações
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                jsonResponse(['success' => false, 'message' => 'Configure as credenciais primeiro']);
            }
            
            $url = rtrim($settings['base_url'], '/') . '/instance/create';
            
            $data = [
                'instanceName' => $settings['instance_name'],
                'token' => $settings['api_key'],
                'qrcode' => true,
                'webhookUrl' => BASE_URL . 'webhook/whatsapp.php'
            ];
            
            $response = makeEvolutionRequest($url, 'POST', $data, $settings['api_key']);
            
            if ($response['success']) {
                jsonResponse(['success' => true, 'message' => 'Instância criada com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao criar instância: ' . $response['message']]);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'generate_qr':
        try {
            // Buscar configurações
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                jsonResponse(['success' => false, 'message' => 'Configure as credenciais primeiro']);
            }
            
            $url = rtrim($settings['base_url'], '/') . '/instance/connect/' . $settings['instance_name'];
            
            $response = makeEvolutionRequest($url, 'GET', null, $settings['api_key']);
            
            if ($response['success'] && isset($response['data']['base64'])) {
                // Salvar QR Code no banco
                $query = "UPDATE whatsapp_settings SET qr_code = ?, status = 'connecting', updated_at = NOW() WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$response['data']['base64'], $user_id]);
                
                jsonResponse([
                    'success' => true, 
                    'qr_code' => $response['data']['base64'],
                    'message' => 'QR Code gerado com sucesso'
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao gerar QR Code']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'check_status':
        try {
            // Buscar configurações
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                jsonResponse(['success' => false, 'message' => 'Configure as credenciais primeiro']);
            }
            
            $url = rtrim($settings['base_url'], '/') . '/instance/connectionState/' . $settings['instance_name'];
            
            $response = makeEvolutionRequest($url, 'GET', null, $settings['api_key']);
            
            if ($response['success']) {
                $status = 'disconnected';
                
                if (isset($response['data']['state'])) {
                    switch ($response['data']['state']) {
                        case 'open':
                            $status = 'connected';
                            break;
                        case 'connecting':
                            $status = 'connecting';
                            break;
                        default:
                            $status = 'disconnected';
                            break;
                    }
                }
                
                // Atualizar status no banco
                $query = "UPDATE whatsapp_settings SET status = ?, updated_at = NOW() WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$status, $user_id]);
                
                jsonResponse(['success' => true, 'status' => $status]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao verificar status']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'send_test':
        try {
            // Buscar configurações
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $settings = $stmt->fetch();
            
            if (!$settings) {
                jsonResponse(['success' => false, 'message' => 'Configure as credenciais primeiro']);
            }
            
            $phone = preg_replace('/[^0-9]/', '', $input['test_phone']);
            if (!str_starts_with($phone, '55')) {
                $phone = '55' . $phone;
            }
            
            $url = rtrim($settings['base_url'], '/') . '/message/sendText/' . $settings['instance_name'];
            
            $data = [
                'number' => $phone,
                'textMessage' => [
                    'text' => $input['test_message']
                ]
            ];
            
            $response = makeEvolutionRequest($url, 'POST', $data, $settings['api_key']);
            
            if ($response['success']) {
                jsonResponse(['success' => true, 'message' => 'Mensagem enviada com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao enviar mensagem']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}

function makeEvolutionRequest($url, $method = 'GET', $data = null, $apiKey = null) {
    $headers = ['Content-Type: application/json'];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($error)) {
        return ['success' => false, 'message' => 'Erro cURL: ' . $error];
    }
    
    $decoded_response = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        return ['success' => true, 'data' => $decoded_response];
    }
    
    return [
        'success' => false, 
        'message' => 'Erro HTTP ' . $http_code,
        'data' => $decoded_response
    ];
}
?>
