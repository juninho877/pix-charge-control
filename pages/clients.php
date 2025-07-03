
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar clientes
$clients = [];
try {
    $query = "SELECT * FROM clients WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Gerenciar Clientes</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="bi bi-plus"></i> Novo Cliente
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($clients)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-people fs-1 text-muted"></i>
                        <p class="text-muted mt-2">Nenhum cliente cadastrado ainda.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($client['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($client['phone'] ?? ''); ?></td>
                                    <td>R$ <?php echo number_format($client['valor_cobranca'] ?? 0, 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($client['data_vencimento'] ?? 'now')); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($client['status'] ?? '') == 'ativo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($client['status'] ?? 'inativo'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editClient(<?php echo $client['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteClient(<?php echo $client['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
</div>

<!-- Modal Adicionar Cliente -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="clientForm">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor da Cobrança</label>
                        <input type="number" class="form-control" name="valor_cobranca" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Vencimento</label>
                        <input type="date" class="form-control" name="data_vencimento" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveClient()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
function saveClient() {
    const form = document.getElementById('clientForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.action = 'add';
    
    fetch('api/clients.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Cliente adicionado com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addClientModal')).hide();
            loadPage('clients');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao salvar cliente', 'danger');
        console.error('Erro:', error);
    });
}

function editClient(id) {
    showNotification('Funcionalidade de edição em desenvolvimento', 'info');
}

function deleteClient(id) {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        fetch('api/clients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Cliente excluído com sucesso!', 'success');
                loadPage('clients');
            } else {
                showNotification('Erro: ' + data.message, 'danger');
            }
        });
    }
}
</script>
