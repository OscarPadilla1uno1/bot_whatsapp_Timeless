<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pedidos') }}
        </h2>
    </x-slot>

    <div class="space-y-6 p-4">
        {{-- Mensajes --}}
        @if (session('success'))
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        {{-- Card: Tabla de pedidos --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

            <div class="p-6 text-gray-900">
                {{-- Tabs --}}
                <div class="border-b border-gray-200 mb-4">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <a href="{{ route('cocina.pedidosCocina', ['tab' => 'hoy']) }}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab == 'hoy' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Hoy
                        </a>
                        {{-- <a href="{{ route('cocina.pedidosCocina', ['tab' => 'futuro']) }}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab == 'futuro' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Futuro
                        </a>
                        <a href="{{ route('cocina.pedidosCocina', ['tab' => 'pasado']) }}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $tab == 'pasado' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Pasado
                        </a> --}}
                    </nav>
                </div>
                <div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
                    <form method="GET" class="flex flex-wrap gap-2 w-full sm:w-auto">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                            placeholder="Buscar cliente, estado, fecha o total"
                            class="border rounded px-3 py-2 w-full sm:w-64">

                        <button type="submit"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm">
                            Buscar
                        </button>

                        @if(request('buscar'))
                            <a href="{{ route('cocina.pedidosCocina', ['tab' => $tab]) }}"
                                class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 text-sm">
                                Reiniciar
                            </a>
                        @endif
                    </form>
                </div>
                <h3 class="text-lg font-semibold mb-4">Lista de Pedidos</h3>

                <table class="min-w-full divide-y divide-gray-200" id="tabla-pedidos">
                   <thead class="bg-gray-100">
    <tr>
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
        {{-- AGREGAR ESTA LÍNEA --}}
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
    </tr>
</thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                       @forelse ($pedidos as $pedido)
    <tr>
        <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900">{{ $pedido->id }}</td>
        <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900 max-w-[10rem] truncate"
            title="{{ $pedido->cliente->nombre }}">
            {{ $pedido->cliente->nombre }}
        </td>
        <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900">{{ ucfirst($pedido->estado) }}</td>
        
        {{-- AGREGAR ESTA COLUMNA --}}
        <td class="px-2 py-2 whitespace-nowrap text-sm">
            @if($pedido->domicilio == 1)
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                    Domicilio
                </span>
            @else
                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                    Local
                </span>
            @endif
        </td>
        
        <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900">
            LPS. {{ number_format($pedido->total, 2) }}
        </td>
        <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-900">
            {{ \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}
        </td>
        <td class="px-2 py-2 whitespace-nowrap">
            <a href="{{ route('cocina.pedidosCocina', ['tab' => $tab, 'pedido_id' => $pedido->id]) }}"
                class="text-blue-600 hover:text-blue-800 font-semibold text-xs sm:text-sm">
                Ver Detalle
            </a>
        </td>
    </tr>
@empty
    {{-- Cambiar colspan de 6 a 7 porque agregamos una columna --}}
    <tr>
        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
            No hay pedidos registrados.
        </td>
    </tr>
@endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $pedidos->links() }}
                </div>
            </div>
        </div>

        {{-- Card: Detalle del pedido seleccionado --}}
        @if ($pedidoSeleccionado)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Detalle del Pedido #{{ $pedidoSeleccionado->id }}</h3>

                    {{-- AGREGAR TIPO DE ENTREGA --}}
<div class="grid grid-cols-1 items-center sm:grid-cols-2 gap-4 mb-4">
    <p><strong>Tipo de entrega:</strong> 
        @if($pedidoSeleccionado->domicilio == 1)
            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                A Domicilio
            </span>
        @else
            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm font-medium">
                En Local
            </span>
        @endif
    </p>
</div>

                    
    {{-- AGREGAR AQUÍ LAS NOTAS --}}
    @if($pedidoSeleccionado->notas)
        <div class="sm:col-span-3">
            <p class="mb-1"><strong>Notas:</strong></p>
            <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                {{ $pedidoSeleccionado->notas }}
            </div>
        </div>
    @endif

                        {{-- Detalles del pedido (platillos) --}}
                        @if ($pedidoSeleccionado->detalles->count())
                            <div class="mb-4">
                                <h4 class="font-semibold mb-2">Platillos:</h4>
                                <table class="min-w-full border border-gray-200 text-sm">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="border px-2 py-1 text-left">Platillo</th>
                                            <th class="border px-2 py-1 text-center">Cantidad</th>
                                            <th class="border px-2 py-1 text-right">Precio Unitario</th>
                                            <th class="border px-2 py-1 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pedidoSeleccionado->detalles as $detalle)
                                            <tr>
                                                <td class="border px-2 py-1">{{ $detalle->platillo->nombre }}</td>
                                                <td class="border px-2 py-1 text-center">{{ $detalle->cantidad }}</td>
                                                <td class="border px-2 py-1 text-right">LPS.
                                                    {{ number_format($detalle->precio_unitario, 2) }}</td>
                                                <td class="border px-2 py-1 text-right">LPS.
                                                    {{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif


                    </div>

                    {{-- Timeline de estados --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-center overflow-x-auto py-8">
                            <div class="flex items-center relative">
                                @foreach ($estados as $index => $estado)
                                                    <div class="flex flex-col items-center relative">
                                                        {{-- Icono con animación --}}
                                                        <div
                                                            class="w-12 h-12 flex items-center justify-center rounded-full
                                                                                                                                                                                                                    {{ ($index < array_search($pedidoSeleccionado->estado, $estados)) ? 'bg-green-500 text-white' :
                                    ($pedidoSeleccionado->estado == $estado ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-700') }}
                                                                                                                                                                                                                    z-10 transition-all duration-300 transform 
                                                                                                                                                                                                                    {{ $pedidoSeleccionado->estado == $estado ? 'animate-pulse' : '' }}">
                                                        </div>

                                                        {{-- Nombre del estado --}}
                                                        <span class="mt-2 text-xs text-center w-24">{{ ucfirst($estado) }}</span>


                                                    </div>
                                @endforeach
                            </div>
                        </div>




                    </div>

              {{-- Formulario para cambiar estado --}}
@if ($tab == 'hoy' && $pedidoSeleccionado->estado !== 'cancelado' && $pedidoSeleccionado->estado !== 'entregado')
    {{-- Mostrar botón diferente según el tipo de entrega --}}
    @if($pedidoSeleccionado->domicilio == 1)
        {{-- Pedido a domicilio: Botón "Despachado" --}}
        <form method="POST" action="{{ route('cocina.pedidos.actualizarEstado.cocina', $pedidoSeleccionado->id) }}">
            @csrf
            <input type="hidden" name="nuevo_estado" value="despachado">
            
            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Despachado (A Domicilio)
                </button>
            </div>
        </form>
    @else
        {{-- Pedido en local: Botón "Entregado" --}}
        <form method="POST" action="{{ route('cocina.pedidos.actualizarEstado.cocina', $pedidoSeleccionado->id) }}">
            @csrf
            <input type="hidden" name="nuevo_estado" value="entregado">
            
            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Entregado (En Local)
                </button>
            </div>
        </form>
    @endif
@else
    {{-- Modo solo lectura: futuro, pasado o cancelado --}}
    <div class="mt-4">
        <p><strong>Estado actual:</strong> {{ ucfirst($pedidoSeleccionado->estado) }}</p>
        <p><strong>Tipo de entrega:</strong> {{ $pedidoSeleccionado->domicilio == 1 ? 'A Domicilio' : 'En Local' }}</p>

        @if ($pedidoSeleccionado->estado === 'cancelado')
            <div class="mt-2 text-red-600 font-semibold">
                Este pedido ha sido cancelado. No se puede editar.
            </div>
        @endif
    </div>
@endif


                </div>
            </div>
        @endif
        </div>
    </div>
    <script>
        function recargarDatos() {
            // Obtener los parámetros actuales de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentUrl = window.location.pathname + window.location.search;
            
            fetch(currentUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Crear un documento temporal para extraer solo el contenido necesario
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Actualizar solo la tabla de pedidos
                const nuevaTabla = doc.querySelector('#tabla-pedidos');
                if (nuevaTabla) {
                    document.querySelector('#tabla-pedidos').innerHTML = nuevaTabla.innerHTML;
                }
                
                // Actualizar el detalle del pedido si existe
                const nuevoDetalle = doc.querySelector('.bg-white.overflow-hidden.shadow-sm.sm\\:rounded-lg:last-child');
                const detalleActual = document.querySelector('.bg-white.overflow-hidden.shadow-sm.sm\\:rounded-lg:last-child');
                if (nuevoDetalle && detalleActual) {
                    detalleActual.innerHTML = nuevoDetalle.innerHTML;
                }
            })
            .catch(error => {
                console.log('Error al recargar:', error);
            });
        }
        
        // Recargar cada 5 segundos
        setInterval(recargarDatos, 5000);
    </script>
</x-app-layout>