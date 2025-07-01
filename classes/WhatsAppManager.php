
<?php
require_once 'config/database.php';
require_once 'config/config.php';

class WhatsAppManager {
    private $conn;
    private $user_id;
    private $settings;

    public function __construct($user_id = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        if ($user_id) {
            $this->user_id = $user_id;
            $this->loadSettings();
        }
    }

    private function loadSettings() {
        $query = "SELECT * FROM whatsapp_settings WHERE user_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->user_id]);
        
        if ($stmt->rowCount() > 0) {
            $this->settings = $stmt->fetch();
        }
    }

    public function saveSettings($user_id, $data) {
        try {
            // Verificar se já existe configuração
            $query = "SELECT id FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                // Atualizar
                $query = "UPDATE whatsapp_settings SET 
                         instance_name = ?, api_key = ?, base_url = ?, 
                         phone_number = ?, is_active = 1, updated_at = NOW()
                         WHERE user_id = ?";
                
                $params = [
                    $data['instance_name'],
                    $data['api_key'],
                    $data['base_url'] ?? EVOLUTION_DEFAULT_URL,
                    $data['phone_number'] ?? null,
                    $user_id
                ];
            } else {
                // Inserir
                $query = "INSERT INTO whatsapp_settings 
                         (user_id, instance_name, api_key, base_url, phone_number) 
                         VALUES (?, ?, ?, ?, ?)";
                
                $params = [
                    $user_id,
                    $data['instance_name'],
                    $data['api_key'],
                    $data['base_url'] ?? EVOLUTION_DEFAULT_URL,
                    $data['phone_number'] ?? null
                ];
            }

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($result) {
                return ['success' => true, 'message' => 'Configurações salvas com sucesso'];
            }

            return ['success' => false, 'message' => 'Erro ao salvar configurações'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getSettings($user_id) {
        try {
            $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'settings' => $stmt->fetch()];
            }

            return ['success' => false, 'message' => 'Configurações não encontradas'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function createInstance() {
        if (!$this->settings) {
            return ['success' => false, 'message' => 'Configurações não encontradas'];
        }

        try {
            $instance_data = [
                'instanceName' => $this->settings['instance_name'],
                'token' => $this->settings['api_key'],
                'qrcode' => true,
                'webhookUrl' => BASE_URL . 'webhook/whatsapp.php',
                'webhookByEvents' => false,
                'webhookBase64' => false,
                'chatwootAccountId' => null,
                'chatwootToken' => null,
                'chatwootUrl' => null,
                'chatwootSignMsg' => false,
                'chatwootReopenConversation' => false,
                'chatwootConversationPending' => false
            ];

            $response = $this->makeRequest('POST', '/instance/create', $instance_data, false);

            if ($response['success']) {
                return ['success' => true, 'message' => 'Instância criada com sucesso'];
            }

            return ['success' => false, 'message' => 'Erro ao criar instância'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getQRCode() {
        if (!$this->settings) {
            return ['success' => false, 'message' => 'Configurações não encontradas'];
        }

        try {
            $response = $this->makeRequest('GET', '/instance/connect/' . $this->settings['instance_name']);

            if ($response['success'] && isset($response['data']['base64'])) {
                // Salvar QR Code no banco
                $query = "UPDATE whatsapp_settings SET qr_code = ?, status = 'connecting' WHERE user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$response['data']['base64'], $this->user_id]);

                return [
                    'success' => true, 
                    'qr_code' => $response['data']['base64'],
                    'message' => 'QR Code gerado'
                ];
            }

            return ['success' => false, 'message' => 'Erro ao obter QR Code'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getConnectionStatus() {
        if (!$this->settings) {
            return ['success' => false, 'message' => 'Configurações não encontradas'];
        }

        try {
            $response = $this->makeRequest('GET', '/instance/connectionState/' . $this->settings['instance_name']);

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
                        case 'close':
                        default:
                            $status = 'disconnected';
                            break;
                    }
                }

                // Atualizar status no banco
                $query = "UPDATE whatsapp_settings SET status = ? WHERE user_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$status, $this->user_id]);

                return ['success' => true, 'status' => $status];
            }

            return ['success' => false, 'message' => 'Erro ao verificar status'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function sendMessage($phone, $message, $client_id = null, $message_type = 'cobranca') {
        if (!$this->settings || $this->settings['status'] !== 'connected') {
            return ['success' => false, 'message' => 'WhatsApp não conectado'];
        }

        try {
            // Limpar e formatar número
            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($clean_phone) == 11 && substr($clean_phone, 0, 1) != '55') {
                $clean_phone = '55' . $clean_phone;
            }

            $message_data = [
                'number' => $clean_phone,
                'textMessage' => [
                    'text' => $message
                ]
            ];

            $response = $this->makeRequest('POST', '/message/sendText/' . $this->settings['instance_name'], $message_data);

            if ($response['success']) {
                // Registrar mensagem no histórico
                if ($client_id) {
                    $this->logMessage($client_id, $message_type, $message, 'enviado');
                }

                return ['success' => true, 'message' => 'Mensagem enviada com sucesso'];
            }

            return ['success' => false, 'message' => 'Erro ao enviar mensagem'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function sendPaymentReminder($client_data, $payment_link) {
        $message = $this->generatePaymentMessage($client_data, $payment_link);
        
        return $this->sendMessage(
            $client_data['phone'], 
            $message, 
            $client_data['id'], 
            'cobranca'
        );
    }

    private function generatePaymentMessage($client_data, $payment_link) {
        $valor_formatado = 'R$ ' . number_format($client_data['valor_cobranca'], 2, ',', '.');
        $data_vencimento = date('d/m/Y', strtotime($client_data['data_vencimento']));
        
        $message = "Olá {$client_data['name']}! 👋\n\n";
        $message .= "Esperamos que esteja tudo bem com você!\n\n";
        $message .= "📅 Lembramos que seu pagamento de {$valor_formatado} vence em {$data_vencimento}.\n\n";
        $message .= "💳 Para facilitar, você pode pagar via PIX clicando no link abaixo:\n";
        $message .= "{$payment_link}\n\n";
        $message .= "📱 Ou se preferir, escaneie o QR Code que será exibido na página.\n\n";
        $message .= "Em caso de dúvidas, estamos à disposição!\n\n";
        $message .= "Obrigado! 🙏";

        return $message;
    }

    private function logMessage($client_id, $message_type, $content, $status) {
        try {
            $query = "INSERT INTO whatsapp_messages 
                     (user_id, client_id, message_type, message_content, status) 
                     VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$this->user_id, $client_id, $message_type, $content, $status]);

        } catch (Exception $e) {
            // Log error silently
            error_log("Erro ao registrar mensagem: " . $e->getMessage());
        }
    }

    private function makeRequest($method, $endpoint, $data = null, $use_auth = true) {
        $url = $this->settings['base_url'] . $endpoint;
        
        $headers = ['Content-Type: application/json'];
        
        if ($use_auth) {
            $headers[] = 'apikey: ' . $this->settings['api_key'];
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
}
?>
