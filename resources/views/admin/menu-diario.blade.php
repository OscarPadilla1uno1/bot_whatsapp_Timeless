<x-app-layout>
<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administrar Menú Diario') }}
        </h2>
    </x-slot>
    <div class="max-w-7xl mx-auto p-6">

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
        window.csrfToken = '{{ csrf_token() }}';
        window.routes = {
            porFecha: "{{ route('admin.menu.fecha') }}",
            agregar: "{{ route('admin.menu.agregar') }}",
            eliminar: "{{ route('admin.menu.eliminar') }}",
            actualizarCantidad: "{{ route('admin.menu.actualizarCantidad') }}"
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
    // Get local date string in YYYY-MM-DD format
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const today = `${year}-${month}-${day}`;
    
    console.log('Fecha de hoy:', today);
    
    const fechaInput = document.getElementById('fecha');
    
    // Verificar si el elemento existe
    if (fechaInput) {
        fechaInput.value = today;
        
        fechaInput.addEventListener('change', function () {
            cargarMenuPorFecha(this.value);
        });
    } else {
        console.error('No se encontró el elemento con id "fecha"');
    }

    document.getElementById('agregar-platillo-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const platilloId = document.getElementById('platillo').value;
        const cantidad = document.getElementById('cantidad').value;
        const fecha = document.getElementById('fecha').value;

        fetch(window.routes.agregar, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": window.csrfToken,
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
});
    </script>


</x-app-layout>