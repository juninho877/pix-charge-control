
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar configurações
$settings = [];
try {
    $query = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch() ?: [];
} catch (Exception $e) {
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Configurações do Sistema</h6>
            </div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Mercado Pago</h5>
                                    <div class="form-group mb-3">
                                        <label>Access Token</label>
                                        <input type="text" class="form-control" name="mp_access_token" 
                                               value="<?php echo htmlspecialchars($settings['mp_access_token'] ?? ''); ?>" 
                                               placeholder="APP_USR-...">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Public Key</label>
                                        <input type="text" class="form-control" name="mp_public_key" 
                                               value="<?php echo htmlspecialchars($settings['mp_public_key'] ?? ''); ?>" 
                                               placeholder="APP_USR-...">
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="mp_sandbox" 
                                               <?php echo ($settings['mp_sandbox'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Modo Sandbox (Teste)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Evolution API</h5>
                                    <div class="form-group mb-3">
                                        <label>URL da API</label>
                                        <input type="text" class="form-control" name="evolution_url" 
                                               value="<?php echo htmlspecialchars($settings['evolution_url'] ?? 'https://evolution.example.com'); ?>" 
                                               placeholder="https://evolution.example.com">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Token de Acesso</label>
                                        <input type="text" class="form-control" name="evolution_token" 
                                               value="<?php echo htmlspecialchars($settings['evolution_token'] ?? ''); ?>" 
                                               placeholder="Token da Evolution API">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label>Nome da Instância</label>
                                        <input type="text" class="form-control" name="evolution_instance" 
                                               value="<?php echo htmlspecialchars($settings['evolution_instance'] ?? 'minha-instancia'); ?>" 
                                               placeholder="minha-instancia">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Preferências Gerais</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="dark_mode" 
                                                       <?php echo ($settings['dark_mode'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Modo Escuro</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="notifications" 
                                                       <?php echo ($settings['notifications'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Notificações</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="auto_backup" 
                                                       <?php echo ($settings['auto_backup'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Backup Automático</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Salvar Configurações
                        </button>
                        <button type="button" class="btn btn-warning" onclick="testConnection()">
                            <i class="bi bi-wifi"></i> Testar Conexões
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'save_settings';
    
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Configurações salvas com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao salvar configurações', 'danger');
        console.error('Erro:', error);
    });
});

function testConnection() {
    showNotification('Testando conexões...', 'info');
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'test_connections'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Conexões testadas com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao testar conexões', 'danger');
        console.error('Erro:', error);
    });
}
</script>
