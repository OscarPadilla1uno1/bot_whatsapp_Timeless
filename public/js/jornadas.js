// jornadas.js

// Cargar conductores con disponibilidad
async function loadDriversWithAvailability() {
    try {
        console.log('Cargando conductores...');
        const response = await fetch('/admin/drivers/with-availability');
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Respuesta del servidor:', result);

        // Intentar extraer el array de conductores de diferentes formas
        let drivers = [];

        // Si la respuesta es un array, usarlo directamente
        if (Array.isArray(result)) {
            drivers = result;
        }
        // Si la respuesta es un objeto con una propiedad 'drivers' que es un array
        else if (result && Array.isArray(result.drivers)) {
            drivers = result.drivers;
        }
        // Si la respuesta es un objeto con una propiedad 'data' que es un array
        else if (result && Array.isArray(result.data)) {
            drivers = result.data;
        }
        // Si la respuesta es un objeto y tiene 'success' y 'drivers'
        else if (result && result.success && Array.isArray(result.drivers)) {
            drivers = result.drivers;
        }
        // Si no, intentar obtener cualquier propiedad que sea un array
        else if (result && typeof result === 'object') {
            // Buscar la primera propiedad que sea un array
            for (let key in result) {
                if (Array.isArray(result[key])) {
                    drivers = result[key];
                    break;
                }
            }
        }

        // Si después de todo no tenemos un array, lanzar error
        if (!Array.isArray(drivers)) {
            console.error('No se pudo extraer un array de conductores de la respuesta:', result);
            throw new Error('Formato de respuesta no válido. Se esperaba un array de conductores.');
        }

        console.log('Drivers a renderizar:', drivers);
        renderDriversList(drivers);
        
    } catch (error) {
        console.error('Error loading drivers:', error);
        const container = document.getElementById('drivers-list');
        if (container) {
            container.innerHTML = '<div class="error">Error cargando conductores: ' + error.message + '</div>';
        }
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