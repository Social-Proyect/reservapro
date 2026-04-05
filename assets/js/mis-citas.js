// Funciones para gestión de citas del cliente

function cancelarCita(citaId, codigo) {
    const modal = document.getElementById('cancel-modal');
    document.getElementById('cancel-cita-id').value = citaId;
    modal.classList.add('active');
}

function cerrarModal() {
    const modal = document.getElementById('cancel-modal');
    modal.classList.remove('active');
    document.getElementById('cancel-form').reset();
}

async function confirmarCancelacion() {
    const citaId = document.getElementById('cancel-cita-id').value;
    const motivo = document.getElementById('motivo').value;

    try {
        const formData = new FormData();
        formData.append('cita_id', citaId);
        formData.append('motivo', motivo);

        const response = await fetch('api/cancel-booking.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert('Cita cancelada exitosamente');
            location.reload();
        } else {
            alert(data.message || 'Error al cancelar la cita');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la cancelación');
    }
}

function reagendarCita(citaId) {
    // Redirigir a la página de reserva con parámetro para reagendar
    window.location.href = `index.php?reagendar=${citaId}`;
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cancel-modal');
    
    window.onclick = function(event) {
        if (event.target === modal) {
            cerrarModal();
        }
    };
});
