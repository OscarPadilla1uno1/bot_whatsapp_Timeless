<x-guest-layout>
    <div class="py-2">
        <div
            class="flex bg-white rounded-lg shadow-lg border border-2 border-gray-400 overflow-hidden mx-auto max-w-6xl lg:max-w-5xl lg:flex h-auto my-8">

            <!-- Formulario -->
            <div class="w-full lg:w-1/2 p-8">
                <img src="{{ asset('images/logo.jpeg') }}" alt="La Campaña Food Service Logo" class="mx-auto"
                    style="width: 170px; height: 170px; border-radius: 25%;">


                <p class="text-xl text-gray-600 text-center pt-4">Bienvenido</p>
                <div class="mx-4 mt-2">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="p-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Correo</label>
                            <input name="email"
                                class="bg-gray-200 text-gray-700 focus:outline-none focus:shadow-outline border border-gray-300 rounded py-3 px-4 block w-full appearance-none"
                                type="email" required autofocus>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña</label>
                                <a href="{{ route('password.request') }}" class="text-xs text-gray-500">¿Olvidaste tu
                                    contraseña?</a>
                            </div>
                            <input name="password"
                                class="bg-gray-200 text-gray-700 focus:outline-none focus:shadow-outline border border-gray-300 rounded py-3 px-4 block w-full appearance-none"
                                type="password" required>
                        </div>
                        <div class="p-6 flex items-center">
                            <input id="remember" name="remember" type="checkbox"
                                class="form-checkbox h-4 w-4 text-gray-600">
                            <label for="remember" class="ml-2 text-gray-700 text-sm">Recuerdame</label>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-center">
                                <button
                                    class="bg-blue-800 text-white text-lg font-bold py-4 px-8 rounded-lg hover:bg-blue-500 w-full transition duration-300 shadow-md">
                                    Ingresar
                                </button>
                            </div>
                        </div>
                    </form>
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mx-4 my-2"
                            role="alert">
                            <strong class="font-bold">Error:</strong>
                            <span class="block sm:inline">{{ $errors->first('email') }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <!-- Sección de la imagen con fondo - oculta en móviles -->
            <div class="hidden lg:block w-full lg:w-1/2">
                <div class="w-full h-full min-h-[700px]"
                    style="background-image: url('https://cdn.pixabay.com/photo/2016/12/26/17/28/spaghetti-1932466_1280.jpg'); background-size: cover; background-position: center;">
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>