// Obtener empresa_id de la URL
function getEmpresaId() {
    const params = new URLSearchParams(window.location.search);
    return params.get('empresa_id') || '';
}

// Estado global de la reserva
const bookingState = {
    currentStep: 1,
    serviceId: null,
    serviceName: null,
    serviceDuration: null,
    servicePrice: null,
    employeeId: null,
    employeeName: null,
    selectedDate: null,
    selectedTime: null
};

let calendar = null;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeBooking();
});

function initializeBooking() {
    // Event listeners para los botones de navegación
    document.getElementById('btn-next').addEventListener('click', nextStep);
    document.getElementById('btn-prev').addEventListener('click', prevStep);
    document.getElementById('btn-confirm').addEventListener('click', confirmBooking);

    // Event listeners para selección de servicios
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', function() {
            selectService(this);
        });
    });

    // Inicializar calendario
    const calendarContainer = document.getElementById('calendar');
    calendar = new Calendar(calendarContainer, onDateSelected);
}

// Seleccionar servicio
function selectService(card) {
    // Remover selección anterior
    document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
    
    // Marcar como seleccionado
    card.classList.add('selected');
    
    // Guardar información del servicio
    bookingState.serviceId = card.getAttribute('data-service-id');
    bookingState.serviceName = card.querySelector('.service-name').textContent;
    bookingState.serviceDuration = card.getAttribute('data-duration');
    bookingState.servicePrice = card.getAttribute('data-price');
    
    // Ir automáticamente al siguiente paso
    nextStep();
}

// Cargar empleados disponibles para el servicio seleccionado
async function loadEmployees() {
    if (!bookingState.serviceId) return;

    const container = document.getElementById('employees-container');
    container.innerHTML = '<div class="spinner"></div>';

    try {
        const empresaId = getEmpresaId();
        const response = await fetch(`api/get-employees.php?servicio_id=${bookingState.serviceId}&empresa_id=${empresaId}`);
        const data = await response.json();

        if (data.success) {
            let html = '';
            
            // Agregar opción "Cualquier empleado"
            html += `
                <div class="employee-card" data-employee-id="0">
                    <div class="employee-photo" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                        👤
                    </div>
                    <h3 class="employee-name">Cualquier Empleado</h3>
                    <p class="employee-specialty">Primero disponible</p>
                    <p class="employee-description">Reserva con el primer especialista disponible</p>
                </div>
            `;

            // Agregar empleados específicos
            data.empleados.forEach(emp => {
                const photoUrl = emp.foto || 'assets/img/default-avatar.png';
                html += `
                    <div class="employee-card" data-employee-id="${emp.id}">
                        <img src="${photoUrl}" alt="${emp.nombre}" class="employee-photo" onerror="this.src='assets/img/default-avatar.png'">
                        <h3 class="employee-name">${emp.nombre} ${emp.apellido}</h3>
                        <p class="employee-specialty">${emp.especialidad || ''}</p>
                        <p class="employee-description">${emp.descripcion || ''}</p>
                    </div>
                `;
            });

            container.innerHTML = html;

            // Event listeners para selección de empleado
            document.querySelectorAll('.employee-card').forEach(card => {
                card.addEventListener('click', function() {
                    selectEmployee(this);
                });
            });
        }
    } catch (error) {
        container.innerHTML = '<p class="text-muted">Error al cargar empleados</p>';
        console.error('Error:', error);
    }
}

// Seleccionar empleado
function selectEmployee(card) {
    // Remover selección anterior
    document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('selected'));
    
    // Marcar como seleccionado
    card.classList.add('selected');
    
    // Guardar información del empleado
    bookingState.employeeId = card.getAttribute('data-employee-id');
    bookingState.employeeName = card.querySelector('.employee-name').textContent;
    
    // Ir automáticamente al siguiente paso
    nextStep();
}

// Cargar fechas disponibles
async function loadAvailableDates() {
    if (!bookingState.serviceId) return;

    try {
        const empresaId = getEmpresaId();
        const url = `api/get-available-dates.php?servicio_id=${bookingState.serviceId}&empresa_id=${empresaId}` +
                (bookingState.employeeId && bookingState.employeeId !== '0' ? `&empleado_id=${bookingState.employeeId}` : '');
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            calendar.setAvailableDates(data.fechas);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Callback cuando se selecciona una fecha
function onDateSelected(date) {
    bookingState.selectedDate = date;
    loadAvailableTimes(date);
}

// Cargar horarios disponibles para una fecha
async function loadAvailableTimes(date) {
    const container = document.getElementById('available-times');
    container.innerHTML = '<div class="spinner"></div>';

    try {
        const empresaId = getEmpresaId();
        const url = `api/get-available-times.php?fecha=${date}&servicio_id=${bookingState.serviceId}&empresa_id=${empresaId}` +
                (bookingState.employeeId && bookingState.employeeId !== '0' ? `&empleado_id=${bookingState.employeeId}` : '');
        
        const response = await fetch(url);
        const data = await response.json();

        if (data.success && data.horarios.length > 0) {
            let html = '';
            data.horarios.forEach(hora => {
                html += `<div class="time-slot" data-time="${hora}">${hora}</div>`;
            });
            container.innerHTML = html;

            // Event listeners para selección de hora
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.addEventListener('click', function() {
                    selectTime(this);
                });
            });
        } else {
            container.innerHTML = '<p class="text-muted">No hay horarios disponibles para esta fecha</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-muted">Error al cargar horarios</p>';
        console.error('Error:', error);
    }
}

// Seleccionar hora
function selectTime(slot) {
    // Remover selección anterior
    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
    
    // Marcar como seleccionado
    slot.classList.add('selected');
    
    // Guardar hora seleccionada
    bookingState.selectedTime = slot.getAttribute('data-time');
    
    // Ir automáticamente al siguiente paso si hay fecha y hora
    if (bookingState.selectedDate && bookingState.selectedTime) {
        nextStep();
    }
}

// Actualizar resumen de la reserva
function updateSummary() {
    document.getElementById('summary-service').textContent = bookingState.serviceName;
    document.getElementById('summary-employee').textContent = bookingState.employeeName;
    
    const fechaFormateada = new Date(bookingState.selectedDate + 'T00:00:00').toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('summary-datetime').textContent = `${fechaFormateada} a las ${bookingState.selectedTime}`;
    document.getElementById('summary-duration').textContent = `${bookingState.serviceDuration} minutos`;
    // Usar el símbolo de moneda de la empresa
    var moneda = window.empresaMoneda || '$';
    document.getElementById('summary-price').textContent = `${moneda}${parseFloat(bookingState.servicePrice).toFixed(2)}`;
}

// Navegar al siguiente paso
function nextStep() {
    // Validar paso actual
    if (!validateCurrentStep()) return;

    bookingState.currentStep++;

    // Realizar acciones específicas para cada paso
    if (bookingState.currentStep === 2) {
        loadEmployees();
    } else if (bookingState.currentStep === 3) {
        loadAvailableDates();
    } else if (bookingState.currentStep === 4) {
        updateSummary();
    }

    updateStepDisplay();
}

// Navegar al paso anterior
function prevStep() {
    if (bookingState.currentStep > 1) {
        bookingState.currentStep--;
        updateStepDisplay();
    }
}

// Validar paso actual
function validateCurrentStep() {
    switch (bookingState.currentStep) {
        case 1:
            if (!bookingState.serviceId) {
                alert('Por favor selecciona un servicio');
                return false;
            }
            break;
        case 2:
            if (!bookingState.employeeId) {
                alert('Por favor selecciona un empleado');
                return false;
            }
            break;
        case 3:
            if (!bookingState.selectedDate || !bookingState.selectedTime) {
                alert('Por favor selecciona fecha y hora');
                return false;
            }
            break;
    }
    return true;
}

// Actualizar visualización de pasos
function updateStepDisplay() {
    // Actualizar indicadores de progreso
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        const stepNumber = index + 1;
        if (stepNumber < bookingState.currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNumber === bookingState.currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });

    // Mostrar/ocultar contenido de pasos
    document.querySelectorAll('.step-content').forEach((content, index) => {
        if (index + 1 === bookingState.currentStep) {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
        }
    });

    // Actualizar botones
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnConfirm = document.getElementById('btn-confirm');

    if (bookingState.currentStep === 1) {
        btnPrev.style.display = 'none';
    } else {
        btnPrev.style.display = 'inline-block';
    }

    if (bookingState.currentStep === 4) {
        btnNext.style.display = 'none';
        btnConfirm.style.display = 'inline-block';
    } else {
        btnNext.style.display = 'inline-block';
        btnConfirm.style.display = 'none';
    }
}

// Confirmar reserva
async function confirmBooking() {
    const form = document.getElementById('booking-form');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    // Agregar datos de la reserva
    formData.append('servicio_id', bookingState.serviceId);
    formData.append('empleado_id', bookingState.employeeId);
    formData.append('fecha', bookingState.selectedDate);
    formData.append('hora', bookingState.selectedTime);
    formData.append('duracion', bookingState.serviceDuration);
    formData.append('precio', bookingState.servicePrice);
    // Asegurar que empresa_id se envía
    formData.append('empresa_id', getEmpresaId());
    formData.append('password', document.getElementById('customer-password').value);

    // Deshabilitar botón
    const btnConfirm = document.getElementById('btn-confirm');
    btnConfirm.disabled = true;
    btnConfirm.textContent = 'Procesando...';

    try {
        const response = await fetch('api/create-booking.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Mostrar modal de éxito
            showSuccessModal(data.codigo_confirmacion);
        } else {
            alert(data.message || 'Error al crear la reserva');
            btnConfirm.disabled = false;
            btnConfirm.textContent = '✓ Confirmar Reserva';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar la reserva');
        btnConfirm.disabled = false;
        btnConfirm.textContent = '✓ Confirmar Reserva';
    }
}

// Mostrar modal de éxito
function showSuccessModal(confirmationCode) {
    const modal = document.getElementById('success-modal');
    document.getElementById('confirmation-code').textContent = confirmationCode;
    modal.classList.add('active');
}
