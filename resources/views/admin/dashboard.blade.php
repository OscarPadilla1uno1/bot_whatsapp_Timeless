<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Card de bienvenida -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __('Bienvenido de vuelta') }}, {{ Auth::user()->name }}
                </div>
            </div>

            <!-- Card del menú del día -->
            <div class="mt-6 bg-white shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Menú del Día</h4>

                    @if (empty($menu))
                        <p class="text-gray-600">No hay menú disponible para hoy.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            #</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nombre</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Descripción</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Precio</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cantidad Disponible</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($menu as $index => $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $index + 1 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->nombre }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->descripcion }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->precio_base }} LPS</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $item->cantidad_disponible }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @can('Administrador')
                        <div class="mt-4">
                            <a href="{{ route('admin.menu') }}"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Crear/Editar Menú del Día
                            </a>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Ventas Mensuales -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Ventas Mensuales</h4>
                    <canvas id="graficoVentas"></canvas>
                </div>

                <!-- Clientes Frecuentes -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col items-center justify-center">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Clientes Frecuentes</h4>
                    <canvas id="graficoClientes" class="max-w-xs max-h-64"></canvas>
                </div>

                <!-- Platillos Más Vendidos -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Platillos Más Vendidos</h4>
                    <canvas id="graficoPlatillos"></canvas>
                </div>

                <!-- Ventas Semanales -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Ventas Semanales</h4>
                    <canvas id="graficoSemana"></canvas>
                </div>
            </div>



        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ventasMensuales = @json($ventasMensuales);
        const platillosMasVendidos = @json($platillosMasVendidos);
        const clientesFrecuentes = @json($clientesFrecuentes);
        const ventasSemanales = @json($ventasSemanales);

        // Ventas Mensuales
        new Chart(document.getElementById('graficoVentas'), {
            type: 'bar',
            data: {
                labels: ventasMensuales.map(e => e.mes),
                datasets: [{
                    label: 'Ventas (LPS)',
                    data: ventasMensuales.map(e => e.total),
                    backgroundColor: '#60A5FA'
                }]
            }
        });

        // Platillos Más Vendidos
        new Chart(document.getElementById('graficoPlatillos'), {
            type: 'bar',
            data: {
                labels: platillosMasVendidos.map(e => e.nombre),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: platillosMasVendidos.map(e => e.total_vendido),
                    backgroundColor: '#F59E0B'
                }]
            }
        });

        // Clientes Frecuentes (Pastel)
        new Chart(document.getElementById('graficoClientes'), {
            type: 'pie',
            data: {
                labels: clientesFrecuentes.map(e => e.nombre),
                datasets: [{
                    data: clientesFrecuentes.map(e => e.total_pedidos),
                    backgroundColor: clientesFrecuentes.map(() => '#' + Math.floor(Math.random() * 16777215).toString(16))
                }]
            }
        });

        // Ventas Semanales
        new Chart(document.getElementById('graficoSemana'), {
            type: 'bar',
            data: {
                labels: ventasSemanales.map(e => e.dia),
                datasets: [{
                    label: 'Ventas (LPS)',
                    data: ventasSemanales.map(e => e.total),
                    backgroundColor: '#10B981'
                }]
            }
        });
    </script>





</x-app-layout>