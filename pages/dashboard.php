
<?php
// Simulação de dados para o dashboard - remover require das classes problemáticas
$stats_data = [
    'total_clients' => 25,
    'active_clients' => 18,
    'revenue_this_month' => 15750.00,
    'pending_charges' => 7
];

$clients_data = [
    [
        'id' => 1,
        'name' => 'João Silva',
        'phone' => '(11) 99999-9999',
        'valor_cobranca' => 150.00,
        'data_vencimento' => '2024-01-15',
        'status' => 'pendente'
    ],
    [
        'id' => 2,
        'name' => 'Maria Santos',
        'phone' => '(11) 88888-8888',
        'valor_cobranca' => 200.00,
        'data_vencimento' => '2024-01-10',
        'status' => 'pago'
    ]
];

$mp_configured = true;
?>

<div class="row">
    <!-- Cards de Estatísticas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Clientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_data['total_clients']; ?></div>
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
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_data['active_clients']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Recebido Este Mês</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($stats_data['revenue_this_month'], 2, ',', '.'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Cobranças Pendentes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats_data['pending_charges']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Vendas -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Visão Geral dos Pagamentos</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="paymentChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Status das Integrações -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Status das Integrações</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-credit-card text-primary"></i>
                            <span class="ms-2">Mercado Pago</span>
                        </div>
                        <span class="badge <?php echo $mp_configured ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo $mp_configured ? 'Conectado' : 'Pendente'; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-whatsapp text-success"></i>
                            <span class="ms-2">WhatsApp</span>
                        </div>
                        <span class="badge bg-warning">Pendente</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-robot text-info"></i>
                            <span class="ms-2">Automação</span>
                        </div>
                        <span class="badge bg-success">Ativo</span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="javascript:void(0)" onclick="loadPage('settings')" class="btn btn-primary btn-sm">
                        <i class="bi bi-gear"></i> Configurar Integrações
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clientes Recentes -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Clientes Recentes</h6>
            </div>
            <div class="card-body">
                <?php if (empty($clients_data)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">Nenhum cliente cadastrado ainda.</p>
                        <a href="javascript:void(0)" onclick="loadPage('clients')" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Adicionar Primeiro Cliente
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Telefone</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients_data as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                    <td>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $client['phone']); ?>" 
                                           target="_blank" class="text-success">
                                            <i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($client['phone']); ?>
                                        </a>
                                    </td>
                                    <td>R$ <?php echo number_format($client['valor_cobranca'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($client['data_vencimento'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $client['status'] == 'pago' ? 'success' : 
                                                ($client['status'] == 'vencido' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($client['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editClient(<?php echo $client['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="generatePayment(<?php echo $client['id']; ?>)">
                                                <i class="bi bi-qr-code"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="javascript:void(0)" onclick="loadPage('clients')" class="btn btn-primary">
                            Ver Todos os Clientes
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de pagamentos
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var ctx = document.getElementById('paymentChart');
        if (ctx) {
            ctx = ctx.getContext('2d');
            var paymentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Pagamentos Recebidos',
                        data: [1200, 1900, 3000, 5000, 2000, 3000],
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolução dos Pagamentos'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    }
                }
            });
        }
    }, 500);
});

// Funções auxiliares
function editClient(clientId) {
    alert('Editar cliente ID: ' + clientId);
}

function generatePayment(clientId) {
    alert('Gerar pagamento para cliente ID: ' + clientId);
}
</script>
