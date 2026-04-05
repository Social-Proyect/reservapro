// Funciones generales del panel de administración

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
}

function verCita(citaId) {
    // Abrir modal con detalles de la cita
    window.location.href = `calendario.php?ver=${citaId}`;
}

function editarCita(citaId) {
    // Abrir modal para editar cita
    window.location.href = `calendario.php?editar=${citaId}`;
}

function bloquearHorario() {
    // Abrir modal para bloquear horario
    window.location.href = `calendario.php?bloquear=1`;
}

// Confirmación de eliminación
function confirmarEliminar(mensaje = '¿Estás seguro de que deseas eliminar este elemento?') {
    return confirm(mensaje);
}

// Formatear fecha
function formatearFecha(fecha) {
    const f = new Date(fecha);
    return f.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Formatear hora
function formatearHora(fecha) {
    const f = new Date(fecha);
    return f.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Mostrar notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notif = document.createElement('div');
    notif.className = `alert alert-${tipo}`;
    notif.textContent = mensaje;
    notif.style.position = 'fixed';
    notif.style.top = '20px';
    notif.style.right = '20px';
    notif.style.zIndex = '9999';
    notif.style.minWidth = '300px';
    notif.style.animation = 'slideIn 0.3s ease';
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Agregar estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
