
<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/ClientManager.php';

// Verificar autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autenticado'], 401);
}

$clientManager = new ClientManager();
$user_id = $_SESSION['user_id'];

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $data = [
            'name' => sanitize($input['name']),
            'email' => sanitize($input['email']),
            'phone' => sanitize($input['phone']),
            'valor_cobranca' => floatval($input['valor_cobranca']),
            'data_vencimento' => $input['data_vencimento']
        ];
        
        $result = $clientManager->createClient($user_id, $data);
        jsonResponse($result);
        break;
        
    case 'update':
        $client_id = intval($input['client_id']);
        $data = [
            'name' => sanitize($input['name']),
            'email' => sanitize($input['email']),
            'phone' => sanitize($input['phone']),
            'valor_cobranca' => floatval($input['valor_cobranca']),
            'data_vencimento' => $input['data_vencimento']
        ];
        
        $result = $clientManager->updateClient($user_id, $client_id, $data);
        jsonResponse($result);
        break;
        
    case 'delete':
        $client_id = intval($input['client_id']);
        $result = $clientManager->deleteClient($user_id, $client_id);
        jsonResponse($result);
        break;
        
    case 'get':
        $client_id = intval($_GET['id']);
        $result = $clientManager->getClient($user_id, $client_id);
        jsonResponse($result);
        break;
        
    case 'list':
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'page' => intval($_GET['page'] ?? 1),
            'per_page' => intval($_GET['per_page'] ?? 10)
        ];
        
        $result = $clientManager->getClients($user_id, $filters);
        jsonResponse($result);
        break;
        
    case 'update_status':
        $client_id = intval($input['client_id']);
        $status = sanitize($input['status']);
        $payment_data = $input['payment_data'] ?? null;
        
        $result = $clientManager->updatePaymentStatus($user_id, $client_id, $status, $payment_data);
        jsonResponse($result);
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
