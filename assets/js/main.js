
// Sistema SaaS - JavaScript Principal
let currentPage = 'dashboard';
let darkMode = localStorage.getItem('darkMode') === 'true';

// Inicializar aplicação
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema inicializado');
    initializeDarkMode();
    initializeTooltips();
    
    // Verificar se há uma página específica na URL
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page) {
        currentPage = page;
        updateActiveNavigation();
    }
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
    const mainTitle = document.getElementById('page-title');
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
    
    // Atualizar URL sem recarregar a página
    const newUrl = window.location.pathname + '?page=' + page;
    window.history.pushState({page: page}, '', newUrl);
    
    // Carregar conteúdo da página
    fetch(`pages/${page}.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            if (content) {
                content.innerHTML = html;
            }
            updateActiveNavigation();
            
            // Executar scripts específicos da página se existirem
            const scripts = content.querySelectorAll('script');
            scripts.forEach(script => {
                if (script.innerHTML) {
                    try {
                        eval(script.innerHTML);
                    } catch (error) {
                        console.error('Erro ao executar script:', error);
                    }
                }
            });
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
                            <br><button class="btn btn-sm btn-primary mt-2" onclick="loadPage('${page}')">Tentar Novamente</button>
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

// Funções utilitárias
function initializeTooltips() {
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
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
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

// Lidar com navegação do navegador
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.page) {
        currentPage = event.state.page;
        loadPage(currentPage);
    }
});

// Exportar funções globais
window.loadPage = loadPage;
window.toggleDarkMode = toggleDarkMode;
window.showNotification = showNotification;
