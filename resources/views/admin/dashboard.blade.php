<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inicio') }}
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

        </div>
    </div>


</x-app-layout>