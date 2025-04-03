<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Card de bienvenida -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                {{ __('Bienvenido de vuelta') }}, {{ Auth::user()->name }}
            </div>
        </div>

        <!-- Card del menú del día -->
        <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-xl font-semibold mb-4">Menú del día</h3>

                @if (empty($menu))
                    <p>No hay menú disponible para hoy.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($menu as $item)
                            <div class="p-4 bg-gray-100 rounded-lg">
                                <h4 class="text-lg font-semibold">{{ $item->nombre }}</h4>
                                <p>{{ $item->descripcion }}</p>
                                <p><strong>Precio: </strong>{{ $item->precio_base }} LPS</p>
                                <p><strong>Cantidad disponible: </strong>{{ $item->cantidad_disponible }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Botón para Crear/Editar Menú (Solo visible para administradores) -->
                @can('admin')
                    <div class="mt-4">
                        <a href="#" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Crear/Editar Menú del Día
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</div>


</x-app-layout>