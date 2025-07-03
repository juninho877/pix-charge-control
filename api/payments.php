
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
    case 'add':
        try {
            $query = "UPDATE clients SET valor_cobranca = ?, data_vencimento = ?, status = 'ativo' WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                floatval($input['valor']),
                $input['data_vencimento'],
                intval($input['client_id']),
                $user_id
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Pagamento adicionado com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao adicionar pagamento']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'confirm':
        try {
            $query = "UPDATE clients SET status = 'pago', updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([intval($input['id']), $user_id]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Pagamento confirmado']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao confirmar pagamento']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'generate_pix':
        try {
            // Aqui você integraria com a API do Mercado Pago
            // Por enquanto, vamos simular
            jsonResponse(['success' => true, 'message' => 'PIX gerado com sucesso', 'pix_code' => 'PIX_CODE_EXAMPLE']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao gerar PIX: ' . $e->getMessage()]);
        }
        break;
        
    case 'send_reminder':
        try {
            // Aqui você integraria com a API do WhatsApp
            // Por enquanto, vamos simular
            jsonResponse(['success' => true, 'message' => 'Lembrete enviado via WhatsApp']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao enviar lembrete: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
