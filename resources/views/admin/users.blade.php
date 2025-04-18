<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de usuarios') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Primera fila: tabla de usuarios y permisos --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Tabla de usuarios --}}
                <div class="col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Usuarios actuales</h3>
                        {{-- Aquí va la tabla de usuarios --}}
                        <table class="min-w-full text-sm text-left text-gray-600">
                            <thead class="bg-gray-100 font-bold">
                                <tr>
                                    <th class="px-4 py-2">Nombre</th>
                                    <th class="px-4 py-2">Correo</th>
                                    <th class="px-4 py-2">Permisos</th>
                                    <th class="px-4 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($usuarios as $usuario)
                                    <tr class="border-b">
                                        <td class="px-4 py-2">{{ $usuario->name }}</td>
                                        <td class="px-4 py-2">{{ $usuario->email }}</td>
                                        <td class="px-4 py-2">{{ $usuario->permisos }}</td>
                                        <td class="px-4 py-2 space-x-2">
                                            <button
                                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Editar</button>
                                            <button
                                                class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">Eliminar</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Permisos del usuario seleccionado --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Permisos del usuario</h3>
                        {{-- Aquí mostrás dinámicamente los permisos del usuario seleccionado --}}
                        <ul class="list-disc pl-5 text-sm text-gray-700">
                            <li>Ver usuarios</li>
                            <li>Editar usuarios</li>
                            <li>Eliminar usuarios</li>
                            {{-- … --}}
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Segunda fila: formulario para agregar usuario --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Agregar nuevo usuario</h3>

                    <form id="form-crear-usuario" action="{{ route('admin.users.create') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" name="name" id="name" required
                                    class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Correo
                                    electrónico</label>
                                <input type="email" name="email" id="email" required
                                    class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                                <input type="password" name="password" id="password" required
                                    class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar
                                contraseña</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        </div>

                        {{-- Selección de permisos --}}
                        <div class="mb-4">
                            <label for="permisos" class="block text-sm font-medium text-gray-700 mb-1">Permisos</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach ($permisos as $permiso)
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="permiso" value="{{ $permiso->name }}"
                                            class="rounded text-orange-500 focus:ring-orange-400">
                                        <span class="ml-2 text-sm">{{ $permiso->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>


                        <button type="submit"
                            class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded">
                            Crear usuario
                        </button>
                        </form>

                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            registrarNuevoUsuario('form-crear-usuario', '/admin/users/create');
        });

    </script>

</x-app-layout>