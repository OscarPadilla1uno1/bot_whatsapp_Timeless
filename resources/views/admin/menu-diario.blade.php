<x-app-layout>
    <div class="max-w-7xl mx-auto p-6">
        <h2 class="text-2xl font-semibold mb-6 text-gray-800">Administrar Menú Diario</h2>

        <!-- Selección de Fecha -->
        <div class="mb-6">
            <label for="fecha" class="block text-sm font-medium text-gray-700">Selecciona una fecha:</label>
            <input type="date" id="fecha"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <!-- Card: Tabla de platillos -->
        <div class="bg-white shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Platillos en el Menú</h4>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="tabla-platillos-menu">
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    #</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre del Platillo</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cantidad Máxima</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="body-tabla-platillos-menu">
                            @forelse ($platillosEnMenu as $index => $platillo)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $platillo->nombre }}</td>
                                    <td class="px-4 py-2 relative">
                                        <span class="cantidad-text">{{ $platillo->cantidad_disponible }}</span>
                                        <input type="number" class="cantidad-input hidden w-20 border rounded px-1"
                                            data-id="{{ $platillo->id }}"
                                            data-fecha="{{ request('fecha') ?? date('Y-m-d') }}"
                                            value="{{ $platillo->cantidad_disponible }}">
                                        <button
                                            class="guardar-cantidad hidden absolute right-4 top-1/2 -translate-y-1/2 text-green-600 font-semibold text-sm"
                                            title="Guardar">
                                            Guardar
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button onclick="eliminarPlatillo({{ $platillo->id }})"
                                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay platillos agregados
                                        aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card: Formulario para agregar platillo -->
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Agregar Platillo al Menú</h4>

                <form id="agregar-platillo-form" class="space-y-4">
                    @csrf

                    <div>
                        <label for="platillo" class="block text-sm font-medium text-gray-700">Selecciona un
                            Platillo:</label>
                        <select name="platillo_id" id="platillo"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach ($todosLosPlatillos as $platillo)
                                <option value="{{ $platillo->id }}">{{ $platillo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad Máxima:</label>
                        <input type="number" name="cantidad" id="cantidad" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 transition">
                            Agregar al Menú
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('fecha').value = today;
        });

        document.getElementById('fecha').addEventListener('change', function () {
            const fecha = this.value;
            const token = document.querySelector('input[name="_token"]').value;

            fetch(`{{ route('admin.menu.fecha') }}?fecha=${fecha}`, {
                headers: { "X-CSRF-TOKEN": token, "Accept": "application/json" }
            })
                .then(res => res.json())
                .then(data => renderizarTabla(data.platillos))
                .catch(err => console.error("Error al cargar menú:", err));
        });

        document.getElementById('agregar-platillo-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const platilloId = document.getElementById('platillo').value;
            const cantidad = document.getElementById('cantidad').value;
            const fecha = document.getElementById('fecha').value;
            const token = document.querySelector('input[name="_token"]').value;

            fetch("{{ route('admin.menu.agregar') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ platillo_id: platilloId, cantidad: cantidad, fecha: fecha })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Platillo agregado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        cargarMenuPorFecha(fecha);
                    }
                })
                .catch(err => console.error("Error al agregar:", err));
        });

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
            const token = document.querySelector('input[name="_token"]').value;

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
                    fetch("{{ route('admin.menu.eliminar') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": token,
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
                        .catch(err => {
                            console.error("Error al eliminar:", err);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al eliminar',
                                text: 'El platillo no se pudo eliminar',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        });
                }
            });

        }

        function cargarMenuPorFecha(fecha) {
            const token = document.querySelector('input[name="_token"]').value;

            fetch(`{{ route('admin.menu.fecha') }}?fecha=${fecha}`, {
                headers: { "X-CSRF-TOKEN": token, "Accept": "application/json" }
            })
                .then(res => res.json())
                .then(data => renderizarTabla(data.platillos))
                .catch(err => console.error("Error al cargar menú:", err));
        }
    </script>
    <script>
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
                    const token = document.querySelector('input[name="_token"]').value;

                    if (!nuevaCantidad || isNaN(nuevaCantidad) || parseInt(nuevaCantidad) <= 0) {
                        alert("Ingresa una cantidad válida mayor a 0.");
                        return;
                    }

                    fetch("{{ route('admin.menu.actualizarCantidad') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": token,
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
                        .catch(err => {
                            console.error(err);
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
    </script>

</x-app-layout>