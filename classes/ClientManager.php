
<?php
require_once 'config/database.php';
require_once 'config/config.php';

class ClientManager {
    private $conn;
    private $table_name = "clients";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createClient($user_id, $data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, name, email, phone, valor_cobranca, data_vencimento) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $user_id,
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['valor_cobranca'],
                $data['data_vencimento']
            ]);

            if ($result) {
                $client_id = $this->conn->lastInsertId();
                return ['success' => true, 'client_id' => $client_id, 'message' => 'Cliente criado com sucesso'];
            }

            return ['success' => false, 'message' => 'Erro ao criar cliente'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getClients($user_id, $filters = []) {
        try {
            $where_conditions = ["user_id = ?"];
            $params = [$user_id];

            // Aplicar filtros
            if (!empty($filters['search'])) {
                $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $search_term = '%' . $filters['search'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }

            if (!empty($filters['status'])) {
                $where_conditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
                $where_conditions[] = "data_vencimento BETWEEN ? AND ?";
                $params[] = $filters['data_inicio'];
                $params[] = $filters['data_fim'];
            }

            // Paginação
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $per_page = isset($filters['per_page']) ? (int)$filters['per_page'] : 10;
            $offset = ($page - 1) * $per_page;

            // Query principal
            $where_clause = implode(' AND ', $where_conditions);
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE " . $where_clause . " 
                     ORDER BY created_at DESC 
                     LIMIT ? OFFSET ?";
            
            $params[] = $per_page;
            $params[] = $offset;

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $clients = $stmt->fetchAll();

            // Contar total de registros
            $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                           WHERE " . $where_clause;
            
            $count_params = array_slice($params, 0, -2); // Remove limit e offset
            $count_stmt = $this->conn->prepare($count_query);
            $count_stmt->execute($count_params);
            $total = $count_stmt->fetch()['total'];

            return [
                'success' => true,
                'clients' => $clients,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'pages' => ceil($total / $per_page)
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getClient($user_id, $client_id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$client_id, $user_id]);

            if ($stmt->rowCount() == 1) {
                return ['success' => true, 'client' => $stmt->fetch()];
            }

            return ['success' => false, 'message' => 'Cliente não encontrado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function updateClient($user_id, $client_id, $data) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET name = ?, email = ?, phone = ?, valor_cobranca = ?, 
                         data_vencimento = ?, updated_at = NOW()
                     WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['valor_cobranca'],
                $data['data_vencimento'],
                $client_id,
                $user_id
            ]);

            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cliente atualizado com sucesso'];
            }

            return ['success' => false, 'message' => 'Nenhuma alteração realizada'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function deleteClient($user_id, $client_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$client_id, $user_id]);

            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Cliente excluído com sucesso'];
            }

            return ['success' => false, 'message' => 'Cliente não encontrado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function updatePaymentStatus($user_id, $client_id, $status, $payment_data = null) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET status = ?, updated_at = NOW()";
            $params = [$status];

            if ($payment_data) {
                $query .= ", payment_id = ?, payment_link = ?, qr_code = ?";
                $params[] = $payment_data['payment_id'] ?? null;
                $params[] = $payment_data['payment_link'] ?? null;
                $params[] = $payment_data['qr_code'] ?? null;
            }

            $query .= " WHERE id = ? AND user_id = ?";
            $params[] = $client_id;
            $params[] = $user_id;

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Status atualizado com sucesso'];
            }

            return ['success' => false, 'message' => 'Cliente não encontrado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getClientsByVencimento($user_id, $days_ahead = 3) {
        try {
            $target_date = date('Y-m-d', strtotime("+{$days_ahead} days"));
            
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE user_id = ? AND data_vencimento = ? AND status IN ('pendente', 'ativo')
                     ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $target_date]);

            return ['success' => true, 'clients' => $stmt->fetchAll()];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }

    public function getStatistics($user_id) {
        try {
            $stats = [];

            // Total de clientes
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['total_clients'] = $stmt->fetch()['total'];

            // Clientes ativos
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                     WHERE user_id = ? AND status = 'ativo'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['active_clients'] = $stmt->fetch()['total'];

            // Pagamentos recebidos no mês
            $query = "SELECT COUNT(*) as total, SUM(valor_cobranca) as valor FROM " . $this->table_name . " 
                     WHERE user_id = ? AND status = 'pago' AND MONTH(updated_at) = MONTH(NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $month_data = $stmt->fetch();
            $stats['paid_this_month'] = $month_data['total'];
            $stats['revenue_this_month'] = $month_data['valor'] ?? 0;

            // Cobranças pendentes
            $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                     WHERE user_id = ? AND status = 'pendente'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $stats['pending_charges'] = $stmt->fetch()['total'];

            return ['success' => true, 'stats' => $stats];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }
}
?>
