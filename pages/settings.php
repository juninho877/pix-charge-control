
<?php
require_once 'classes/MercadoPago.php';

$mercadoPago = new MercadoPago();
$mp_settings = $mercadoPago->getSettings($_SESSION['user_id']);
$mp_data = $mp_settings['success'] ? $mp_settings['settings'] : null;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Configurações</h1>
</div>

<div class="row">
    <!-- Configurações do Mercado Pago -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-credit-card text-primary"></i> Mercado Pago
                </h5>
            </div>
            <div class="card-body">
                <form id="mercadoPagoForm">
                    <div class="mb-3">
                        <label for="mp_access_token" class="form-label">Access Token *</label>
                        <input type="password" class="form-control" id="mp_access_token" name="access_token" 
                               placeholder="Seu access token do Mercado Pago" 
                               value="<?php echo $mp_data['access_token'] ?? ''; ?>" required>
                        <div class="form-text">
                            <a href="https://www.mercadopago.com.br/developers/panel/credentials" target="_blank">
                                Como obter seu Access Token
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mp_valor_base" class="form-label">Valor Base (R$)</label>
                        <input type="number" class="form-control" id="mp_valor_base" name="valor_base" 
                               step="0.01" min="0" value="<?php echo $mp_data['valor_base'] ?? '0'; ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mp_desconto_3" class="form-label">Desconto 3 meses (%)</label>
                                <input type="number" class="form-control" id="mp_desconto_3" name="desconto_3_meses" 
                                       step="0.01" min="0" max="100" value="<?php echo $mp_data['desconto_3_meses'] ?? '0'; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mp_desconto_6" class="form-label">Desconto 6 meses (%)</label>
                                <input type="number" class="form-control" id="mp_desconto_6" name="desconto_6_meses" 
                                       step="0.01" min="0" max="100" value="<?php echo $mp_data['desconto_6_meses'] ?? '0'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Salvar Configurações
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Configurações do WhatsApp -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-whatsapp text-success"></i> WhatsApp
                </h5>
            </div>
            <div class="card-body">
                <form id="whatsappForm">
                    <div class="mb-3">
                        <label for="wa_instance_name" class="form-label">Nome da Instância *</label>
                        <input type="text" class="form-control" id="wa_instance_name" name="instance_name" 
                               placeholder="ex: minha-empresa" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="wa_api_key" class="form-label">API Key *</label>
                        <input type="password" class="form-control" id="wa_api_key" name="api_key" 
                               placeholder="Sua API Key da Evolution" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="wa_base_url" class="form-label">URL Base</label>
                        <input type="url" class="form-control" id="wa_base_url" name="base_url" 
                               value="https://evov2.duckdns.org/" placeholder="https://evov2.duckdns.org/">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check"></i> Salvar e Conectar
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="generateQRCode()">
                            <i class="bi bi-qr-code"></i> Gerar QR Code
                        </button>
                    </div>
                </form>
                
                <div id="qrCodeContainer" class="mt-3 text-center" style="display: none;">
                    <h6>Escaneie o QR Code com seu WhatsApp:</h6>
                    <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Configurações de Automação -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-robot text-info"></i> Automação de Cobranças
                </h5>
            </div>
            <div class="card-body">
                <form id="automationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_cobranca" name="auto_cobranca" checked>
                                    <label class="form-check-label" for="auto_cobranca">
                                        Ativar cobrança automática
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="dias_antecedencia" class="form-label">Dias de antecedência</label>
                                <select class="form-select" id="dias_antecedencia" name="dias_antecedencia">
                                    <option value="1">1 dia</option>
                                    <option value="2">2 dias</option>
                                    <option value="3" selected>3 dias</option>
                                    <option value="5">5 dias</option>
                                    <option value="7">7 dias</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notification_email" name="notification_email" checked>
                                    <label class="form-check-label" for="notification_email">
                                        Notificações por email
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notification_whatsapp" name="notification_whatsapp" checked>
                                    <label class="form-check-label" for="notification_whatsapp">
                                        Notificações por WhatsApp
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message_template" class="form-label">Modelo de Mensagem</label>
                        <textarea class="form-control" id="message_template" name="message_template" rows="4" 
                                  placeholder="Olá {nome}, sua cobrança de R$ {valor} vence em {dias} dias. Acesse: {link}">Olá {nome}, sua cobrança de R$ {valor} vence em {dias} dias. Acesse: {link}</textarea>
                        <div class="form-text">
                            Use as variáveis: {nome}, {valor}, {dias}, {link}, {vencimento}
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check"></i> Salvar Configurações
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Configurações de Perfil -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person text-secondary"></i> Preferências
                </h5>
            </div>
            <div class="card-body">
                <form id="preferencesForm">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" 
                                   <?php echo $user['dark_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="dark_mode">
                                Modo escuro
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="timezone" class="form-label">Fuso horário</label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="America/Sao_Paulo">São Paulo (UTC-3)</option>
                            <option value="America/Manaus">Manaus (UTC-4)</option>
                            <option value="America/Rio_Branco">Rio Branco (UTC-5)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-check"></i> Salvar Preferências
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Formulário Mercado Pago
document.getElementById('mercadoPagoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'save_mercadopago';
    
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Configurações do Mercado Pago salvas com sucesso!', 'success');
        } else {
            showAlert('Erro ao salvar configurações: ' + data.message, 'danger');
        }
    });
});

// Formulário WhatsApp
document.getElementById('whatsappForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'save_whatsapp';
    
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Configurações do WhatsApp salvas com sucesso!', 'success');
        } else {
            showAlert('Erro ao salvar configurações: ' + data.message, 'danger');
        }
    });
});

// Gerar QR Code
function generateQRCode() {
    fetch('api/whatsapp.php?action=generate_qr')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.qr_code) {
                document.getElementById('qrCodeImage').src = data.qr_code;
                document.getElementById('qrCodeContainer').style.display = 'block';
            } else {
                showAlert('Erro ao gerar QR Code: ' + data.message, 'danger');
            }
        });
}

// Função para mostrar alertas
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.insertBefore(alertDiv, document.body.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
