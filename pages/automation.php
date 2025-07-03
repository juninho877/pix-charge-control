
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar configurações de automação
$automation_settings = [];
try {
    $query = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $automation_settings = $stmt->fetch() ?: [];
} catch (Exception $e) {
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Automação de Cobranças</h6>
            </div>
            <div class="card-body">
                <form id="automationConfigForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Lembretes Automáticos</h5>
                                    <p>Configure quando enviar lembretes de vencimento.</p>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reminder_3_days" name="reminder_3_days" 
                                               <?php echo ($automation_settings['reminder_3_days'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="reminder_3_days">3 dias antes do vencimento</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reminder_due_date" name="reminder_due_date" 
                                               <?php echo ($automation_settings['reminder_due_date'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="reminder_due_date">No dia do vencimento</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="reminder_overdue" name="reminder_overdue" 
                                               <?php echo ($automation_settings['reminder_overdue'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="reminder_overdue">3 dias após vencimento</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Mensagem Personalizada</h5>
                                    <textarea class="form-control" name="message_template" rows="5" 
                                              placeholder="Olá {nome}, sua mensalidade no valor de R$ {valor} vence em {dias} dias."><?php echo htmlspecialchars($automation_settings['message_template'] ?? 'Olá {nome}, sua mensalidade no valor de R$ {valor} vence em {dias} dias.'); ?></textarea>
                                    <small class="text-muted">Use {nome}, {valor}, {dias} como variáveis</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Configurações Avançadas</h5>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="auto_suspend" name="auto_suspend" 
                                               <?php echo ($automation_settings['auto_suspend'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_suspend">Suspender automaticamente após</label>
                                    </div>
                                    <select class="form-select mb-3" name="suspend_days">
                                        <option value="7" <?php echo ($automation_settings['suspend_days'] ?? 7) == 7 ? 'selected' : ''; ?>>7 dias</option>
                                        <option value="15" <?php echo ($automation_settings['suspend_days'] ?? 7) == 15 ? 'selected' : ''; ?>>15 dias</option>
                                        <option value="30" <?php echo ($automation_settings['suspend_days'] ?? 7) == 30 ? 'selected' : ''; ?>>30 dias</option>
                                    </select>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="send_reports" name="send_reports" 
                                               <?php echo ($automation_settings['send_reports'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="send_reports">Enviar relatórios mensais</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5>Status da Automação</h5>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        A automação está <strong>ATIVA</strong> e processando cobranças.
                                    </div>
                                    <button type="button" class="btn btn-warning" onclick="testAutomation()">
                                        <i class="bi bi-play-circle"></i> Testar Automação
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Formulário de configuração de automação
document.getElementById('automationConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'save_automation_config';
    
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Configurações de automação salvas!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao salvar configurações', 'danger');
        console.error('Erro:', error);
    });
});

// Testar automação
function testAutomation() {
    showNotification('Testando automação...', 'info');
    fetch('api/automation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'test_automation'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Teste de automação executado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao testar automação', 'danger');
        console.error('Erro:', error);
    });
}
</script>
