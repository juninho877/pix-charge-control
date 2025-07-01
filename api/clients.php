
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

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        try {
            $query = "INSERT INTO clients (user_id, name, email, phone, valor_cobranca, data_vencimento, status) VALUES (?, ?, ?, ?, ?, ?, 'ativo')";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $user_id,
                sanitize($input['name']),
                sanitize($input['email']),
                sanitize($input['phone']),
                floatval($input['valor_cobranca']),
                $input['data_vencimento']
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Cliente criado com sucesso', 'client_id' => $conn->lastInsertId()]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao criar cliente']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'update':
        try {
            $client_id = intval($input['client_id']);
            $query = "UPDATE clients SET name = ?, email = ?, phone = ?, valor_cobranca = ?, data_vencimento = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                sanitize($input['name']),
                sanitize($input['email']),
                sanitize($input['phone']),
                floatval($input['valor_cobranca']),
                $input['data_vencimento'],
                $client_id,
                $user_id
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Cliente atualizado com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao atualizar cliente']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete':
        try {
            $client_id = intval($input['client_id']);
            $query = "DELETE FROM clients WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$client_id, $user_id]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Cliente excluído com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao excluir cliente']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'get':
        try {
            $client_id = intval($_GET['id']);
            $query = "SELECT * FROM clients WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$client_id, $user_id]);
            $client = $stmt->fetch();
            
            if ($client) {
                jsonResponse(['success' => true, 'client' => $client]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Cliente não encontrado']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'update_status':
        try {
            $client_id = intval($input['client_id']);
            $status = sanitize($input['status']);
            $query = "UPDATE clients SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$status, $client_id, $user_id]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Status atualizado com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao atualizar status']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
