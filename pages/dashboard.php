
<?php
// Verificar se a conexão existe
if (!isset($conn) || !$conn) {
    $database = new Database();
    $conn = $database->getConnection();
}

$user_id = $_SESSION['user_id'] ?? 1;

// Inicializar variáveis com valores padrão
$total_clients = 0;
$active_clients = 0;
$due_soon = 0;
$monthly_revenue = 0;
$recent_clients = [];
$mp_config = false;
$wa_config = false;

// Buscar estatísticas se a conexão estiver disponível
if ($conn) {
    try {
        // Total de clientes
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE user_id = ?");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $total_clients = $stmt->fetchColumn() ?: 0;
        }

        // Clientes ativos
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE user_id = ? AND status = 'ativo'");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $active_clients = $stmt->fetchColumn() ?: 0;
        }

        // Vencimentos próximos
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE user_id = ? AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $due_soon = $stmt->fetchColumn() ?: 0;
        }

        // Faturamento mensal
        $stmt = $conn->prepare("SELECT SUM(valor_cobranca) FROM clients WHERE user_id = ? AND status = 'ativo' AND MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $monthly_revenue = $stmt->fetchColumn() ?: 0;
        }

        // Últimos clientes
        $stmt = $conn->prepare("SELECT * FROM clients WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $recent_clients = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Verificar configurações
        $stmt = $conn->prepare("SELECT COUNT(*) FROM mercadopago_settings WHERE user_id = ?");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $mp_config = $stmt->fetchColumn() > 0;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM whatsapp_settings WHERE user_id = ?");
        if ($stmt) {
            $stmt->execute([$user_id]);
            $wa_config = $stmt->fetchColumn() > 0;
        }
    } catch (Exception $e) {
        error_log("Erro no dashboard: " . $e->getMessage());
    }
}
?>

<div class="row">
    <!-- Estatísticas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total de Clientes</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $total_clients; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Clientes Ativos</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $active_clients; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Vencimentos Próximos</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $due_soon; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Faturamento Mensal</div>
                        <div class="h5 mb-0 fw-bold">R$ <?php echo number_format($monthly_revenue, 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar fs-2 text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seção de Clientes e Integrações -->
<div class="row">
    <!-- Últimos Clientes -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Últimos Clientes Adicionados</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_clients)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <p class="text-muted mt-2">Nenhum cliente cadastrado ainda.</p>
                        <button class="btn btn-primary btn-sm" onclick="loadPage('clients')">
                            <i class="bi bi-plus"></i> Adicionar Cliente
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['email'] ?? ''); ?></td>
                                    <td>R$ <?php echo number_format($client['valor_cobranca'] ?? 0, 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($client['status'] ?? '') == 'ativo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($client['status'] ?? 'inativo'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status das Integrações -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Status das Integrações</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-credit-card fs-2 <?php echo $mp_config ? 'text-success' : 'text-danger'; ?> me-3"></i>
                    <div>
                        <h6 class="mb-0">Mercado Pago</h6>
                        <small class="text-muted">
                            <?php echo $mp_config ? 'Configurado' : 'Não configurado'; ?>
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-whatsapp fs-2 <?php echo $wa_config ? 'text-success' : 'text-danger'; ?> me-3"></i>
                    <div>
                        <h6 class="mb-0">WhatsApp</h6>
                        <small class="text-muted">
                            <?php echo $wa_config ? 'Configurado' : 'Não configurado'; ?>
                        </small>
                    </div>
                </div>

                <button class="btn btn-primary btn-sm" onclick="loadPage('settings')">
                    <i class="bi bi-gear"></i> Configurar Integrações
                </button>
            </div>
        </div>
    </div>
</div>
