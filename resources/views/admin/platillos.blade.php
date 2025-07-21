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
                    <form method="GET" action="{{ route('admin.platillos') }}" class="mb-4 flex gap-2 items-center">
                        <!-- Barra de búsqueda alineada a la izquierda -->
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Buscar platillo..."
                            class="block w-full sm:w-80 md:w-96 rounded-md border-gray-300 shadow-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">

                        <!-- Botón de Buscar -->
                        <button type="submit"
                            class="bg-indigo-600 text-white rounded-md px-4 py-2 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
                            Buscar
                        </button>

                        <!-- Botón de Reiniciar Filtros -->
                        <button type="submit"
                            class="bg-gray-600 text-white rounded-md px-4 py-2 hover:bg-gray-700 focus:ring-2 focus:ring-gray-500"
                            name="search" value="">
                            Reiniciar Filtros
                        </button>
                    </form>

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
                                    Imagen</th>
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
                                        <!-- Verificar si hay una imagen -->
                                        @if($platillo->imagen_url)
                                            <button onclick="showImage('{{ asset('storage/' . $platillo->imagen_url) }}')"
                                                class="text-blue-600 hover:text-blue-800 font-semibold text-xs sm:text-sm mr-2"
                                                data-imagen-url="{{ asset('storage/' . $platillo->imagen_url) }}">
                                                Ver Imagen
                                            </button>
                                        @else
                                            <span class="text-red-500">Sin imagen</span>
                                        @endif
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
                    {{ $platillos->appends(['search' => request('search')])->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6 space-y-6">
        <!-- Card: Formulario para agregar platillo -->
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Agregar Nuevo Platillo</h4>

                <form method="POST" action="{{ route('admin.platillos.crear') }}" id="agregar-platillo-form"
                    class="space-y-4">
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
                        <label for="imagen" class="block text-sm font-medium text-gray-700">Imagen del Platillo:</label>
                        <div class="mt-1">
                            <!-- Contenedor para el input de tipo file -->
                            <label for="imagen-input"
                                class="cursor-pointer inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span id="imagen-text">Seleccionar Imagen</span>
                                <input type="file" name="imagen" id="imagen-input" accept="image/*" class="hidden"
                                    onchange="updateButtonText()" />

                            </label>
                        </div>
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

    <!-- Modal de edición -->
    <div id="modal-editar-platillo"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded shadow max-w-md w-full max-h-[90vh] overflow-y-auto">

            <h3 class="text-lg font-bold mb-4">Editar Platillo</h3>

            <form id="form-editar-platillo" method="POST" action="{{ route('admin.platillos.actualizar') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="edit-id">

                <div class="mb-4">
                    <label for="edit-nombre" class="block text-sm font-medium text-gray-700">Nombre:</label>
                    <input type="text" name="nombre" id="edit-nombre" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label for="edit-descripcion" class="block text-sm font-medium text-gray-700">Descripción:</label>
                    <textarea name="descripcion" id="edit-descripcion" rows="3" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                </div>

                <div class="mb-4">
                    <label for="edit-precio_base" class="block text-sm font-medium text-gray-700">Precio Base
                        (LPS.):</label>
                    <input type="number" name="precio_base" id="edit-precio_base" step="0.01" min="1" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label for="edit-imagen" class="block text-sm font-medium text-gray-700">Imagen:</label>
                    <input type="file" name="imagen" id="edit-imagen" accept="image/*"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <p id="imagen-previa" class="mt-2 text-sm text-gray-500"></p>
                    <button type="button" id="eliminar-imagen"
                        class="text-red-600 hover:text-red-800 font-semibold text-xs sm:text-sm mt-2 hidden">
                        Eliminar Imagen
                    </button>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="cerrarModalEditar()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Cancelar</button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Guardar</button>
                </div>

            </form>
        </div>
    </div>

    


    <!-- Modal para ver la imagen -->
    <div id="imageModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white p-4 rounded-lg max-w-sm w-full">
            <img id="full-image" src="" alt="Imagen del Platillo" class="w-full rounded-md">
            <button onclick="closeImageModal()" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-md">Cerrar</button>
        </div>
    </div>

    <script>
        window.routes = {
            eliminarPlatillo: "{{route('admin.platillos.eliminar')}}",
            actualizarPlatillo: "{{route('admin.platillos.actualizar')}}",
            crearPlatillo: "{{route('admin.platillos.crear')}}"
        }
    </script>

    <script>
    window.csrfToken = "{{ csrf_token() }}";
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
    const formEditar = document.getElementById("form-editar-platillo");
    const formAgregar = document.getElementById("agregar-platillo-form");

    if (formEditar) {
        formEditar.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch("{{route('admin.platillos.actualizar')}}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire(
                            "Actualizado",
                            "El platillo se actualizó correctamente.",
                            "success"
                        ).then(() => location.reload());
                    } else {
                        Swal.fire(
                            "Error",
                            "Hubo un problema al actualizar.",
                            "error"
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    Swal.fire("Error", "Ocurrió un error inesperado.", "error");
                });
        });
    }

    if (formAgregar) {
        formAgregar.addEventListener("submit", function (event) {
            event.preventDefault();

            const imagenInput = document.getElementById("imagen-input");
            const file = imagenInput.files[0];

            if (!file) {
                Swal.fire({
                    icon: "warning",
                    title: "Imagen requerida",
                    text: "Por favor, selecciona una imagen antes de guardar el platillo.",
                    confirmButtonText: "Aceptar",
                });
                return;
            }

            if (!file.type.startsWith("image/")) {
                Swal.fire({
                    icon: "error",
                    title: "Archivo no válido",
                    text: "El archivo seleccionado no es una imagen.",
                    confirmButtonText: "Aceptar",
                });
                return;
            }

            const formData = new FormData(this);
            const paginaActual =
                new URLSearchParams(window.location.search).get("page") || 1;
            formData.append("page", paginaActual);

            fetch("{{route('admin.platillos.crear')}}", {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            title: "¡Éxito!",
                            text: "Platillo guardado correctamente.",
                            icon: "success",
                            confirmButtonText: "Aceptar",
                            allowOutsideClick: false,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        });
    }

    document.querySelectorAll(".descripcion-cell").forEach((cell) => {
        cell.addEventListener("click", () => {
            const div = cell.querySelector("div");
            if (div.classList.contains("truncate")) {
                div.classList.remove("truncate");
                div.classList.add("whitespace-normal");
            } else {
                div.classList.add("truncate");
                div.classList.remove("whitespace-normal");
            }
        });
    });
});
    </script>

    




</x-app-layout>