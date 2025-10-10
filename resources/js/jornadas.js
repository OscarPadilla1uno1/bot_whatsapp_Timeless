// jornadas.js

// Cargar conductores con disponibilidad
async function loadDriversWithAvailability() {
    try {
        const response = await fetch('/admin/drivers/with-availability');
        const drivers = await response.json();
        renderDriversList(drivers);
    } catch (error) {
        console.error('Error loading drivers:', error);
    }
}

function renderDriversList(drivers) {
    const container = document.getElementById('drivers-list');
    
    if (!container) {
        console.error('Elemento drivers-list no encontrado');
        return;
    }
    
    container.innerHTML = drivers.map(driver => `
        <div class="driver-card ${driver.disponible_jornadas ? '' : 'disabled'} ${driver.jornadas_activas > 0 ? 'active-shift' : ''}">
            <div class="driver-header">
                <div class="driver-name">${driver.name}</div>
                <label class="toggle-switch">
                    <input type="checkbox" 
                           ${driver.disponible_jornadas ? 'checked' : ''} 
                           onchange="toggleDriverAvailability(${driver.id}, this.checked)"
                           ${driver.jornadas_activas > 0 ? 'disabled' : ''}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="driver-info">
                <small>${driver.email}</small>
            </div>
            <div style="margin-top: 10px;">
                <span class="driver-status ${driver.disponible_jornadas ? 'status-available' : 'status-unavailable'}">
                    ${driver.disponible_jornadas ? 'Disponible' : 'No disponible'}
                </span>
                ${driver.jornadas_activas > 0 ? `
                    <span class="driver-status status-active">
                        ${driver.jornadas_activas} jornada(s) activa
                    </span>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Toggle disponibilidad de conductor
async function toggleDriverAvailability(driverId, disponible) {
    try {
        const response = await fetch('/admin/drivers/toggle-availability', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                driver_id: driverId,
                disponible: disponible
            })
        });

        const data = await response.json();

        if (data.success) {
            loadDriversWithAvailability(); // Recargar lista
            alert(data.message);
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error actualizando disponibilidad');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    loadDriversWithAvailability();

    // Actualizar cada 30 segundos
    setInterval(loadDriversWithAvailability, 30000);
});