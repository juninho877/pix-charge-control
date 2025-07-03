
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar dados para relatórios
$stats = [
    'total_clients' => 0,
    'active_clients' => 0,
    'monthly_revenue' => 0,
    'pending_payments' => 0
];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_clients'] = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE user_id = ? AND status = 'ativo'");
    $stmt->execute([$user_id]);
    $stats['active_clients'] = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT SUM(valor_cobranca) FROM clients WHERE user_id = ? AND status = 'ativo'");
    $stmt->execute([$user_id]);
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE user_id = ? AND data_vencimento < CURDATE() AND status = 'ativo'");
    $stmt->execute([$user_id]);
    $stats['pending_payments'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Relatórios e Estatísticas</h6>
                <div>
                    <button class="btn btn-primary btn-sm" onclick="generateReport('monthly')">
                        <i class="bi bi-file-earmark-pdf"></i> Relatório Mensal
                    </button>
                    <button class="btn btn-success btn-sm" onclick="exportData('excel')">
                        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Estatísticas Resumidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['total_clients']; ?></h5>
                                <small>Total de Clientes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['active_clients']; ?></h5>
                                <small>Clientes Ativos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>R$ <?php echo number_format($stats['monthly_revenue'], 2, ',', '.'); ?></h5>
                                <small>Receita Mensal</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5><?php echo $stats['pending_payments']; ?></h5>
                                <small>Pagamentos Pendentes</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros de Relatório -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Período</label>
                        <select class="form-control" id="periodFilter">
                            <option value="7">Últimos 7 dias</option>
                            <option value="30" selected>Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                            <option value="365">Último ano</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Status</label>
                        <select class="form-control" id="statusFilter">
                            <option value="">Todos</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Data Inicial</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3">
                        <label>Data Final</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                
                <button class="btn btn-primary" onclick="loadReportData()">
                    <i class="bi bi-search"></i> Gerar Relatório
                </button>
                
                <!-- Área do Relatório -->
                <div id="reportContent" class="mt-4">
                    <div class="text-center">
                        <i class="bi bi-bar-chart fs-1 text-muted"></i>
                        <p class="text-muted">Selecione os filtros e clique em "Gerar Relatório"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadReportData() {
    const period = document.getElementById('periodFilter').value;
    const status = document.getElementById('statusFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    const data = {
        action: 'generate_report',
        period: period,
        status: status,
        start_date: startDate,
        end_date: endDate
    };
    
    document.getElementById('reportContent').innerHTML = '<div class="text-center"><div class="spinner-border"></div><p>Gerando relatório...</p></div>';
    
    fetch('api/reports.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reportContent').innerHTML = data.html;
            showNotification('Relatório gerado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao gerar relatório', 'danger');
    });
}

function generateReport(type) {
    showNotification('Gerando relatório em PDF...', 'info');
    fetch('api/reports.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'generate_pdf', type: type})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Relatório PDF gerado!', 'success');
            if (data.download_url) {
                window.open(data.download_url, '_blank');
            }
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    });
}

function exportData(format) {
    showNotification('Exportando dados...', 'info');
    fetch('api/reports.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'export_data', format: format})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Dados exportados!', 'success');
            if (data.download_url) {
                window.open(data.download_url, '_blank');
            }
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    });
}
</script>
