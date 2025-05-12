<div>
    {{-- Success is as dangerous as failure. --}}
    <div class="space-y-6">

    {{-- Mensajes de éxito o error --}}
    @if (session()->has('success'))
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Card 1: Tabla de pedidos --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <h3 class="text-lg font-semibold mb-4">Lista de Pedidos</h3>

            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Cliente</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2">Total</th>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pedidos as $pedido)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $pedido->id }}</td>
                            <td class="px-4 py-2">{{ $pedido->cliente->nombre }}</td>
                            <td class="px-4 py-2">{{ ucfirst($pedido->estado) }}</td>
                            <td class="px-4 py-2">${{ number_format($pedido->total, 2) }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($pedido->fecha_pedido)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <button wire:click="seleccionarPedido({{ $pedido->id }})" class="text-blue-500 hover:underline">
                                    Ver Detalle
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $pedidos->links() }}
            </div>
        </div>
    </div>

    {{-- Card 2: Detalle del pedido seleccionado --}}
    @if ($pedidoSeleccionado)
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-semibold mb-4">Detalle del Pedido #{{ $pedidoSeleccionado->id }}</h3>

                {{-- Información básica --}}
                <div class="mb-4">
                    <p><strong>Cliente:</strong> {{ $pedidoSeleccionado->cliente->nombre }}</p>
                    <p><strong>Teléfono:</strong> {{ $pedidoSeleccionado->cliente->telefono }}</p>
                    <p><strong>Total:</strong> ${{ number_format($pedidoSeleccionado->total, 2) }}</p>
                </div>

                {{-- Timeline de estados --}}
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Historial de Estado:</h4>
                    <ul class="space-y-2">
                        @foreach ($estados as $estado)
                            <li class="flex items-center">
                                <div class="w-4 h-4 rounded-full {{ $pedidoSeleccionado->estado == $estado ? 'bg-green-500' : 'bg-gray-300' }} mr-2"></div>
                                <span>{{ ucfirst($estado) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Formulario para cambiar estado --}}
                <form wire:submit.prevent="actualizarEstado">
                    <div class="flex items-center space-x-4">
                        <select wire:model="nuevoEstado" class="border rounded p-2">
                            <option value="">Seleccionar nuevo estado</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}">{{ ucfirst($estado) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Actualizar Estado
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif

</div>

