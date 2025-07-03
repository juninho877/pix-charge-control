
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar configurações existentes
$settings = [];
try {
    $query = "SELECT * FROM whatsapp_settings WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log("Erro ao buscar configurações WhatsApp: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-whatsapp"></i> Integração WhatsApp - Evolution API
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Configure sua integração com WhatsApp usando a Evolution API para envio automático de cobranças.
                </div>
                
                <form id="whatsappConfigForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Nome da Instância</label>
                                <input type="text" class="form-control" name="instance_name" 
                                       value="<?php echo htmlspecialchars($settings['instance_name'] ?? ''); ?>" 
                                       placeholder="minha-instancia" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>API Key</label>
                                <input type="text" class="form-control" name="api_key" 
                                       value="<?php echo htmlspecialchars($settings['api_key'] ?? ''); ?>" 
                                       placeholder="Sua API Key" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>URL Base da API</label>
                        <input type="url" class="form-control" name="base_url" 
                               value="<?php echo htmlspecialchars($settings['base_url'] ?? 'https://api.evolutionapi.com'); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Número do WhatsApp (opcional)</label>
                        <input type="tel" class="form-control" name="phone_number" 
                               value="<?php echo htmlspecialchars($settings['phone_number'] ?? ''); ?>" 
                               placeholder="5511999999999">
                    </div>
                    
                    <div class="d-flex gap-2 mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Salvar Configurações
                        </button>
                        <button type="button" class="btn btn-success" onclick="createInstance()" id="createBtn">
                            <i class="bi bi-plus-circle"></i> Criar Instância
                        </button>
                        <button type="button" class="btn btn-info" onclick="generateQR()" id="qrBtn">
                            <i class="bi bi-qr-code"></i> Gerar QR Code
                        </button>
                        <button type="button" class="btn btn-warning" onclick="checkStatus()" id="statusBtn">
                            <i class="bi bi-check-circle"></i> Verificar Status
                        </button>
                    </div>
                </form>

                <!-- Status da Conexão -->
                <div id="connectionStatus" class="alert alert-secondary" style="display: none;">
                    <strong>Status:</strong> <span id="statusText">Verificando...</span>
                </div>

                <!-- QR Code -->
                <div id="qrCodeSection" style="display: none;">
                    <h5>QR Code para Conexão</h5>
                    <div class="text-center p-4">
                        <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                        <p class="mt-2 text-muted">Escaneie o QR Code com seu WhatsApp para conectar</p>
                        <button class="btn btn-sm btn-secondary" onclick="generateQR()">
                            <i class="bi bi-arrow-clockwise"></i> Atualizar QR Code
                        </button>
                    </div>
                </div>

                <!-- Teste de Envio -->
                <div class="mt-4">
                    <h6>Teste de Envio de Mensagem</h6>
                    <form id="testMessageForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Número de Destino</label>
                                    <input type="tel" class="form-control" name="test_phone" 
                                           placeholder="5511999999999" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Mensagem</label>
                                    <input type="text" class="form-control" name="test_message" 
                                           value="Teste de conexão WhatsApp!" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send"></i> Enviar Teste
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configurações WhatsApp
document.getElementById('whatsappConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'save_config';
    
    fetch('api/whatsapp.php', {
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

// Criar instância
function createInstance() {
    fetch('api/whatsapp.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'create_instance'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Instância criada com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao criar instância', 'danger');
        console.error('Erro:', error);
    });
}

// Gerar QR Code
function generateQR() {
    document.getElementById('qrBtn').disabled = true;
    document.getElementById('qrBtn').innerHTML = '<i class="bi bi-arrow-clockwise"></i> Gerando...';
    
    fetch('api/whatsapp.php?action=generate_qr')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.qr_code) {
            document.getElementById('qrCodeImage').src = 'data:image/png;base64,' + data.qr_code;
            document.getElementById('qrCodeSection').style.display = 'block';
            showNotification('QR Code gerado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao gerar QR Code', 'danger');
        console.error('Erro:', error);
    })
    .finally(() => {
        document.getElementById('qrBtn').disabled = false;
        document.getElementById('qrBtn').innerHTML = '<i class="bi bi-qr-code"></i> Gerar QR Code';
    });
}

// Verificar status
function checkStatus() {
    fetch('api/whatsapp.php?action=check_status')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const statusDiv = document.getElementById('connectionStatus');
            const statusText = document.getElementById('statusText');
            
            statusDiv.style.display = 'block';
            statusDiv.className = 'alert alert-' + (data.status === 'connected' ? 'success' : 'warning');
            statusText.textContent = data.status === 'connected' ? 'Conectado' : 
                                   data.status === 'connecting' ? 'Conectando...' : 'Desconectado';
            
            showNotification('Status verificado: ' + statusText.textContent, 'info');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao verificar status', 'danger');
        console.error('Erro:', error);
    });
}

// Teste de mensagem
document.getElementById('testMessageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'send_test';
    
    fetch('api/whatsapp.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Mensagem de teste enviada!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao enviar mensagem', 'danger');
        console.error('Erro:', error);
    });
});
</script>
