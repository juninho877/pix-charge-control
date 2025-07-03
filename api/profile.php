
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
    case 'update_profile':
        try {
            $query = "UPDATE users SET name = ?, email = ?, phone = ?, company = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                sanitize($input['name']),
                sanitize($input['email']),
                sanitize($input['phone'] ?? ''),
                sanitize($input['company'] ?? ''),
                $user_id
            ]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Perfil atualizado com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao atualizar perfil']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    case 'change_password':
        try {
            // Verificar senha atual
            $query = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($input['current_password'], $user['password'])) {
                jsonResponse(['success' => false, 'message' => 'Senha atual incorreta']);
            }
            
            // Atualizar senha
            $new_password_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$new_password_hash, $user_id]);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Senha alterada com sucesso']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Erro ao alterar senha']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
