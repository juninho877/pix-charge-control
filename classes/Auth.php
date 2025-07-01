
<?php
require_once 'config/database.php';
require_once 'config/config.php';

class Auth {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($name, $email, $password, $phone = null) {
        try {
            // Verificar se email já existe
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email já cadastrado'];
            }

            // Hash da senha
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = generateToken();

            // Inserir usuário
            $query = "INSERT INTO " . $this->table_name . " 
                     (name, email, password, phone, verification_token) 
                     VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$name, $email, $password_hash, $phone, $verification_token]);

            if ($result) {
                $user_id = $this->conn->lastInsertId();
                
                // Criar configurações padrão para o usuário
                $this->createDefaultSettings($user_id);
                
                // Enviar email de verificação (implementar conforme necessário)
                // $this->sendVerificationEmail($email, $verification_token);
                
                return ['success' => true, 'message' => 'Usuário cadastrado com sucesso'];
            }

            return ['success' => false, 'message' => 'Erro ao cadastrar usuário'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            $query = "SELECT id, name, email, password, is_active, email_verified 
                     FROM " . $this->table_name . " WHERE email = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                
                if (!$user['is_active']) {
                    return ['success' => false, 'message' => 'Conta desativada'];
                }

                if (password_verify($password, $user['password'])) {
                    // Criar sessão
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['logged_in'] = true;

                    // Atualizar último login
                    $this->updateLastLogin($user['id']);

                    return ['success' => true, 'message' => 'Login realizado com sucesso'];
                }
            }

            return ['success' => false, 'message' => 'Email ou senha incorretos'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }

    public function forgotPassword($email) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? AND is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);

            if ($stmt->rowCount() == 1) {
                $reset_token = generateToken();
                $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $query = "UPDATE " . $this->table_name . " 
                         SET reset_token = ?, reset_expires = ? WHERE email = ?";
                
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$reset_token, $reset_expires, $email]);

                if ($result) {
                    // Enviar email com token (implementar conforme necessário)
                    // $this->sendResetEmail($email, $reset_token);
                    
                    return ['success' => true, 'message' => 'Email de recuperação enviado'];
                }
            }

            return ['success' => false, 'message' => 'Email não encontrado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function resetPassword($token, $new_password) {
        try {
            $query = "SELECT id FROM " . $this->table_name . " 
                     WHERE reset_token = ? AND reset_expires > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$token]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                $query = "UPDATE " . $this->table_name . " 
                         SET password = ?, reset_token = NULL, reset_expires = NULL 
                         WHERE id = ?";
                
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$password_hash, $user['id']]);

                if ($result) {
                    return ['success' => true, 'message' => 'Senha alterada com sucesso'];
                }
            }

            return ['success' => false, 'message' => 'Token inválido ou expirado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    private function createDefaultSettings($user_id) {
        $query = "INSERT INTO user_settings (user_id) VALUES (?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
    }

    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " SET updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
    }

    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }

        $query = "SELECT u.*, us.dark_mode, us.timezone 
                 FROM " . $this->table_name . " u
                 LEFT JOIN user_settings us ON u.id = us.user_id
                 WHERE u.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);

        return $stmt->fetch();
    }
}
?>
