
<?php
require_once 'classes/ClientManager.php';

$clientManager = new ClientManager();

// Filtros
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;

$filters = [
    'search' => $search,
    'status' => $status,
    'page' => $page,
    'per_page' => 10
];

$result = $clientManager->getClients($_SESSION['user_id'], $filters);
$clients = $result['success'] ? $result['clients'] : [];
$pagination = $result['pagination'] ?? null;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestão de Clientes</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
            <i class="bi bi-plus"></i> Novo Cliente
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="GET">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Nome, email ou telefone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo $status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="pendente" <?php echo $status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="pago" <?php echo $status == 'pago' ? 'selected' : ''; ?>>Pago</option>
                    <option value="vencido" <?php echo $status == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Clientes -->
<div class="card">
    <div class="card-body">
        <?php if (empty($clients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum cliente encontrado</h5>
                <p class="text-muted">Adicione seu primeiro cliente para começar</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="bi bi-plus"></i> Adicionar Cliente
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Cobrança</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                    <?php if ($client['email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $client['phone']); ?>" 
                                   target="_blank" class="text-success text-decoration-none">
                                    <i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($client['phone']); ?>
                                </a>
                            </td>
                            <td>
                                <strong>R$ <?php echo number_format($client['valor_cobranca'], 2, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <?php 
                                $vencimento = strtotime($client['data_vencimento']);
                                $hoje = time();
                                $class = $vencimento < $hoje ? 'text-danger' : ($vencimento - $hoje < 86400 * 3 ? 'text-warning' : '');
                                ?>
                                <span class="<?php echo $class; ?>">
                                    <?php echo date('d/m/Y', $vencimento); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $client['status'] == 'pago' ? 'success' : 
                                        ($client['status'] == 'vencido' ? 'danger' : 
                                        ($client['status'] == 'ativo' ? 'primary' : 'warning')); 
                                ?>">
                                    <?php echo ucfirst($client['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editClient(<?php echo $client['id']; ?>)" 
                                            title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-success" onclick="generatePayment(<?php echo $client['id']; ?>)" 
                                            title="Gerar Pagamento">
                                        <i class="bi bi-qr-code"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="sendWhatsApp(<?php echo $client['id']; ?>)" 
                                            title="Enviar WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteClient(<?php echo $client['id']; ?>)" 
                                            title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($pagination && $pagination['pages'] > 1): ?>
            <nav aria-label="Paginação">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                    <li class="page-item <?php echo $i == $pagination['page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
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
            <form id="addClientForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="client_name" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="client_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="client_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="client_email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="client_phone" class="form-label">Telefone/WhatsApp *</label>
                        <input type="text" class="form-control" id="client_phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="client_value" class="form-label">Valor da Cobrança *</label>
                        <input type="number" class="form-control" id="client_value" name="valor_cobranca" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="client_due_date" class="form-label">Data de Vencimento *</label>
                        <input type="date" class="form-control" id="client_due_date" name="data_vencimento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editClientForm">
                <input type="hidden" id="edit_client_id" name="client_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_client_name" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="edit_client_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_client_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_client_email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="edit_client_phone" class="form-label">Telefone/WhatsApp *</label>
                        <input type="text" class="form-control" id="edit_client_phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_client_value" class="form-label">Valor da Cobrança *</label>
                        <input type="number" class="form-control" id="edit_client_value" name="valor_cobranca" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_client_due_date" class="form-label">Data de Vencimento *</label>
                        <input type="date" class="form-control" id="edit_client_due_date" name="data_vencimento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funções para gerenciar clientes
function editClient(clientId) {
    // Buscar dados do cliente e preencher modal
    fetch(`api/clients.php?action=get&id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const client = data.client;
                document.getElementById('edit_client_id').value = client.id;
                document.getElementById('edit_client_name').value = client.name;
                document.getElementById('edit_client_email').value = client.email || '';
                document.getElementById('edit_client_phone').value = client.phone;
                document.getElementById('edit_client_value').value = client.valor_cobranca;
                document.getElementById('edit_client_due_date').value = client.data_vencimento;
                
                new bootstrap.Modal(document.getElementById('editClientModal')).show();
            }
        });
}

function deleteClient(clientId) {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        fetch('api/clients.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', client_id: clientId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao excluir cliente: ' + data.message);
            }
        });
    }
}

function generatePayment(clientId) {
    // Implementar geração de pagamento
    console.log('Gerar pagamento para cliente:', clientId);
}

function sendWhatsApp(clientId) {
    // Implementar envio de WhatsApp
    console.log('Enviar WhatsApp para cliente:', clientId);
}

// Formulários
document.getElementById('addClientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'create';
    
    fetch('api/clients.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao criar cliente: ' + data.message);
        }
    });
});

document.getElementById('editClientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'update';
    
    fetch('api/clients.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao atualizar cliente: ' + data.message);
        }
    });
});
</script>
