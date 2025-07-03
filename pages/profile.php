
<?php
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo '<div class="alert alert-danger">Erro de conexão com o banco de dados</div>';
    return;
}

$user_id = $_SESSION['user_id'] ?? 1;

// Buscar dados do usuário
$user_data = [];
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch() ?: [];
} catch (Exception $e) {
    error_log("Erro ao buscar usuário: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Meu Perfil</h6>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Nome Completo</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Telefone</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Empresa</label>
                                <input type="text" class="form-control" name="company" 
                                       value="<?php echo htmlspecialchars($user_data['company'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Salvar Alterações
                    </button>
                </form>
                
                <hr>
                
                <h6>Alterar Senha</h6>
                <form id="passwordForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Senha Atual</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Nova Senha</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label>Confirmar Senha</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key"></i> Alterar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Formulário de perfil
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.action = 'update_profile';
    
    fetch('api/profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Perfil atualizado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao salvar perfil', 'danger');
        console.error('Erro:', error);
    });
});

// Formulário de senha
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    if (data.new_password !== data.confirm_password) {
        showNotification('As senhas não coincidem!', 'danger');
        return;
    }
    
    data.action = 'change_password';
    
    fetch('api/profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Senha alterada com sucesso!', 'success');
            document.getElementById('passwordForm').reset();
        } else {
            showNotification('Erro: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showNotification('Erro ao alterar senha', 'danger');
        console.error('Erro:', error);
    });
});
</script>
