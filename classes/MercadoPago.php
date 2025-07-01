
<?php
require_once 'config/database.php';
require_once 'config/config.php';

class MercadoPago {
    private $conn;
    private $access_token;
    private $user_id;

    public function __construct($user_id = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        if ($user_id) {
            $this->user_id = $user_id;
            $this->loadAccessToken();
        }
    }

    private function loadAccessToken() {
        $query = "SELECT access_token FROM mercadopago_settings WHERE user_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->user_id]);
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch();
            $this->access_token = $result['access_token'];
        }
    }

    public function saveSettings($user_id, $data) {
        try {
            // Verificar se já existe configuração
            $query = "SELECT id FROM mercadopago_settings WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                // Atualizar
                $query = "UPDATE mercadopago_settings SET 
                         access_token = ?, valor_base = ?, desconto_3_meses = ?, 
                         desconto_6_meses = ?, webhook_url = ?, is_active = 1,
                         updated_at = NOW()
                         WHERE user_id = ?";
                
                $params = [
                    $data['access_token'],
                    $data['valor_base'] ?? 0,
                    $data['desconto_3_meses'] ?? 0,
                    $data['desconto_6_meses'] ?? 0,
                    MP_WEBHOOK_URL,
                    $user_id
                ];
            } else {
                // Inserir
                $query = "INSERT INTO mercadopago_settings 
                         (user_id, access_token, valor_base, desconto_3_meses, 
                          desconto_6_meses, webhook_url) 
                         VALUES (?, ?, ?, ?, ?, ?)";
                
                $params = [
                    $user_id,
                    $data['access_token'],
                    $data['valor_base'] ?? 0,
                    $data['desconto_3_meses'] ?? 0,
                    $data['desconto_6_meses'] ?? 0,
                    MP_WEBHOOK_URL
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
            $query = "SELECT * FROM mercadopago_settings WHERE user_id = ?";
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

    public function createPixPayment($client_data, $amount) {
        if (!$this->access_token) {
            return ['success' => false, 'message' => 'Token de acesso não configurado'];
        }

        try {
            $payment_data = [
                'transaction_amount' => floatval($amount),
                'description' => 'Pagamento - ' . $client_data['name'],
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $client_data['email'] ?? 'cliente@email.com',
                    'first_name' => $client_data['name'],
                    'identification' => [
                        'type' => 'CPF',
                        'number' => '11111111111' // Implementar validação de CPF se necessário
                    ]
                ],
                'notification_url' => MP_WEBHOOK_URL,
                'external_reference' => 'client_' . $client_data['id'],
                'date_of_expiration' => date('c', strtotime($client_data['data_vencimento'] . ' +1 day'))
            ];

            $response = $this->makeRequest('POST', '/v1/payments', $payment_data);

            if ($response['success'] && isset($response['data']['id'])) {
                $payment_info = $response['data'];
                
                return [
                    'success' => true,
                    'payment_id' => $payment_info['id'],
                    'qr_code' => $payment_info['point_of_interaction']['transaction_data']['qr_code'] ?? '',
                    'qr_code_base64' => $payment_info['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '',
                    'payment_link' => BASE_URL . 'payment.php?id=' . $payment_info['id'],
                    'ticket_url' => $payment_info['point_of_interaction']['transaction_data']['ticket_url'] ?? ''
                ];
            }

            return ['success' => false, 'message' => 'Erro ao criar pagamento PIX'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getPaymentStatus($payment_id) {
        if (!$this->access_token) {
            return ['success' => false, 'message' => 'Token de acesso não configurado'];
        }

        try {
            $response = $this->makeRequest('GET', '/v1/payments/' . $payment_id);

            if ($response['success']) {
                return [
                    'success' => true,
                    'status' => $response['data']['status'],
                    'status_detail' => $response['data']['status_detail'],
                    'payment_data' => $response['data']
                ];
            }

            return ['success' => false, 'message' => 'Erro ao consultar pagamento'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    private function makeRequest($method, $endpoint, $data = null) {
        $url = MP_BASE_URL . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ];

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

    public function processWebhook($webhook_data) {
        try {
            if (isset($webhook_data['type']) && $webhook_data['type'] === 'payment') {
                $payment_id = $webhook_data['data']['id'];
                
                // Consultar status do pagamento
                $payment_status = $this->getPaymentStatus($payment_id);
                
                if ($payment_status['success']) {
                    $status = $payment_status['status'];
                    $external_reference = $payment_status['payment_data']['external_reference'] ?? '';
                    
                    if (strpos($external_reference, 'client_') === 0) {
                        $client_id = str_replace('client_', '', $external_reference);
                        
                        // Atualizar status do cliente
                        if ($status === 'approved') {
                            $this->updateClientPaymentStatus($client_id, 'pago', $payment_status['payment_data']);
                        } elseif ($status === 'cancelled' || $status === 'rejected') {
                            $this->updateClientPaymentStatus($client_id, 'vencido', $payment_status['payment_data']);
                        }
                    }
                }
            }

            return ['success' => true, 'message' => 'Webhook processado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao processar webhook: ' . $e->getMessage()];
        }
    }

    private function updateClientPaymentStatus($client_id, $status, $payment_data) {
        // Buscar cliente e atualizar status
        $query = "UPDATE clients SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status, $client_id]);

        // Registrar no histórico
        if ($status === 'pago') {
            $query = "INSERT INTO payment_history 
                     (user_id, client_id, payment_id, amount, status, payment_method, transaction_data, paid_at)
                     SELECT user_id, id, ?, valor_cobranca, ?, 'pix', ?, NOW()
                     FROM clients WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $payment_data['id'],
                $payment_data['status'],
                json_encode($payment_data),
                $client_id
            ]);
        }
    }
}
?>
