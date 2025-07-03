
// Sistema SaaS - JavaScript Principal

// Variáveis globais
let currentPage = 'dashboard';
let darkMode = localStorage.getItem('darkMode') === 'true';

// Inicializar aplicação
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema inicializado');
    initializeDarkMode();
    initializeTooltips();
});

// Funções de Dark Mode
function initializeDarkMode() {
    const body = document.body;
    const toggle = document.getElementById('darkModeToggle');
    
    if (darkMode) {
        body.setAttribute('data-bs-theme', 'dark');
        if (toggle) {
            toggle.innerHTML = '<i class="bi bi-sun"></i>';
        }
    } else {
        body.removeAttribute('data-bs-theme');
        if (toggle) {
            toggle.innerHTML = '<i class="bi bi-moon"></i>';
        }
    }
}

function toggleDarkMode() {
    darkMode = !darkMode;
    localStorage.setItem('darkMode', darkMode);
    
    const body = document.body;
    const toggle = document.getElementById('darkModeToggle');
    
    if (darkMode) {
        body.setAttribute('data-bs-theme', 'dark');
        if (toggle) {
            toggle.innerHTML = '<i class="bi bi-sun"></i>';
        }
    } else {
        body.removeAttribute('data-bs-theme');
        if (toggle) {
            toggle.innerHTML = '<i class="bi bi-moon"></i>';
        }
    }
    
    // Salvar no servidor
    fetch('api/settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'save_preference',
            dark_mode: darkMode
        })
    }).catch(error => console.error('Erro ao salvar preferência:', error));
}

// Funções de Navegação
function loadPage(page) {
    console.log('Carregando página:', page);
    currentPage = page;
    
    // Mostrar loading
    const content = document.getElementById('dashboard-content');
    if (content) {
        content.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    }
    
    // Atualizar título da página
    const mainTitle = document.querySelector('main h1');
    if (mainTitle) {
        const titles = {
            'dashboard': 'Dashboard',
            'clients': 'Clientes',
            'payments': 'Pagamentos',
            'whatsapp': 'WhatsApp',
            'automation': 'Automação',
            'reports': 'Relatórios',
            'settings': 'Configurações',
            'profile': 'Perfil'
        };
        mainTitle.textContent = titles[page] || 'Dashboard';
    }
    
    // Carregar conteúdo da página
    fetch(`pages/${page}.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro ${response.status}: Página não encontrada`);
            }
            return response.text();
        })
        .then(html => {
            if (content) {
                content.innerHTML = html;
            }
            updateActiveNavigation();
            initializePageScripts(page);
        })
        .catch(error => {
            console.error('Erro ao carregar página:', error);
            if (content) {
                content.innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Erro!</strong> Não foi possível carregar a página "${page}".
                            <br><small>${error.message}</small>
                        </div>
                    </div>
                `;
            }
        });
}

function updateActiveNavigation() {
    // Atualizar links ativos na sidebar
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        const onclick = link.getAttribute('onclick');
        if (onclick && onclick.includes(`'${currentPage}'`)) {
            link.classList.add('active');
        }
    });
}

// Inicializar scripts específicos das páginas
function initializePageScripts(page) {
    switch (page) {
        case 'clients':
            initializeClients();
            break;
        case 'settings':
            initializeSettings();
            break;
        case 'whatsapp':
            initializeWhatsApp();
            break;
    }
}

// Funções específicas dos clientes
function initializeClients() {
    // Formatar números de telefone
    const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 2) {
                    value = value.replace(/(\d{0,2})/, '($1');
                } else if (value.length <= 6) {
                    value = value.replace(/(\d{2})(\d{0,4})/, '($1) $2');
                } else if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                }
            }
            e.target.value = value;
        });
    });
}

function initializeSettings() {
    console.log('Configurações inicializadas');
}

function initializeWhatsApp() {
    console.log('WhatsApp inicializado');
}

// Funções utilitárias
function initializeTooltips() {
    // Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Exportar funções globais
window.loadPage = loadPage;
window.toggleDarkMode = toggleDarkMode;
window.showNotification = showNotification;
