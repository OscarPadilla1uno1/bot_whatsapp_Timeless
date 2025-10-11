<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configuración del Bot') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Mensajes de estado -->
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            
            @if($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Error en el formulario. Por favor, verifica los datos.</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Formulario de Configuración -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">Configurar Horario del Bot</h3>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('configuracion.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <!-- Switch de activación -->
                                <div class="mb-6">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="activo" 
                                               name="activo" 
                                               value="1" 
                                               {{ $configuracion->activo ? 'checked' : '' }}
                                               class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                                        <span class="ml-3 text-sm font-medium text-gray-700">Bot activo</span>
                                    </label>
                                </div>

                                <!-- Horarios -->
                                <div class="mb-6">
                                    <h4 class="text-md font-semibold text-gray-700 mb-3">Horario de actividad</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="hora_inicio" class="block text-sm font-medium text-gray-700 mb-2">Hora de inicio</label>
                                            <input type="time" 
                                                   id="hora_inicio" 
                                                   name="hora_inicio" 
                                                   value="{{ old('hora_inicio', $configuracion->hora_inicio->format('H:i')) }}" 
                                                   required
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label for="hora_fin" class="block text-sm font-medium text-gray-700 mb-2">Hora de fin</label>
                                            <input type="time" 
                                                   id="hora_fin" 
                                                   name="hora_fin" 
                                                   value="{{ old('hora_fin', $configuracion->hora_fin->format('H:i')) }}" 
                                                   required
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Días de la semana -->
                                <div class="mb-6">
                                    <h4 class="text-md font-semibold text-gray-700 mb-3">Días de actividad</h4>
                                    @php
                                        $diasSeleccionados = explode(',', $configuracion->dias_semana);
                                        $dias = [
                                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 
                                            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
                                        ];
                                    @endphp
                                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2">
                                        @foreach($dias as $numero => $nombre)
                                            <div class="day-checkbox">
                                                <input type="checkbox" 
                                                       id="dia_{{ $numero }}" 
                                                       name="dias_semana[]" 
                                                       value="{{ $numero }}" 
                                                       {{ in_array((string)$numero, $diasSeleccionados) ? 'checked' : '' }}
                                                       class="sr-only">
                                                <label for="dia_{{ $numero }}" 
                                                       class="block w-full px-3 py-2 text-center text-sm font-medium border border-gray-300 rounded-md cursor-pointer transition-all duration-200 hover:bg-gray-50 day-label">
                                                    {{ $nombre }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" 
                                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-6 rounded-md font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-200">
                                        Guardar Configuración
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- API Section -->
                    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">API para Builder Bot</h3>
                        </div>
                        <div class="p-6">
                            <div class="bg-gray-50 border-l-4 border-blue-500 p-4 mb-4">
                                <p class="text-sm text-gray-700 mb-3">Usa estas APIs en tu Builder Bot para verificar la disponibilidad:</p>
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <code class="bg-gray-800 text-green-400 px-2 py-1 rounded text-xs">GET /api/bot/configuracion</code>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Obtiene configuración completa</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <code class="bg-gray-800 text-green-400 px-2 py-1 rounded text-xs">GET /api/bot/activo</code>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Verifica si el bot está activo</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-md font-semibold text-gray-700 mb-3">Probar API:</h4>
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <button onclick="testApi('configuracion')" 
                                            class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700 transition-colors">
                                        Probar /configuracion
                                    </button>
                                    <button onclick="testApi('activo')" 
                                            class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 transition-colors">
                                        Probar /activo
                                    </button>
                                </div>
                                <div class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-xs min-h-24 overflow-auto" 
                                     id="apiResult">
                                    Resultado de la API aparecerá aquí...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Estado -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">Estado Actual</h3>
                        </div>
                        <div class="p-6">
                            <!-- Reloj y estado -->
                            <div class="bg-gradient-to-br from-indigo-600 to-blue-600 text-white rounded-lg p-4 text-center mb-4">
                                <div class="text-2xl font-bold" id="currentTime">20:45:32</div>
                                <div class="text-sm opacity-90" id="currentDate">Lunes, 12 de Junio de 2023</div>
                                <div class="mt-3">
                                    <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold bg-white/20" id="globalStatus">
                                        {{ $configuracion->activo ? 'DISPONIBLE' : 'NO DISPONIBLE' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Resumen de configuración -->
                            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-700 mb-3">Configuración actual:</h4>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Estado:</span>
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $configuracion->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $configuracion->activo ? 'ACTIVO' : 'INACTIVO' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Horario:</span>
                                    <span class="text-sm font-medium">
                                        {{ $configuracion->hora_inicio->format('H:i') }} - {{ $configuracion->hora_fin->format('H:i') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Días activos:</span>
                                    <div class="text-sm font-medium mt-1">
                                        @php
                                            $diasNombres = array_map(function($dia) use ($dias) {
                                                return $dias[$dia] ?? '';
                                            }, array_map('intval', $diasSeleccionados));
                                        @endphp
                                        {{ implode(', ', $diasNombres) }}
                                    </div>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Última actualización:</span>
                                    <span class="text-sm">{{ $configuracion->updated_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>

                            <!-- Sección del QR -->
                            <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg p-4 mt-4">
                                <h4 class="font-semibold text-white mb-3 text-center">Código QR de WhatsApp</h4>
                                <div class="bg-white rounded-lg p-3 flex items-center justify-center">
                                    <div id="qr-container" class="relative">
                                        <img id="qr-image" 
                                             src="{{ route('bot.qr') }}?t={{ time() }}" 
                                             alt="QR Code" 
                                             class="w-full h-auto max-w-[250px] rounded-lg"
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect width=%22200%22 height=%22200%22 fill=%22%23f3f4f6%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22sans-serif%22 font-size=%2214%22 fill=%22%236b7280%22%3EQR no disponible%3C/text%3E%3C/svg%3E'">
                                        <div id="qr-loading" class="hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
                                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-white text-center mt-3 opacity-90">
                                    Escanea con WhatsApp para conectar el bot
                                </p>
                                <p class="text-xs text-white text-center mt-1 opacity-75" id="qr-countdown">
                                    Actualizando en 10s
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Estilos para los checkboxes de días */
        .day-checkbox input[type="checkbox"]:checked + .day-label {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .day-checkbox input[type="checkbox"]:checked + .day-label:hover {
            background-color: #2563eb;
        }

        /* Animación suave para el cambio de QR */
        #qr-image {
            transition: opacity 0.3s ease-in-out;
        }
        
        #qr-image.loading {
            opacity: 0.5;
        }
    </style>

    <script>
        // Actualizar reloj en tiempo real
        function updateClock() {
            const now = new Date();
            const timeElem = document.getElementById('currentTime');
            const dateElem = document.getElementById('currentDate');
            const statusElem = document.getElementById('globalStatus');
            
            if (timeElem && dateElem) {
                const timeString = now.toLocaleTimeString('es-ES');
                const dateString = now.toLocaleDateString('es-ES', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                timeElem.textContent = timeString;
                dateElem.textContent = dateString.charAt(0).toUpperCase() + dateString.slice(1);
                
                // Lógica para actualizar estado en tiempo real
                const activoCheckbox = document.getElementById('activo');
                const horaInicio = document.getElementById('hora_inicio');
                const horaFin = document.getElementById('hora_fin');
                
                if (activoCheckbox && horaInicio && horaFin && statusElem) {
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    const currentTime = `${hours}:${minutes}`;
                    
                    const isActiveTime = currentTime >= horaInicio.value && currentTime <= horaFin.value;
                    
                    // Verificar día de la semana
                    let dayOfWeek = now.getDay();
                    if (dayOfWeek === 0) dayOfWeek = 7;
                    
                    const dayCheckboxes = document.querySelectorAll('input[name="dias_semana[]"]');
                    let isActiveDay = false;
                    
                    dayCheckboxes.forEach(checkbox => {
                        if (checkbox.checked && parseInt(checkbox.value) === dayOfWeek) {
                            isActiveDay = true;
                        }
                    });
                    
                    const isActive = activoCheckbox.checked && isActiveTime && isActiveDay;
                    statusElem.textContent = isActive ? 'DISPONIBLE' : 'NO DISPONIBLE';
                    statusElem.className = `inline-block px-4 py-2 rounded-full text-sm font-semibold bg-white/20 ${isActive ? 'text-green-100' : 'text-red-100'}`;
                }
            }
        }
        
        setInterval(updateClock, 1000);
        updateClock();
        
        // Actualizar QR cada 10 segundos
        let qrCountdown = 10;
        
        function updateQR() {
            const qrImage = document.getElementById('qr-image');
            const qrCountdownElem = document.getElementById('qr-countdown');
            
            if (qrImage) {
                // Agregar timestamp para evitar caché
                const timestamp = new Date().getTime();
                qrImage.src = "{{ route('bot.qr') }}?t=" + timestamp;
                
                // Reiniciar countdown
                qrCountdown = 10;
                if (qrCountdownElem) {
                    qrCountdownElem.textContent = 'Actualizando en 10s';
                }
            }
        }
        
        // Actualizar countdown cada segundo
        function updateCountdown() {
            const qrCountdownElem = document.getElementById('qr-countdown');
            
            if (qrCountdownElem && qrCountdown > 0) {
                qrCountdown--;
                qrCountdownElem.textContent = `Actualizando en ${qrCountdown}s`;
                
                if (qrCountdown === 0) {
                    updateQR();
                }
            }
        }
        
        // Iniciar actualizaciones
        setInterval(updateCountdown, 1000);
        setInterval(updateQR, 10000);
        
        // Función para probar APIs
        const apiRoutes = {
            configuracion: "{{ route('bot.configuracion') }}",
            activo: "{{ route('bot.activo') }}"
        };

        function testApi(endpoint) {
            const resultElem = document.getElementById('apiResult');
            if (resultElem) {
                resultElem.innerHTML = 'Probando API...';

                fetch(apiRoutes[endpoint])
                    .then(response => response.json())
                    .then(data => {
                        resultElem.innerHTML = JSON.stringify(data, null, 2);
                    })
                    .catch(error => {
                        resultElem.innerHTML = `Error: ${error.message}`;
                    });
            }
        }
    </script>
</x-app-layout>