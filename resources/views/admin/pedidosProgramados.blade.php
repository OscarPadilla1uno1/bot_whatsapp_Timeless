<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Programar pedidos') }}
        </h2>
    </x-slot>

    <div class="p-4 space-y-6">

        {{-- Card de contenido dividido en 2 columnas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Menú del día --}}
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-2">Menú del día</h3>
                <input type="date" id="fecha-menu-programar" class="border p-2 rounded mb-4"
                    min="{{ now()->setTimezone('America/Tegucigalpa')->addDay()->format('Y-m-d') }}">
                <div id="contenedor-menu-programar" class="space-y-4">
                    <p class="text-gray-500">Seleccione una fecha para ver el menú.</p>
                </div>
            </div>

            {{-- Pedidos programados --}}
            <div class="bg-white shadow rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-2">Pedidos programados</h3>
                <div id="contenedor-pedidos">
                    {{--<p class="text-gray-500">Seleccione una fecha para ver pedidos programados.</p>--}}

                    <div class="overflow-x-auto">
                        <table id="tabla-pedidos" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        {{-- Formulario para programar nuevo pedido --}}
        <div class="bg-white p-4 rounded shadow">
            <h3 class="text-lg font-semibold mb-4">Nuevo Pedido</h3>
            <form id="form-programar-pedido">
                <meta name="csrf-token" content="{{ csrf_token() }}">
                <input type="hidden" id="ruta-store-pedido" value="{{ route('admin.pedidos.programado.store') }}">
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Nombre del cliente</label>
                        <input type="text" name="nombre" id="nombre" required
                            class="mt-1 block w-full border-gray-300 rounded shadow-sm">
                    </div>
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" required
                            class="mt-1 block w-full border-gray-300 rounded shadow-sm">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Link de Google Maps</label>
                    <input type="text" id="google-maps-link" required
                        class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                        placeholder="https://www.google.com/maps/place/...">
                    <div id="coordenadas-info" class="text-sm mt-1 text-gray-600"></div>
                    <input type="hidden" name="latitud" id="latitud">
                    <input type="hidden" name="longitud" id="longitud">
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Método de pago</label>
                    <select id="metodo_pago" class="mt-1 block w-full border-gray-300 rounded shadow-sm">
                        <option value="" disabled selected>Seleccione un método de pago</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>

                <div class="mt-4 flex items-center space-x-2">
                    <input type="checkbox" id="domicilio" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                    <label for="domicilio" class="text-sm text-gray-700">¿Entrega a domicilio?</label>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Notas / Observaciones</label>
                    <textarea id="notas" rows="3" class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                        placeholder="Ej. Sin cebolla, entregar a las 2pm..."></textarea>
                </div>

                {{-- Campos se llenan dinámicamente según el menú del día --}}
                <div id="form-platillos-programar" class="mt-4">
                    <p class="text-gray-500">Seleccione una fecha para ver las opciones.</p>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar
                        Pedido</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal para editar pedido (inicialmente oculto) --}}
    <div id="modal-editar-pedido-programado"
        class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex justify-center items-center overflow-hidden">
        <div class="relative bg-white p-6 rounded shadow w-full max-w-2xl max-h-screen overflow-y-auto">
            <!-- Botón para cerrar -->
            <button type="button" id="cerrar-modal-edit-pedido"
                class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-4xl font-bold rounded focus:outline-none">
                &times;
            </button>

            <h3 class="text-lg font-semibold mb-4">Editar Pedido</h3>

            <form id="form-editar-pedido-programar" method="PUT">
                <div id="campos-form-pedido-editar">
                    <!-- Cliente -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" id="nombre-edit" class="mt-1 block w-full rounded border-gray-300">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input type="text" id="telefono-edit" class="mt-1 block w-full rounded border-gray-300">
                    </div>

                    <!-- Coordenadas -->
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">Link de Google Maps</label>
                        <input type="text" id="google-maps-link-edit" required
                            class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                            placeholder="https://www.google.com/maps/place/...">
                        <div id="coordenadas-info-edit" class="text-sm mt-1 text-gray-600"></div>
                        <input type="hidden" name="latitud" id="latitud-edit">
                        <input type="hidden" name="longitud" id="longitud-edit">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Ubicación (lat, long)</label>
                        <input type="text" id="ubicacion-text-edit" class="mt-1 block w-full rounded border-gray-300"
                            disabled>
                    </div>

                    <!-- Campo DOMICILIO -->
                    <div class="mb-4 flex items-center space-x-2">
                        <input type="checkbox" id="domicilio-edit" name="domicilio"
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                        <label for="domicilio-edit" class="text-sm text-gray-700">¿Entrega a domicilio?</label>
                    </div>

                    <!-- Campo NOTAS -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Notas / Observaciones</label>
                        <textarea id="notas-edit" name="notas" rows="3"
                            class="mt-1 block w-full border-gray-300 rounded shadow-sm"
                            placeholder="Ej. sin cebolla, entregar antes de las 2 pm..."></textarea>
                    </div>

                    <!-- Aquí se inyecta método de pago y platillos -->
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.routes = {
            pedidosPorFecha: "{{ route('admin.pedidos.por_fecha') }}",
            menuPorFecha: "{{ route('admin.menu.fecha') }}",
            actualizarPedido: "{{ route('admin.pedidos.programado.actualizar', ['id' => '__ID__']) }}",
            editarPedido: "{{ route('admin.pedidos.programado.edit', ['pedido' => '__ID__']) }}",
            borrarPedido: "{{ route('admin.borrar.programado', ['id' => '__ID__']) }}"
        };
    </script>



    <script>
        document.addEventListener("DOMContentLoaded", function () {


            // Inicializar DataTable vacía al principio
            $('#tabla-pedidos').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
                    emptyTable: "No hay pedidos programados"
                },
                columns: [
                    { title: "#" },
                    { title: "Cliente" },
                    { title: "Estado" },
                    { title: "Total" },
                    { title: "Acciones" }
                ],
                pageLength: 10,
                lengthChange: false,
                createdRow: function (row, data, dataIndex) {
                    // Aplica clases de Tailwind a cada celda del row
                    $('td', row).addClass('px-2 py-2 whitespace-nowrap text-sm text-gray-900');

                    // Opcional: Estilo especial para la última celda (acciones)
                    $('td:last-child', row).removeClass('text-gray-900').addClass('text-blue-600 font-semibold text-xs');
                },
                initComplete: function () {
                    // Aplica clases al thead (una sola vez)
                    $('#tabla-pedidos thead').addClass('bg-gray-100');
                    $('#tabla-pedidos thead th').addClass('px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider');

                    // Aplica clases a tbody
                    $('#tabla-pedidos tbody').addClass('bg-white divide-y divide-gray-200');
                },
            });



            // Event listener para formulario de nuevo pedido
            document.getElementById("form-programar-pedido").addEventListener("submit", async function (e) {
                e.preventDefault();

                //console.log("llegamos al submit"); // Verificar que se llega al submit

                const fecha = document.getElementById("fecha-menu-programar").value;
                const nombre = document.getElementById("nombre").value;
                const telefono = document.getElementById("telefono").value;
                const latitud = parseFloat(document.getElementById("latitud").value);
                const longitud = parseFloat(document.getElementById("longitud").value);
                const mapaUrl = document.getElementById("google-maps-link").value;
                const metodo_pago = document.getElementById("metodo_pago")?.value;
                const domicilio = document.getElementById("domicilio").checked;
                const notas = document.getElementById("notas").value.trim() || null;

                const platillos = [];

                document
                    .querySelectorAll('#form-platillos-programar input[type="number"]')
                    .forEach((input) => {
                        const cantidad = parseInt(input.value);
                        const id = parseInt(input.name.replace("cantidad_", ""));

                        if (cantidad > 0) {
                            platillos.push({ id, cantidad });
                        }
                    });

                if (platillos.length === 0) {
                    Swal.fire({
                        icon: "warning",
                        title: "Sin platillos",
                        text: "Debes seleccionar al menos un platillo.",
                    });
                    return;
                }

                if (!fecha) {
                    Swal.fire({
                        icon: "warning",
                        title: "Fecha requerida",
                        text: "Debes seleccionar una fecha para el pedido.",
                    });
                    return;
                }

                if (!nombre.trim()) {
                    Swal.fire({
                        icon: "warning",
                        title: "Nombre requerido",
                        text: "El nombre del cliente no puede estar vacío.",
                    });
                    return;
                }

                if (!telefono.trim()) {
                    Swal.fire({
                        icon: "warning",
                        title: "Teléfono requerido",
                        text: "Debes ingresar el número de teléfono.",
                    });
                    return;
                }

                if (!mapaUrl.trim()) {
                    Swal.fire({
                        icon: "warning",
                        title: "Link de Google Maps requerido",
                        text: "Por favor proporciona un enlace válido de ubicación.",
                    });
                    return;
                }

                if (!latitud || isNaN(latitud) || !longitud || isNaN(longitud)) {
                    Swal.fire({
                        icon: "warning",
                        title: "Coordenadas no detectadas",
                        text: "No se detectaron coordenadas válidas en el enlace de Google Maps.",
                    });
                    return;
                }

                if (!metodo_pago) {
                    Swal.fire({
                        icon: "warning",
                        title: "Método de pago requerido",
                        text: "Debes seleccionar un método de pago.",
                    });
                    return;
                }

                const payload = {
                    fecha,
                    nombre,
                    telefono,
                    mapa_url: mapaUrl,
                    latitud,
                    longitud,
                    platillos,
                    metodo_pago,
                    domicilio,
                    notas,
                };
                console.log(payload)

                const token = document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content");

                // Obtener la URL del input oculto
                const url = document.getElementById("ruta-store-pedido").value;

                try {
                    const res = await fetch(url, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": token,
                        },
                        body: JSON.stringify(payload),
                        credentials: "same-origin",
                    });

                    const data = await res.json();

                    if (res.ok) {
                        Swal.fire({
                            icon: "success",
                            title: "¡Pedido guardado!",
                            text: "El pedido fue registrado correctamente.",
                        });

                        console.log("Pedido guardado:", data);

                        document.getElementById("form-programar-pedido").reset();
                        document.getElementById("form-platillos-programar").innerHTML =
                            '<p class="text-gray-500">Seleccione una fecha para ver las opciones.</p>';
                        document.getElementById("coordenadas-info").textContent = "";

                        cargarPedidosPorFecha(fecha); // Reusar función si ya existe
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error al guardar",
                            text: data.mensaje || "Ocurrió un error desconocido.",
                        });
                    }
                } catch (error) {
                    console.error("Error al enviar datos:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error de red",
                        text: "No se pudo conectar con el servidor.",
                    });
                }
            });

            // Event listener para enlace de Google Maps en formulario principal
            const googleMapsInput = document.getElementById("google-maps-link");
            if (googleMapsInput) {
                googleMapsInput.addEventListener("input", function () {
                    const value = this.value;
                    const match = value.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                    const infoDiv = document.getElementById("coordenadas-info");

                    if (match) {
                        const lat = parseFloat(match[1]);
                        const lng = parseFloat(match[2]);
                        document.getElementById("latitud").value = lat;
                        document.getElementById("longitud").value = lng;

                        infoDiv.textContent = `✅ Coordenadas detectadas: Latitud ${lat}, Longitud ${lng}`;
                        infoDiv.classList.remove("text-red-600");
                        infoDiv.classList.add("text-green-600");
                    } else {
                        infoDiv.textContent =
                            "⚠️ No se detectaron coordenadas en el enlace.";
                        infoDiv.classList.remove("text-green-600");
                        infoDiv.classList.add("text-red-600");

                        document.getElementById("latitud").value = "";
                        document.getElementById("longitud").value = "";
                    }
                });
            }

            // Event listener para enlace de Google Maps en formulario de edición
            const googleMapsEditInput = document.getElementById("google-maps-link-edit");
            if (googleMapsEditInput) {
                googleMapsEditInput.addEventListener("input", function () {
                    const value = this.value;
                    const match = value.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                    const infoDiv = document.getElementById("coordenadas-info-edit");

                    if (match) {
                        const lat = parseFloat(match[1]);
                        const lng = parseFloat(match[2]);
                        document.getElementById("latitud-edit").value = lat;
                        document.getElementById("longitud-edit").value = lng;

                        infoDiv.textContent = `✅ Coordenadas detectadas: Latitud ${lat}, Longitud ${lng}`;
                        infoDiv.classList.remove("text-red-600");
                        infoDiv.classList.add("text-green-600");
                    } else {
                        infoDiv.textContent =
                            "⚠️ No se detectaron coordenadas en el enlace.";
                        infoDiv.classList.remove("text-green-600");
                        infoDiv.classList.add("text-red-600");

                        document.getElementById("latitud-edit").value = "";
                        document.getElementById("longitud-edit").value = "";
                    }
                });
            }

            // Event listener para fecha
            const fechaInput = document.getElementById("fecha-menu-programar");
            if (fechaInput) {
                fechaInput.addEventListener("change", function () {
                    const fecha = this.value;
                    if (fecha) {
                        cargarPedidosPorFecha(fecha);
                    }
                });
            }

            // Event listener para cerrar modal de edición
            const cerrarModalBtn = document.getElementById("cerrar-modal-edit-pedido");
            if (cerrarModalBtn) {
                cerrarModalBtn.addEventListener("click", function () {
                    document.getElementById("modal-editar-pedido-programado").classList.add("hidden");
                });
            }

            document.getElementById("form-editar-pedido-programar").addEventListener("submit", function (e) {
                e.preventDefault();
                actualizarPedidoProgramado(this);
            });
        });
    </script>



</x-app-layout>