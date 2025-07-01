
-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS saas_clientes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saas_clientes;

-- Tabela de usuários do sistema
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de clientes (multi-tenant por user_id)
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    valor_cobranca DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('ativo', 'pendente', 'pago', 'vencido') DEFAULT 'pendente',
    payment_link VARCHAR(255),
    payment_id VARCHAR(100),
    qr_code TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento)
);

-- Tabela de configurações do Mercado Pago
CREATE TABLE mercadopago_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    access_token VARCHAR(255) NOT NULL,
    valor_base DECIMAL(10,2) DEFAULT 0.00,
    desconto_3_meses DECIMAL(5,2) DEFAULT 0.00,
    desconto_6_meses DECIMAL(5,2) DEFAULT 0.00,
    webhook_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de configurações do WhatsApp (Evolution V2)
CREATE TABLE whatsapp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    instance_name VARCHAR(50) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    base_url VARCHAR(255) DEFAULT 'https://evov2.duckdns.org/',
    phone_number VARCHAR(20),
    status ENUM('disconnected', 'connecting', 'connected', 'error') DEFAULT 'disconnected',
    qr_code TEXT,
    session_data TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de histórico de pagamentos
CREATE TABLE payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    payment_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50),
    transaction_data JSON,
    paid_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_user_client (user_id, client_id),
    INDEX idx_payment_id (payment_id)
);

-- Tabela de mensagens WhatsApp enviadas
CREATE TABLE whatsapp_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    message_type ENUM('cobranca', 'lembrete', 'confirmacao') NOT NULL,
    message_content TEXT NOT NULL,
    status ENUM('enviado', 'entregue', 'lido', 'erro') DEFAULT 'enviado',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_user_client (user_id, client_id),
    INDEX idx_sent_date (sent_at)
);

-- Tabela de configurações do sistema por usuário
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    dark_mode BOOLEAN DEFAULT FALSE,
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    notification_email BOOLEAN DEFAULT TRUE,
    notification_whatsapp BOOLEAN DEFAULT TRUE,
    auto_cobranca BOOLEAN DEFAULT TRUE,
    dias_antecedencia_cobranca INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO users (name, email, password, is_active, email_verified) 
VALUES ('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE);

-- Inserir configurações padrão para o admin
INSERT INTO user_settings (user_id) VALUES (1);
