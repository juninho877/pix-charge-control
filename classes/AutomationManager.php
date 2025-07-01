
<?php
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'classes/ClientManager.php';
require_once 'classes/MercadoPago.php';
require_once 'classes/WhatsAppManager.php';

class AutomationManager {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function runDailyAutomation() {
        try {
            $log = [];
            $log[] = "=== Iniciando automação diária - " . date('Y-m-d H:i:s') . " ===";

            // Buscar todos os usuários ativos
            $users = $this->getActiveUsers();
            $log[] = "Usuários ativos encontrados: " . count($users);

            foreach ($users as $user) {
                $log[] = "\n--- Processando usuário: {$user['name']} (ID: {$user['id']}) ---";
                
                $user_log = $this->processUserAutomation($user['id']);
                $log = array_merge($log, $user_log);
            }

            $log[] = "\n=== Automação finalizada - " . date('Y-m-d H:i:s') . " ===";
            
            // Salvar log
            $this->saveAutomationLog(implode("\n", $log));
            
            return ['success' => true, 'log' => $log];

        } catch (Exception $e) {
            $error_msg = "Erro na automação: " . $e->getMessage();
            $this->saveAutomationLog($error_msg);
            return ['success' => false, 'message' => $error_msg];
        }
    }

    private function processUserAutomation($user_id) {
        $log = [];
        
        try {
            // Verificar configurações do usuário
            $settings = $this->getUserSettings($user_id);
            if (!$settings['auto_cobranca']) {
                $log[] = "Auto cobrança desabilitada para este usuário";
                return $log;
            }

            $dias_antecedencia = $settings['dias_antecedencia_cobranca'];
            
            // Buscar clientes com vencimento próximo
            $clientManager = new ClientManager();
            $clients_result = $clientManager->getClientsByVencimento($user_id, $dias_antecedencia);
            
            if (!$clients_result['success']) {
                $log[] = "Erro ao buscar clientes: " . $clients_result['message'];
                return $log;
            }

            $clients = $clients_result['clients'];
            $log[] = "Clientes com vencimento em {$dias_antecedencia} dias: " . count($clients);

            if (empty($clients)) {
                $log[] = "Nenhum cliente encontrado para cobrança";
                return $log;
            }

            // Inicializar integrações
            $mercadoPago = new MercadoPago($user_id);
            $whatsApp = new WhatsAppManager($user_id);

            foreach ($clients as $client) {
                $log[] = "\nProcessando cliente: {$client['name']} (ID: {$client['id']})";
                
                $client_log = $this->processClientPayment($client, $mercadoPago, $whatsApp, $clientManager);
                $log = array_merge($log, $client_log);
            }

            // Verificar pagamentos pendentes
            $log[] = "\nVerificando pagamentos pendentes...";
            $pending_log = $this->checkPendingPayments($user_id, $mercadoPago, $clientManager);
            $log = array_merge($log, $pending_log);

        } catch (Exception $e) {
            $log[] = "Erro ao processar usuário {$user_id}: " . $e->getMessage();
        }

        return $log;
    }

    private function processClientPayment($client, $mercadoPago, $whatsApp, $clientManager) {
        $log = [];
        
        try {
            // Verificar se já tem pagamento gerado
            if (!empty($client['payment_id'])) {
                $log[] = "Cliente já possui pagamento gerado (ID: {$client['payment_id']})";
                
                // Verificar status do pagamento
                $payment_status = $mercadoPago->getPaymentStatus($client['payment_id']);
                if ($payment_status['success']) {
                    if ($payment_status['status'] === 'approved') {
                        $log[] = "Pagamento já aprovado, atualizando status do cliente";
                        $clientManager->updatePaymentStatus($client['user_id'], $client['id'], 'pago');
                        return $log;
                    }
                }
            } else {
                // Criar novo pagamento PIX
                $log[] = "Criando pagamento PIX...";
                $payment_result = $mercadoPago->createPixPayment($client, $client['valor_cobranca']);
                
                if ($payment_result['success']) {
                    $log[] = "Pagamento PIX criado com sucesso (ID: {$payment_result['payment_id']})";
                    
                    // Atualizar cliente com dados do pagamento
                    $payment_data = [
                        'payment_id' => $payment_result['payment_id'],
                        'payment_link' => $payment_result['payment_link'],
                        'qr_code' => $payment_result['qr_code_base64']
                    ];
                    
                    $clientManager->updatePaymentStatus($client['user_id'], $client['id'], 'pendente', $payment_data);
                    $client['payment_link'] = $payment_result['payment_link'];
                    
                } else {
                    $log[] = "Erro ao criar pagamento: " . $payment_result['message'];
                    return $log;
                }
            }

            // Enviar lembrete via WhatsApp
            $log[] = "Enviando lembrete via WhatsApp...";
            $whatsapp_result = $whatsApp->sendPaymentReminder($client, $client['payment_link']);
            
            if ($whatsapp_result['success']) {
                $log[] = "Lembrete enviado com sucesso via WhatsApp";
            } else {
                $log[] = "Erro ao enviar WhatsApp: " . $whatsapp_result['message'];
            }

        } catch (Exception $e) {
            $log[] = "Erro ao processar pagamento do cliente {$client['id']}: " . $e->getMessage();
        }

        return $log;
    }

    private function checkPendingPayments($user_id, $mercadoPago, $clientManager) {
        $log = [];
        
        try {
            // Buscar clientes com pagamentos pendentes
            $query = "SELECT * FROM clients 
                     WHERE user_id = ? AND status = 'pendente' AND payment_id IS NOT NULL
                     ORDER BY data_vencimento ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $pending_clients = $stmt->fetchAll();

            $log[] = "Pagamentos pendentes para verificar: " . count($pending_clients);

            foreach ($pending_clients as $client) {
                $payment_status = $mercadoPago->getPaymentStatus($client['payment_id']);
                
                if ($payment_status['success']) {
                    $status = $payment_status['status'];
                    
                    if ($status === 'approved') {
                        $log[] = "Pagamento aprovado para cliente {$client['name']} - atualizando status";
                        $clientManager->updatePaymentStatus($user_id, $client['id'], 'pago');
                        
                    } elseif ($status === 'cancelled' || $status === 'rejected') {
                        $log[] = "Pagamento cancelado/rejeitado para cliente {$client['name']}";
                        
                        // Verificar se está vencido
                        if (strtotime($client['data_vencimento']) < time()) {
                            $clientManager->updatePaymentStatus($user_id, $client['id'], 'vencido');
                        }
                    }
                }
            }

        } catch (Exception $e) {
            $log[] = "Erro ao verificar pagamentos pendentes: " . $e->getMessage();
        }

        return $log;
    }

    private function getActiveUsers() {
        $query = "SELECT id, name, email FROM users WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getUserSettings($user_id) {
        $query = "SELECT * FROM user_settings WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        
        $settings = $stmt->fetch();
        if (!$settings) {
            // Criar configurações padrão se não existir
            $query = "INSERT INTO user_settings (user_id) VALUES (?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            return [
                'auto_cobranca' => true,
                'dias_antecedencia_cobranca' => 3
            ];
        }
        
        return $settings;
    }

    private function saveAutomationLog($log_content) {
        $log_file = 'logs/automation_' . date('Y-m-d') . '.log';
        
        // Criar diretório se não existir
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, $log_content . "\n", FILE_APPEND | LOCK_EX);
    }

    public function sendTestMessage($user_id, $client_id) {
        try {
            $clientManager = new ClientManager();
            $client_result = $clientManager->getClient($user_id, $client_id);
            
            if (!$client_result['success']) {
                return ['success' => false, 'message' => 'Cliente não encontrado'];
            }

            $client = $client_result['client'];
            
            // Gerar pagamento se não existir
            if (empty($client['payment_link'])) {
                $mercadoPago = new MercadoPago($user_id);
                $payment_result = $mercadoPago->createPixPayment($client, $client['valor_cobranca']);
                
                if ($payment_result['success']) {
                    $payment_data = [
                        'payment_id' => $payment_result['payment_id'],
                        'payment_link' => $payment_result['payment_link'],
                        'qr_code' => $payment_result['qr_code_base64']
                    ];
                    
                    $clientManager->updatePaymentStatus($user_id, $client_id, 'pendente', $payment_data);
                    $client['payment_link'] = $payment_result['payment_link'];
                }
            }

            // Enviar mensagem de teste
            $whatsApp = new WhatsAppManager($user_id);
            return $whatsApp->sendPaymentReminder($client, $client['payment_link']);

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }
}
?>
