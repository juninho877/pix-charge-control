
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
    case 'generate':
        try {
            $type = $input['type'] ?? '';
            $content = '';
            
            switch ($type) {
                case 'revenue':
                    $month = $input['month'] ?? date('m');
                    $year = $input['year'] ?? date('Y');
                    
                    $query = "SELECT SUM(valor_cobranca) as total, COUNT(*) as count FROM clients WHERE user_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? AND status = 'ativo'";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$user_id, $month, $year]);
                    $data = $stmt->fetch();
                    
                    $content = "<h4>Relatório de Faturamento - " . date('F/Y', mktime(0, 0, 0, $month, 1, $year)) . "</h4>";
                    $content .= "<p><strong>Total Faturado:</strong> R$ " . number_format($data['total'] ?? 0, 2, ',', '.') . "</p>";
                    $content .= "<p><strong>Número de Cobranças:</strong> " . ($data['count'] ?? 0) . "</p>";
                    break;
                    
                case 'clients':
                    $status = $input['status'] ?? '';
                    $where = $status ? "AND status = ?" : "";
                    $params = $status ? [$user_id, $status] : [$user_id];
                    
                    $query = "SELECT name, email, phone, valor_cobranca, status, created_at FROM clients WHERE user_id = ? $where ORDER BY name";
                    $stmt = $conn->prepare($query);
                    $stmt->execute($params);
                    $clients = $stmt->fetchAll();
                    
                    $content = "<h4>Relatório de Clientes</h4>";
                    $content .= "<table class='table table-striped'>";
                    $content .= "<thead><tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Valor</th><th>Status</th></tr></thead>";
                    $content .= "<tbody>";
                    foreach ($clients as $client) {
                        $content .= "<tr>";
                        $content .= "<td>" . htmlspecialchars($client['name']) . "</td>";
                        $content .= "<td>" . htmlspecialchars($client['email']) . "</td>";
                        $content .= "<td>" . htmlspecialchars($client['phone'] ?? '') . "</td>";
                        $content .= "<td>R$ " . number_format($client['valor_cobranca'], 2, ',', '.') . "</td>";
                        $content .= "<td>" . ucfirst($client['status']) . "</td>";
                        $content .= "</tr>";
                    }
                    $content .= "</tbody></table>";
                    break;
                    
                case 'overdue':
                    $filter = $input['filter'] ?? '';
                    $where = '';
                    if ($filter) {
                        $where = "AND DATEDIFF(CURDATE(), data_vencimento) <= $filter";
                    }
                    
                    $query = "SELECT name, email, valor_cobranca, data_vencimento, DATEDIFF(CURDATE(), data_vencimento) as days_overdue 
                             FROM clients WHERE user_id = ? AND data_vencimento < CURDATE() AND status != 'pago' $where ORDER BY data_vencimento";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$user_id]);
                    $overdue = $stmt->fetchAll();
                    
                    $content = "<h4>Relatório de Inadimplência</h4>";
                    $content .= "<table class='table table-striped'>";
                    $content .= "<thead><tr><th>Cliente</th><th>Email</th><th>Valor</th><th>Vencimento</th><th>Dias em Atraso</th></tr></thead>";
                    $content .= "<tbody>";
                    foreach ($overdue as $item) {
                        $content .= "<tr>";
                        $content .= "<td>" . htmlspecialchars($item['name']) . "</td>";
                        $content .= "<td>" . htmlspecialchars($item['email']) . "</td>";
                        $content .= "<td>R$ " . number_format($item['valor_cobranca'], 2, ',', '.') . "</td>";
                        $content .= "<td>" . date('d/m/Y', strtotime($item['data_vencimento'])) . "</td>";
                        $content .= "<td class='text-danger'>" . $item['days_overdue'] . " dias</td>";
                        $content .= "</tr>";
                    }
                    $content .= "</tbody></table>";
                    break;
                    
                case 'custom':
                    $start_date = $input['start_date'];
                    $end_date = $input['end_date'];
                    
                    $query = "SELECT COUNT(*) as total_clients, SUM(valor_cobranca) as total_revenue 
                             FROM clients WHERE user_id = ? AND created_at BETWEEN ? AND ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$user_id, $start_date, $end_date]);
                    $data = $stmt->fetch();
                    
                    $content = "<h4>Relatório Personalizado</h4>";
                    $content .= "<p><strong>Período:</strong> " . date('d/m/Y', strtotime($start_date)) . " até " . date('d/m/Y', strtotime($end_date)) . "</p>";
                    $content .= "<p><strong>Clientes Cadastrados:</strong> " . ($data['total_clients'] ?? 0) . "</p>";
                    $content .= "<p><strong>Receita Total:</strong> R$ " . number_format($data['total_revenue'] ?? 0, 2, ',', '.') . "</p>";
                    break;
                    
                default:
                    jsonResponse(['success' => false, 'message' => 'Tipo de relatório inválido']);
            }
            
            jsonResponse(['success' => true, 'content' => $content]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Erro ao gerar relatório: ' . $e->getMessage()]);
        }
        break;
        
    default:
        jsonResponse(['success' => false, 'message' => 'Ação não válida'], 400);
}
?>
