
// Main JavaScript file for the SaaS system

// Global variables
let currentPage = 'dashboard';
let darkMode = localStorage.getItem('darkMode') === 'true';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeDarkMode();
    initializeNavigation();
    initializeTooltips();
    initializeModals();
    
    // Load initial page
    loadPage('dashboard');
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
    // Add active class to current page
    updateActiveNavigation();
    
    // Handle sidebar navigation
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('onclick').match(/loadPage\('(.+)'\)/);
            if (page) {
                loadPage(page[1]);
            }
        });
    });
}

function updateActiveNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        const page = link.getAttribute('onclick');
        if (page && page.includes(currentPage)) {
            link.classList.add('active');
        }
    });
}

function loadPage(page) {
    currentPage = page;
    
    // Show loading
    const content = document.getElementById('dashboard-content');
    if (content) {
        content.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    }
    
    // Load page content
    fetch(`pages/${page}.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Página não encontrada');
            }
            return response.text();
        })
        .then(html => {
            if (content) {
                content.innerHTML = html;
                content.classList.add('fade-in');
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
                        Erro ao carregar a página. Tente novamente.
                    </div>
                `;
            }
        });
}

// Initialize page-specific scripts
function initializePageScripts(page) {
    switch (page) {
        case 'dashboard':
            initializeDashboard();
            break;
        case 'clients':
            initializeClients();
            break;
        case 'settings':
            initializeSettings();
            break;
        case 'payments':
            initializePayments();
            break;
        case 'whatsapp':
            initializeWhatsApp();
            break;
        case 'reports':
            initializeReports();
            break;
    }
}

// Dashboard functions
function initializeDashboard() {
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    
    // Auto-refresh dashboard every 5 minutes
    setInterval(() => {
        if (currentPage === 'dashboard') {
            refreshDashboard();
        }
    }, 300000);
}

function initializeCharts() {
    // Chart initialization is handled in dashboard.php
}

function refreshDashboard() {
    loadPage('dashboard');
}

// Client functions
function initializeClients() {
    // Initialize phone number formatting
    const phoneInputs = document.querySelectorAll('input[name="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', formatPhoneNumber);
    });
    
    // Initialize date inputs with today's date
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = new Date().toISOString().split('T')[0];
        }
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
    // Initialize form validations
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Payment functions
function initializePayments() {
    // Initialize payment-related functionality
}

// WhatsApp functions
function initializeWhatsApp() {
    // Initialize WhatsApp-related functionality
}

// Reports functions
function initializeReports() {
    // Initialize reports-related functionality
}

// Utility functions
function initializeTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initializeModals() {
    // Initialize Bootstrap modals
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            // Reset forms when modal is closed
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => {
                form.reset();
                form.classList.remove('was-validated');
            });
        });
    });
}

// API Functions
function saveUserPreference(key, value) {
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

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    showNotification('Ocorreu um erro inesperado. Tente recarregar a página.', 'danger');
});

// AJAX error handling
function handleAjaxError(error) {
    console.error('AJAX Error:', error);
    showNotification('Erro de conexão. Verifique sua internet e tente novamente.', 'warning');
}

// Form utilities
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

// Date utilities
function formatDate(date, format = 'dd/mm/yyyy') {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    switch (format) {
        case 'dd/mm/yyyy':
            return `${day}/${month}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return d.toLocaleDateString('pt-BR');
    }
}

// Currency utilities
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function parseCurrency(value) {
    return parseFloat(value.replace(/[^\d,-]/g, '').replace(',', '.'));
}

// Phone utilities
function formatPhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    
    if (cleaned.length === 11) {
        return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    
    return phone;
}

function getWhatsAppUrl(phone, message = '') {
    const cleaned = phone.replace(/\D/g, '');
    const formattedPhone = cleaned.startsWith('55') ? cleaned : '55' + cleaned;
    
    if (message) {
        return `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
    }
    
    return `https://wa.me/${formattedPhone}`;
}

// Export functions for global use
window.loadPage = loadPage;
window.toggleDarkMode = toggleDarkMode;
window.showNotification = showNotification;
window.formatCurrency = formatCurrency;
window.formatPhone = formatPhone;
window.getWhatsAppUrl = getWhatsAppUrl;
