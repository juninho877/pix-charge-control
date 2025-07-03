
<?php
// Conectar ao banco de dados
$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Buscar estatísticas
try {
    // Total de clientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_clients = $stmt->fetchColumn();

    // Clientes ativos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients WHERE user_id = ? AND status = 'ativo'");
    $stmt->execute([$user_id]);
    $active_clients = $stmt->fetchColumn();

    // Vencimentos próximos (próximos 7 dias)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clients WHERE user_id = ? AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$user_id]);
    $due_soon = $stmt->fetchColumn();

    // Faturamento mensal
    $stmt = $conn->prepare("SELECT SUM(valor_cobranca) as total FROM clients WHERE user_id = ? AND status = 'ativo' AND MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())");
    $stmt->execute([$user_id]);
    $monthly_revenue = $stmt->fetchColumn() ?? 0;

    // Últimos clientes adicionados
    $stmt = $conn->prepare("SELECT * FROM clients WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_clients = $stmt->fetchAll();

} catch (Exception $e) {
    $total_clients = 0;
    $active_clients = 0;
    $due_soon = 0;
    $monthly_revenue = 0;
    $recent_clients = [];
}
?>

<div class="row">
    <!-- Estatísticas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Clientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_clients; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Clientes Ativos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_clients; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Vencimentos Próximos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $due_soon; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Faturamento Mensal</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($monthly_revenue, 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico e Atividades Recentes -->
<div class="row">
    <!-- Últimos Clientes -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Últimos Clientes Adicionados</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_clients)): ?>
                    <p class="text-muted">Nenhum cliente cadastrado ainda.</p>
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
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    <td>R$ <?php echo number_format($client['valor_cobranca'], 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $client['status'] == 'ativo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($client['status']); ?>
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
                <h6 class="m-0 font-weight-bold text-primary">Status das Integrações</h6>
            </div>
            <div class="card-body">
                <?php
                // Verificar configurações
                try {
                    $stmt = $conn->prepare("SELECT * FROM mercadopago_settings WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $mp_config = $stmt->fetch();

                    $stmt = $conn->prepare("SELECT * FROM whatsapp_settings WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $wa_config = $stmt->fetch();
                } catch (Exception $e) {
                    $mp_config = false;
                    $wa_config = false;
                }
                ?>
                
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-credit-card fa-2x <?php echo $mp_config ? 'text-success' : 'text-danger'; ?> me-3"></i>
                    <div>
                        <h6 class="mb-0">Mercado Pago</h6>
                        <small class="text-muted">
                            <?php echo $mp_config ? 'Configurado' : 'Não configurado'; ?>
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-whatsapp fa-2x <?php echo $wa_config ? 'text-success' : 'text-danger'; ?> me-3"></i>
                    <div>
                        <h6 class="mb-0">WhatsApp</h6>
                        <small class="text-muted">
                            <?php echo $wa_config ? 'Configurado' : 'Não configurado'; ?>
                        </small>
                    </div>
                </div>

                <a href="javascript:void(0)" onclick="loadPage('settings')" class="btn btn-primary btn-sm">
                    <i class="bi bi-gear"></i> Configurar Integrações
                </a>
            </div>
        </div>
    </div>
</div>
