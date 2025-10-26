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

        {{-- ========================================= --}}
        {{-- PAGOS CONSOLIDADOS - CLIENTES TARJETA --}}
        {{-- ========================================= --}}
        <div class="bg-white shadow rounded-lg p-4 mt-6">
            <h3 class="text-lg font-semibold mb-4">Pagos consolidados de clientes</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- SELECT DE CLIENTES --}}
                <div>
                    <label class="block font-medium text-sm text-gray-700 mb-2">Seleccionar cliente</label>
                    <select id="select-cliente" class="w-full border-gray-300 rounded shadow-sm"></select>
                </div>

                {{-- BOTÓN PARA CREAR LINK DE PAGO --}}
                <div class="flex items-end justify-end">
                    <button id="btn-ver-pagos"
                        class="bg-gray-600 text-white px-4 py-2 mr-1 rounded hover:bg-gray-700 disabled:opacity-50" disabled>
                        Ver pagos consolidados
                    </button>
                    <button id="btn-generar-pago"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50" disabled>
                        Generar link de pago
                    </button>
                </div>
            </div>

            {{-- CALENDARIO SIMPLE --}}
            <div id="contenedor-calendario" class="mt-6">
                <p class="text-gray-500 text-sm">Seleccione un cliente para ver sus pedidos programados.</p>
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
                    <input type="text" id="google-maps-link"
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

    <!-- Modal de pagos consolidados -->
    <!-- Modal de pagos consolidados -->
    <div id="modal-pagos-consolidados"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4 overflow-y-auto">

        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto relative p-6">
            <button id="cerrar-modal-pagos"
                class="absolute top-3 right-4 text-2xl text-gray-400 hover:text-red-600 transition">
                &times;
            </button>

            <h3 class="text-xl font-semibold mb-4 text-gray-800">
                Pagos consolidados del cliente
            </h3>

            <div id="contenedor-pagos-consolidados" class="space-y-3">
                <p class="text-gray-500 text-sm">Seleccione un cliente para ver sus pagos consolidados.</p>
            </div>
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

    <script>document.addEventListener("DOMContentLoaded", () => {
            const btnVerPagos = document.getElementById("btn-ver-pagos");
            const modal = document.getElementById("modal-pagos-consolidados");
            const contenedor = document.getElementById("contenedor-pagos-consolidados");
            const cerrarModal = document.getElementById("cerrar-modal-pagos");

            // 🟢 Activar botón cuando se seleccione un cliente
            $('#select-cliente').on('change', function () {
                const clienteId = $(this).val();
                btnVerPagos.disabled = !clienteId;
                document.getElementById('btn-generar-pago').disabled = !clienteId;
            });

            // 🟢 Evento para abrir modal
            btnVerPagos.addEventListener("click", async () => {
                const clienteId = $('#select-cliente').val();
                if (!clienteId) return;

                contenedor.innerHTML = '<p class="text-gray-500 text-sm">Cargando pagos...</p>';
                modal.classList.remove("hidden");

                try {
                    const res = await fetch(`/admin/clientes/${clienteId}/pagos-consolidados`);
                    const pagos = await res.json();

                    if (!pagos.length) {
                        contenedor.innerHTML = '<p class="text-gray-500 text-sm">Este cliente no tiene pagos consolidados.</p>';
                        return;
                    }

                    contenedor.innerHTML = pagos.map(p => `
                <div class="border rounded-lg p-3 hover:bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-gray-700">Referencia: ${p.referencia_transaccion || 'N/A'}</p>
                            <p class="text-sm text-gray-500">Fecha: ${p.fecha_pago}</p>
                            <p class="text-sm text-gray-500">Estado: 
                                <span class="font-medium ${p.estado_pago === 'confirmado' ? 'text-green-600' : p.estado_pago === 'pendiente' ? 'text-yellow-600' : 'text-red-600'}">
                                    ${p.estado_pago.toUpperCase()}
                                </span>
                            </p>
                        </div>
                        <p class="font-bold text-blue-600">L. ${Number(p.monto_total).toFixed(2)}</p>
                    </div>
                    <div class="mt-2 text-sm text-gray-700">
                        <p class="font-medium">Pedidos incluidos:</p>
                        <ul class="list-disc ml-6 text-gray-600">
                            ${p.pedidos.map(pd => `<li>#${pd.id} - ${pd.estado} - L. ${pd.total} (${pd.fecha_pedido})</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `).join('');
                } catch (error) {
                    console.error("Error cargando pagos consolidados:", error);
                    contenedor.innerHTML = '<p class="text-red-500 text-sm">Error al cargar pagos del cliente.</p>';
                }
            });

            // 🔴 Cerrar modal
            cerrarModal.addEventListener("click", () => modal.classList.add("hidden"));
            modal.addEventListener("click", (e) => {
                if (e.target === modal) modal.classList.add("hidden");
            });
        });


    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {


            // Metodos necesarios para pagos consolidados
            // ================================================
            // CALENDARIO DE PAGOS PROGRAMADOS (TARJETA)
            // ================================================

            $('#select-cliente').select2({
                placeholder: 'Buscar cliente...',
                allowClear: true,
                width: '100%'
            });

            // 🧩 2. Cargar lista de clientes
            async function cargarClientes() {
                try {
                    const res = await fetch("{{ route('admin.clientes.listar') }}"); // 👉 Crear esta ruta simple
                    const clientes = await res.json();

                    console.log("Clientes recibidos:", clientes);

                    const select = $('#select-cliente');
                    select.empty().append('<option></option>'); // placeholder

                    clientes.forEach(c => {
                        select.append(new Option(`${c.nombre} (${c.telefono})`, c.id));
                    });

                    select.trigger('change.select2');
                } catch (error) {
                    console.error("Error cargando clientes:", error);
                }
            }

            cargarClientes();

            // 🧩 3. Escuchar selección de cliente
            $('#select-cliente').on('change', async function () {
                const clienteId = $(this).val();
                const contenedor = document.getElementById('contenedor-calendario');
                contenedor.innerHTML = '<p class="text-gray-500 text-sm">Cargando pedidos...</p>';
                document.getElementById('btn-generar-pago').disabled = true;

                if (!clienteId) {
                    contenedor.innerHTML = '<p class="text-gray-500 text-sm">Seleccione un cliente para ver sus pedidos.</p>';
                    return;
                }

                try {
                    const res = await fetch("{{ route('admin.pedidos.programados.tarjeta') }}");
                    const data = await res.json();

                    console.log("Pedidos recibidos:", data);

                    const pedidosCliente = data.filter(ev => ev.cliente_id == clienteId);

                    console.log("Pedidos del cliente:", pedidosCliente);

                    if (pedidosCliente.length === 0) {
                        contenedor.innerHTML = '<p class="text-gray-500 text-sm">Este cliente no tiene pedidos pendientes con tarjeta.</p>';
                        return;
                    }

                    renderCalendario(pedidosCliente);
                    document.getElementById('btn-generar-pago').disabled = false;

                } catch (error) {
                    console.error("Error cargando pedidos:", error);
                    contenedor.innerHTML = '<p class="text-red-500 text-sm">Error cargando pedidos del cliente.</p>';
                }
            });

            // 🧩 4. Renderizar calendario simple (tabla compacta)
            function renderCalendario(pedidosCliente) {
                const contenedor = document.getElementById('contenedor-calendario');
                contenedor.innerHTML = '';

                // Crear tabla
                const tabla = document.createElement('table');
                tabla.className = 'min-w-full border border-gray-200 rounded text-sm';

                // Encabezados
                tabla.innerHTML = `
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-600 w-1/5">Fecha</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600">Pedidos</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600 w-1/5">Total</th>
                <th class="px-3 py-2 text-center font-medium text-gray-600 w-16">Sel.</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;

                const tbody = tabla.querySelector('tbody');

                pedidosCliente.forEach(grupo => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b hover:bg-blue-50 cursor-pointer transition';

                    const total = grupo.total.toFixed(2);
                    const pedidos = grupo.pedidos.map(p => `#${p.id} - L. ${p.total}`).join(', ');

                    tr.innerHTML = `
            <td class="px-3 py-2 text-gray-800">${grupo.fecha}</td>
            <td class="px-3 py-2 text-gray-500 truncate max-w-[250px]" title="${pedidos}">
                ${pedidos.length > 60 ? pedidos.slice(0, 60) + '...' : pedidos}
            </td>
            <td class="px-3 py-2 text-right text-blue-600 font-semibold">L. ${total}</td>
            <td class="px-3 py-2 text-center">
                <input type="checkbox" class="chk-fecha h-4 w-4 text-green-600 border-gray-300 rounded">
            </td>
        `;

                    // Toggle selección visual al hacer clic en la fila
                    tr.addEventListener('click', (e) => {
                        // Evitar conflicto con clic directo en el checkbox
                        if (e.target.tagName !== 'INPUT') {
                            const chk = tr.querySelector('.chk-fecha');
                            chk.checked = !chk.checked;
                        }
                        tr.classList.toggle('bg-green-100', tr.querySelector('.chk-fecha').checked);
                    });

                    tbody.appendChild(tr);
                });

                contenedor.appendChild(tabla);
            }


            // 🧩 5. Generar link de pago consolidado (versión adaptada a tabla compacta)
            document.getElementById('btn-generar-pago').addEventListener('click', async () => {
                // Obtener filas seleccionadas
                const filasSeleccionadas = Array.from(document.querySelectorAll('#contenedor-calendario tbody tr'))
                    .filter(tr => tr.querySelector('.chk-fecha')?.checked);

                if (filasSeleccionadas.length === 0) {
                    Swal.fire('Seleccione al menos un día para facturar.', '', 'info');
                    return;
                }

                // Extraer pedidos e información de las filas seleccionadas
                const pedidosIds = [];
                let total = 0;

                filasSeleccionadas.forEach(tr => {
                    const tooltip = tr.querySelector('td:nth-child(2)').title || '';
                    const ids = tooltip.match(/#(\d+)/g)?.map(id => parseInt(id.replace('#', ''))) || [];
                    pedidosIds.push(...ids);

                    const monto = parseFloat(tr.querySelector('td:nth-child(3)').textContent.replace('L. ', '').trim());
                    total += monto;
                });

                // Obtener datos del cliente
                const clienteId = $('#select-cliente').val();
                const clienteText = $('#select-cliente option:selected').text();
                const fecha = new Date().toISOString().split('T')[0];

                if (!clienteId) {
                    Swal.fire('Seleccione un cliente antes de generar el link.', '', 'warning');
                    return;
                }

                const payload = { cliente: clienteText, cliente_id: clienteId, pedido_ids: pedidosIds, total, fecha };

                console.log("Payload para generar link de pago:", payload);

                try {
                    const res = await fetch("{{ route('admin.pagos.consolidado.create') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    console.log("Respuesta del servidor:", res);

                    const data = await res.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Link de pago generado',
                            html: `
                    <p class="mb-2 text-gray-700">Total: <strong>L. ${total.toFixed(2)}</strong></p>
                    <a href="${data.processUrl}" target="_blank" class="text-blue-600 underline font-medium">
                        Abrir link de pago
                    </a>
                `,
                        });
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo generar el link.', 'error');
                    }

                } catch (error) {
                    console.error(error);
                    Swal.fire('Error de red', 'No se pudo conectar con el servidor.', 'error');
                }
            });


            // ================================================
            // FUNCIÓN AUXILIAR PARA GENERAR LINK DE PAGO
            // ================================================

            /*async function generarLinkConsolidado(pedidos, cliente, fecha) {
                const pedidoIds = pedidos.map(p => p.id);
                const total = pedidos.reduce((sum, p) => sum + parseFloat(p.total), 0);

                const payload = {
                    cliente: cliente,
                    pedido_ids: pedidoIds,
                    total: total,
                    fecha: fecha
                };

                Swal.fire({
                    title: "Generando link...",
                    text: "Por favor espere un momento",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                try {
                    const res = await fetch("{{ route('admin.pagos.consolidado.create') }}", {
            method: "POST",
                headers: {
                "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                        },
            body: JSON.stringify(payload),
                    });

        const data = await res.json();

        Swal.close();

        if (res.ok && data.success) {
            Swal.fire({
                icon: "success",
                title: "Link de pago creado",
                html: `<p>Link generado:</p><a href="${data.processUrl}" target="_blank" class="text-blue-600 underline">${data.processUrl}</a>`,
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.message || "No se pudo generar el link de pago.",
            });
        }
                } catch (error) {
            Swal.fire({
                icon: "error",
                title: "Error de red",
                text: "No se pudo conectar con el servidor.",
            });
        }
            }*/

        ////


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
            let latitud = parseFloat(document.getElementById("latitud").value);
            let longitud = parseFloat(document.getElementById("longitud").value);
            let mapaUrl = document.getElementById("google-maps-link").value;
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

            //14.107337349511209, -87.18244931948077

            if(!domicilio){
                latitud = 14.107337349511209;
                longitud = -87.18244931948077;
                mapaUrl = "https://www.google.com/maps/place/La+Campa%C3%B1a+Food+Service/@14.1072125,-87.1849813,17z/data=!3m1!4b1!4m6!3m5!1s0x8f6fa3917ee15e31:0xa4952da2a77db2ea!8m2!3d14.1072125!4d-87.1824064!16s%2Fg%2F11tc2n04dn?entry=ttu&g_ep=EgoyMDI1MTAyMC4wIKXMDSoASAFQAw%3D%3D";
            }

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
                    cargarClientes();
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