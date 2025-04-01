<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">
        <div class="flex items-center justify-center min-h-screen bg-red-900">

            <!-- Contenedor principal con margen superior ajustado -->
            <div class="w-full max-w-6xl px-4 py-4 "> <!-- Cambié la altura aquí -->

                <!-- Contenedor del formulario con bordes y sombra -->
                <div class="flex flex-col sm:flex-row w-full max-w-full max-h-full bg-white shadow-2xl border border-gray-300 rounded-lg overflow-hidden mx-auto">

                    <!-- Sección del formulario -->
                    <div class="w-full p-6 bg-white">
                        {{ $slot }}
                    </div>
                </div>

            </div>
        </div>
    </body>
</html>
