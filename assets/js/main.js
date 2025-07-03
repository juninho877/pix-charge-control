
// Main JavaScript file for the SaaS system

// Global variables
let currentPage = 'dashboard';
let darkMode = localStorage.getItem('darkMode') === 'true';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando aplicação...');
    initializeDarkMode();
    initializeNavigation();
    initializeTooltips();
});

// Dark Mode Functions
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
    
    // Save to server
    saveUserPreference('dark_mode', darkMode);
}

// Navigation Functions
function initializeNavigation() {
    console.log('Inicializando navegação...');
    updateActiveNavigation();
}

function updateActiveNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        const onclick = link.getAttribute('onclick');
        if (onclick && onclick.includes(`'${currentPage}'`)) {
            link.classList.add('active');
        }
    });
}

function loadPage(page) {
    console.log('Carregando página:', page);
    currentPage = page;
    
    // Show loading
    const content = document.getElementById('dashboard-content');
    if (content) {
        content.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    }
    
    // Update page title
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
    
    // Load page content
    fetch(`pages/${page}.php`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: Página não encontrada`);
            }
            return response.text();
        })
        .then(html => {
            console.log('Página carregada com sucesso');
            if (content) {
                content.innerHTML = html;
            }
            updateActiveNavigation();
            
            // Initialize page-specific scripts
            initializePageScripts(page);
        })
        .catch(error => {
            console.error('Erro ao carregar página:', error);
            if (content) {
                content.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        Erro ao carregar a página "${page}". ${error.message}
                    </div>
                `;
            }
        });
}

// Initialize page-specific scripts
function initializePageScripts(page) {
    console.log('Inicializando scripts da página:', page);
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

// Client functions
function initializeClients() {
    console.log('Clientes inicializado');
    // Initialize phone number formatting
    const phoneInputs = document.querySelectorAll('input[name="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', formatPhoneNumber);
    });
}

function formatPhoneNumber(e) {
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
}

// Settings functions
function initializeSettings() {
    console.log('Configurações inicializadas');
}

// WhatsApp functions
function initializeWhatsApp() {
    console.log('WhatsApp inicializado');
}

// Utility functions
function initializeTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// API Functions
function saveUserPreference(key, value) {
    console.log('Salvando preferência:', key, value);
    fetch('api/settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'save_preference',
            key: key,
            value: value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erro ao salvar preferência:', data.message);
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
    });
}

// Notification functions
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Export functions for global use
window.loadPage = loadPage;
window.toggleDarkMode = toggleDarkMode;
window.showNotification = showNotification;
