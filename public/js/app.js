/**
 * FrigoTIC - Aplicación JavaScript Principal
 * MJCRSoftware - 2024
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los componentes
    initDropdowns();
    initModals();
    initTabs();
    initAlerts();
    initImagePreview();
    initFormValidation();
});

/**
 * Inicializar dropdowns del header
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Cerrar otros dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });
                
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function() {
        dropdowns.forEach(d => d.classList.remove('active'));
    });
}

/**
 * Sistema de Modales
 */
function initModals() {
    // Cerrar modal al hacer clic en overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

// Funciones globales para modales
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

/**
 * Sistema de Ayuda
 */
function openHelp() {
    document.getElementById('helpModal').classList.add('active');
}

function closeHelp() {
    document.getElementById('helpModal').classList.remove('active');
}

/**
 * Inicializar pestañas (tabs)
 */
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            const container = this.closest('.tabs-container');
            
            // Desactivar todas las pestañas
            container.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            container.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Activar pestaña seleccionada
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
}

/**
 * Auto-cerrar alertas
 */
function initAlerts() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Vista previa de imágenes
 */
function initImagePreview() {
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.closest('.form-group')?.querySelector('.image-preview');
            if (!preview) return;
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Vista previa">`;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

/**
 * Validación de formularios
 */
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                field.classList.remove('is-invalid');
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showNotification('Por favor, completa todos los campos requeridos', 'warning');
            }
        });
    });
}

/**
 * Sistema de notificaciones toast
 */
function showNotification(message, type = 'info') {
    const container = document.getElementById('notifications') || createNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notifications';
    container.className = 'notifications-container';
    document.body.appendChild(container);
    return container;
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        danger: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Confirmación de acciones
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Formatear números como moneda
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

/**
 * Formatear fechas
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(date);
}

/**
 * Enviar formulario vía AJAX
 */
async function submitFormAjax(form, onSuccess, onError) {
    const formData = new FormData(form);
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            onSuccess(data);
        } else {
            onError(data.message || 'Error desconocido');
        }
    } catch (error) {
        onError('Error de conexión');
    }
}

/**
 * Debounce para búsquedas en tiempo real
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Actualizar stock (para usuario)
 */
function apuntarConsumo(productoId, productoNombre, precio) {
    document.getElementById('consumo_producto_id').value = productoId;
    document.getElementById('consumo_producto_nombre').textContent = productoNombre;
    document.getElementById('consumo_precio').textContent = formatCurrency(precio);
    document.getElementById('consumo_cantidad').value = 1;
    updateConsumoTotal(precio);
    openModal('consumoModal');
}

function updateConsumoTotal(precioUnitario) {
    const cantidad = parseInt(document.getElementById('consumo_cantidad')?.value) || 1;
    const total = cantidad * precioUnitario;
    const totalElement = document.getElementById('consumo_total');
    if (totalElement) {
        totalElement.textContent = formatCurrency(total);
    }
}

/**
 * Toggle mostrar/ocultar contraseña
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling?.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        if (icon) icon.className = 'fas fa-eye';
    }
}

console.log('FrigoTIC v1.0.0 - MJCRSoftware');
