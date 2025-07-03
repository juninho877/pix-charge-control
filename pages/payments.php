
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar pagamentos
$payments = [];
try {
    $query = "SELECT c.name as client_name, c.email, c.valor_cobranca, c.data_vencimento, c.status, c.id 
              FROM clients c WHERE c.user_id = ? ORDER BY c.data_vencimento DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $payments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Erro ao buscar pagamentos: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Gerenciar Pagamentos</h6>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="bi bi-plus"></i> Novo Pagamento
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchPayment" placeholder="Buscar por cliente...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="statusFilter">
                            <option value="">Todos os status</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="pendente">Pendente</option>
                        </select>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['email']); ?></td>
                                <td>R$ <?php echo number_format($payment['valor_cobranca'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($payment['data_vencimento'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $payment['status'] == 'ativo' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="confirmPayment(<?php echo $payment['id']; ?>)">
                                        <i class="bi bi-check"></i> Confirmar
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="generatePix(<?php echo $payment['id']; ?>)">
                                        <i class="bi bi-qr-code"></i> PIX
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="sendReminder(<?php echo $payment['id']; ?>)">
                                        <i class="bi bi-whatsapp"></i> Lembrete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Pagamento -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Pagamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPaymentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select class="form-select" name="client_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php
                            try {
                                $query = "SELECT id, name FROM clients WHERE user_id = ? ORDER BY name";
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$user_id]);
                                $clients = $stmt->fetchAll();
                                foreach ($clients as $client) {
                                    echo '<option value="' . $client['id'] . '">' . htmlspecialchars($client['name']) . '</option>';
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Erro ao carregar clientes</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" class="form-control" name="valor" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="data_vencimento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Confirmar pagamento
function confirmPayment(paymentId) {
    if (confirm('Confirmar pagamento?')) {
        fetch('api/payments.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'confirm', id: paymentId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Pagamento confirmado!', 'success');
                location.reload();
            } else {
                showNotification('Erro: ' + data.message, 'danger');
            }
        });
    }
}

// Gerar PIX
function generatePix(paymentId) {
    showNotification('Gerando PIX...', 'info');
    fetch('api/payments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'generate_pix', id: paymentId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('PIX gerado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    });
}

// Enviar lembrete
function sendReminder(paymentId) {
    showNotification('Enviando lembrete...', 'info');
    fetch('api/payments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'send_reminder', id: paymentId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Lembrete enviado!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    });
}

// Formulário de novo pagamento
document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'add';
    
    fetch('api/payments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Pagamento adicionado!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addPaymentModal')).hide();
            location.reload();
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    });
});

// Filtros
document.getElementById('searchPayment').addEventListener('input', function() {
    filterPayments();
});

document.getElementById('statusFilter').addEventListener('change', function() {
    filterPayments();
});

function filterPayments() {
    const search = document.getElementById('searchPayment').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#paymentsTable tbody tr');
    
    rows.forEach(row => {
        const clientName = row.cells[0].textContent.toLowerCase();
        const clientEmail = row.cells[1].textContent.toLowerCase();
        const rowStatus = row.cells[4].textContent.toLowerCase().trim();
        
        const matchesSearch = clientName.includes(search) || clientEmail.includes(search);
        const matchesStatus = !status || rowStatus.includes(status);
        
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}
</script>
