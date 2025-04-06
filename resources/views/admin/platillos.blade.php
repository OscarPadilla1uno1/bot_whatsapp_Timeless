<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Platillos') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-bold mb-4">Platillos Disponibles</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="tabla-platillos">
                        <thead class="bg-gray-100">
                            <tr>
                                <th
                                    class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    #</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Descripción</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Precio</th>
                                <th
                                    class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-platillos-body" class="bg-white divide-y divide-gray-200">
                            @forelse($platillos as $index => $platillo)
                                <tr>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900 max-w-[10rem] truncate"
                                        title="{{ $platillo->nombre }}">
                                        {{ $platillo->nombre }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900 max-w-[16rem] truncate cursor-pointer descripcion-cell"
                                        title="{{ $platillo->descripcion }}">
                                        <div class="inline-block w-full truncate">
                                            {{ $platillo->descripcion }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900">
                                        LPS.{{ number_format($platillo->precio_base, 2) }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <button
                                            class="text-blue-600 hover:text-blue-800 font-semibold text-xs sm:text-sm mr-2"
                                            onclick="editarPlatillo({{ $platillo->id }})">
                                            Editar
                                        </button>
                                        <button class="text-red-600 hover:text-red-800 font-semibold text-xs sm:text-sm"
                                            onclick="eliminarPlatillo({{ $platillo->id }})">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-500">No hay platillos
                                        registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pagination">
                    {{ $platillos->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 space-y-6">
    <!-- Card: Formulario para agregar platillo -->
    <div class="bg-white shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Agregar Nuevo Platillo</h4>

            <form method="POST" action="{{ route('admin.platillos.crear') }}" id="agregar-platillo-form" class="space-y-4">
                @csrf

                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Platillo:</label>
                    <input type="text" name="nombre" id="nombre" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea name="descripcion" id="descripcion" rows="3" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <div>
                    <label for="precio_base" class="block text-sm font-medium text-gray-700">Precio Base
                        (Lempiras):</label>
                    <input type="number" step="0.01" name="precio_base" id="precio_base" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 transition">
                        Guardar Platillo
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <script>
    document.getElementById('agregar-platillo-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevenir que el formulario se envíe de la manera tradicional

    const formData = new FormData(this);
    const paginaActual = new URLSearchParams(window.location.search).get('page') || 1; // Obtener la página actual

    // Agregar el parámetro de la página actual al formulario
    formData.append('page', paginaActual);

    fetch('{{ route('admin.platillos.crear') }}', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Platillo guardado correctamente.',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Recargar la página solo si el usuario hizo clic en "Aceptar"
                    location.reload();
                }
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

</script>

    <script>
        document.querySelectorAll('.descripcion-cell').forEach(cell => {
            cell.addEventListener('click', () => {
                const div = cell.querySelector('div');
                // Alternar clases para expandir/contraer
                if (div.classList.contains('truncate')) {
                    div.classList.remove('truncate');
                    div.classList.add('whitespace-normal');
                } else {
                    div.classList.add('truncate');
                    div.classList.remove('whitespace-normal');
                }
            });
        });
    </script>

</x-app-layout>