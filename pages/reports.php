
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar estatísticas para relatórios
$stats = [
    'total_clients' => 0,
    'active_clients' => 0,
    'monthly_revenue' => 0,
    'overdue_clients' => 0
];

try {
    // Total de clientes
    $query = "SELECT COUNT(*) as total FROM clients WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $stats['total_clients'] = $stmt->fetchColumn();

    // Clientes ativos
    $query = "SELECT COUNT(*) as active FROM clients WHERE user_id = ? AND status = 'ativo'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $stats['active_clients'] = $stmt->fetchColumn();

    // Faturamento mensal
    $query = "SELECT SUM(valor_cobranca) as revenue FROM clients WHERE user_id = ? AND status = 'ativo'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;

    // Clientes em atraso
    $query = "SELECT COUNT(*) as overdue FROM clients WHERE user_id = ? AND data_vencimento < CURDATE() AND status != 'pago'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $stats['overdue_clients'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Relatórios</h6>
            </div>
            <div class="card-body">
                <!-- Estatísticas Rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['total_clients']; ?></h4>
                                <p>Total de Clientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['active_clients']; ?></h4>
                                <p>Clientes Ativos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h4>R$ <?php echo number_format($stats['monthly_revenue'], 2, ',', '.'); ?></h4>
                                <p>Faturamento Mensal</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h4><?php echo $stats['overdue_clients']; ?></h4>
                                <p>Em Atraso</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tipos de Relatórios -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-graph-up fa-3x text-primary mb-3"></i>
                                <h5>Faturamento</h5>
                                <p>Relatório de faturamento mensal e anual</p>
                                <form class="mb-2">
                                    <select class="form-select mb-2" id="revenueMonth">
                                        <option value="">Selecione o mês</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select class="form-select mb-2" id="revenueYear">
                                        <option value="">Selecione o ano</option>
                                        <?php for ($year = date('Y') - 2; $year <= date('Y'); $year++): ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </form>
                                <button class="btn btn-primary" onclick="generateReport('revenue')">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-people fa-3x text-success mb-3"></i>
                                <h5>Clientes</h5>
                                <p>Lista completa de clientes com detalhes</p>
                                <form class="mb-2">
                                    <select class="form-select mb-2" id="clientStatus">
                                        <option value="">Todos os status</option>
                                        <option value="ativo">Apenas ativos</option>
                                        <option value="inativo">Apenas inativos</option>
                                    </select>
                                </form>
                                <button class="btn btn-success" onclick="generateReport('clients')">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h5>Inadimplência</h5>
                                <p>Clientes em atraso e cobranças pendentes</p>
                                <form class="mb-2">
                                    <select class="form-select mb-2" id="overdueFilter">
                                        <option value="">Todos os atrasos</option>
                                        <option value="7">Até 7 dias</option>
                                        <option value="30">Até 30 dias</option>
                                        <option value="90">Mais de 90 dias</option>
                                    </select>
                                </form>
                                <button class="btn btn-warning" onclick="generateReport('overdue')">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-calendar fa-3x text-info mb-3"></i>
                                <h5>Período Personalizado</h5>
                                <p>Relatório personalizado por período</p>
                                <form class="mb-2">
                                    <input type="date" class="form-control mb-2" id="startDate">
                                    <input type="date" class="form-control mb-2" id="endDate">
                                </form>
                                <button class="btn btn-info" onclick="generateReport('custom')">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para exibir relatórios -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Relatório</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportContent">
                <!-- Conteúdo do relatório será carregado aqui -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="downloadReport()">
                    <i class="bi bi-download"></i> Download PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Gerar relatórios
function generateReport(type) {
    let params = { action: 'generate', type: type };
    
    switch(type) {
        case 'revenue':
            params.month = document.getElementById('revenueMonth').value;
            params.year = document.getElementById('revenueYear').value;
            break;
        case 'clients':
            params.status = document.getElementById('clientStatus').value;
            break;
        case 'overdue':
            params.filter = document.getElementById('overdueFilter').value;
            break;
        case 'custom':
            params.start_date = document.getElementById('startDate').value;
            params.end_date = document.getElementById('endDate').value;
            if (!params.start_date || !params.end_date) {
                showNotification('Selecione as datas de início e fim', 'warning');
                return;
            }
            break;
    }
    
    showNotification('Gerando relatório...', 'info');
    
    fetch('api/reports.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(params)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reportContent').innerHTML = data.content;
            new bootstrap.Modal(document.getElementById('reportModal')).show();
            showNotification('Relatório gerado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao gerar relatório', 'danger');
        console.error('Erro:', error);
    });
}

// Download do relatório
function downloadReport() {
    showNotification('Preparando download...', 'info');
    // Aqui você implementaria a geração de PDF
    setTimeout(() => {
        showNotification('Download iniciado!', 'success');
    }, 1000);
}
</script>
