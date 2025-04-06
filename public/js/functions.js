function renderizarTabla(platillos) {
    const tabla = document.getElementById('body-tabla-platillos-menu');
    tabla.innerHTML = '';

    if (platillos.length > 0) {
        platillos.forEach((platillo, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">${index + 1}</td>
                <td class="px-6 py-4 whitespace-nowrap">${platillo.nombre}</td>
                <td class="px-6 py-4 whitespace-nowrap">${platillo.cantidad_disponible}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <button onclick="eliminarPlatillo(${platillo.id})"
                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                        Eliminar
                    </button>
                </td>
            `;
            tabla.appendChild(tr);
        });
    } else {
        tabla.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay platillos para esta fecha.</td>
            </tr>
        `;
    }
}

function eliminarPlatillo(platilloId) {
    const fecha = document.getElementById('fecha').value;

    Swal.fire({
        title: '¿Estás seguro?',
        text: "Este platillo será eliminado del menú.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.routes.eliminar, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ platillo_id: platilloId, fecha: fecha })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Platillo eliminado',
                            text: 'El platillo fue eliminado del menú correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        cargarMenuPorFecha(fecha);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al eliminar',
                            text: 'El platillo no se pudo eliminar',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al eliminar',
                        text: 'El platillo no se pudo eliminar, error del servidor',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
        }
    });
}

function cargarMenuPorFecha(fecha) {
    fetch(`${window.routes.porFecha}?fecha=${fecha}`, {
        headers: {
            "X-CSRF-TOKEN": window.csrfToken,
            "Accept": "application/json"
        }
    })
        .then(res => res.json())
        .then(data => renderizarTabla(data.platillos))
        .catch(err => console.error("Error al cargar menú:", err));
}

document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tabla-platillos-menu');

    tabla?.addEventListener('click', function (e) {
        if (e.target.classList.contains('cantidad-text')) {
            const cell = e.target.closest('td');
            const span = cell.querySelector('.cantidad-text');
            const input = cell.querySelector('.cantidad-input');
            const btn = cell.querySelector('.guardar-cantidad');

            span.classList.add('hidden');
            input.classList.remove('hidden');
            btn.classList.remove('hidden');
            input.focus();
        }

        if (e.target.classList.contains('guardar-cantidad')) {
            const cell = e.target.closest('td');
            const input = cell.querySelector('.cantidad-input');
            const span = cell.querySelector('.cantidad-text');
            const btn = cell.querySelector('.guardar-cantidad');

            const nuevaCantidad = input.value.trim();
            const platilloId = input.dataset.id;
            const fecha = input.dataset.fecha;

            if (!nuevaCantidad || isNaN(nuevaCantidad) || parseInt(nuevaCantidad) <= 0) {
                alert("Ingresa una cantidad válida mayor a 0.");
                return;
            }

            fetch(window.routes.actualizarCantidad, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    platillo_id: platilloId,
                    cantidad: nuevaCantidad,
                    fecha: fecha
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        span.textContent = nuevaCantidad;
                        span.classList.remove('hidden');
                        input.classList.add('hidden');
                        btn.classList.add('hidden');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar',
                            text: 'El platillo no se pudo actualizar',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al actualizar',
                        text: 'El platillo no se pudo actualizar, error del servidor',
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
        }
    });
});
