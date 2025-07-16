<!DOCTYPE html>
<html>
<head>
    <title>Sistema de Entregas Avanzado - {{ $driver->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <!-- Leaflet GPS CSS (usando CDN más confiable) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-gps@1.7.0/dist/leaflet-gps.min.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        #map {
            height: 100vh;
            width: 100%;
        }

        .driver-panel {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            max-width: 380px;
            min-width: 300px;
            font-family: Arial, sans-serif;
            transition: all 0.3s ease;
        }

        .panel-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .toggle-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .panel-content {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-online {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }

        .status-offline {
            background-color: #dc3545;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .driver-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .driver-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }

        .route-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .route-controls button {
            padding: 12px 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #1e7e34;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .delivery-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
        }

        .route-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .route-info h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }

        .delivery-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .delivery-item {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delivery-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 10px;
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
        }

        .status-completed {
            background-color: #28a745;
        }

        .status-current {
            background-color: #007bff;
        }

        .legend {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .legend h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin: 5px 0;
            font-size: 12px;
        }

        .legend-color {
            width: 20px;
            height: 3px;
            margin-right: 8px;
            border-radius: 2px;
        }

        #error-message {
            color: #dc3545;
            margin-top: 10px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            display: none;
        }

        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .no-route-message {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }

        /* Responsive para móviles */
        @media (max-width: 768px) {
            .driver-panel {
                left: 5px;
                right: 5px;
                max-width: none;
                min-width: auto;
            }

            .panel-content {
                max-height: 400px;
            }

            .panel-content.collapsed {
                display: none;
            }

            .toggle-icon.collapsed {
                transform: rotate(180deg);
            }

            .route-controls {
                grid-template-columns: 1fr;
            }

            .delivery-stats {
                grid-template-columns: 1fr;
            }
        }

        /* Estilos para desktop */
        @media (min-width: 769px) {
            .panel-content {
                display: block !important;
            }

            .toggle-icon {
                display: none;
            }
        }

        /* Estilos para el control GPS */
        .leaflet-gps-control {
            background: #fff;
            border: 2px solid rgba(0,0,0,0.2);
            border-radius: 4px;
        }

        .leaflet-gps-control.active {
            background: #007bff;
            color: white;
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <div class="driver-panel">
        <div class="panel-header" onclick="togglePanel()">
            <h3>
                <span class="status-indicator status-online" id="status-indicator"></span>
                Motorista - {{ $driver->name }}
            </h3>
            <span class="toggle-icon" id="toggle-icon">▼</span>
        </div>
        
        <div class="panel-content" id="panel-content">
            <div class="driver-info">
                <p><strong>Email:</strong> {{ $driver->email }}</p>
                @if($route)
                    <p><strong>Vehículo:</strong> #{{ $route['vehicle'] }}</p>
                @endif
                <p><strong>Estado:</strong> <span id="connection-status">Conectado</span></p>
            </div>

            @if($route)
                <div class="delivery-stats">
                    <div class="stat-box">
                        <div class="stat-number" id="completed-count">0</div>
                        <div class="stat-label">Completadas</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="pending-count">{{ count($route['steps']) }}</div>
                        <div class="stat-label">Pendientes</div>
                    </div>
                </div>

                <div class="route-controls">
                    <button class="btn-primary" onclick="showOptimizedRoute()">
                        📍 Mostrar Ruta
                    </button>
                    <button class="btn-success" id="follow-button" onclick="toggleRealTimeTracking()">
                        🚀 Seguimiento
                    </button>
                    <button class="btn-success" id="navigation-button" onclick="toggleNavigation()">
                        🧭 Navegación
                    </button>
                    <button class="btn-warning" onclick="recalculateRoute()">
                        🔄 Recalcular
                    </button>
                    <button class="btn-danger" onclick="emergencyStop()">
                        ⚠️ Emergencia
                    </button>
                </div>

                <!-- Panel de navegación -->
                <div id="navigation-panel" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;">
                    <h4 style="margin: 0 0 10px 0; color: #007bff;">🧭 Navegación Activa</h4>
                    <div id="current-instruction" style="font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #333;">
                        Iniciando navegación...
                    </div>
                    <div id="distance-info" style="font-size: 14px; color: #666; margin-bottom: 10px;">
                        Distancia: Calculando...
                    </div>
                    <div id="next-turn" style="font-size: 14px; color: #666; margin-bottom: 10px;">
                        Próximo giro: Calculando...
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button onclick="toggleVoiceNavigation()" id="voice-button" style="padding: 5px 10px; border: none; border-radius: 4px; background: #28a745; color: white; cursor: pointer;">
                            🔊 Voz ON
                        </button>
                        <button onclick="centerOnLocation()" style="padding: 5px 10px; border: none; border-radius: 4px; background: #17a2b8; color: white; cursor: pointer;">
                            📍 Centrar
                        </button>
                    </div>
                </div>

                <div class="legend">
                    <h4>Leyenda del Mapa</h4>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #28a745;"></div>
                        <span>Ruta completada</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #007bff;"></div>
                        <span>Ruta de entregas</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ffc107;"></div>
                        <span>Próxima entrega</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #4285F4;"></div>
                        <span>Tu ubicación</span>
                    </div>
                </div>

                <div class="route-info">
                    <h4>Lista de Entregas</h4>
                    <div class="delivery-list" id="delivery-list">
                        @if($route && isset($route['steps']) && is_array($route['steps']))
                            @foreach($route['steps'] as $index => $step)
                                @if(isset($step['type']) && $step['type'] === 'job' && isset($step['job_details']))
                                    <div class="delivery-item" data-delivery-id="{{ $step['job'] }}">
                                        <div>
                                            <strong>{{ $step['job_details']['cliente'] ?? 'Cliente desconocido' }}</strong>
                                            <br><small>Entrega #{{ $index + 1 }}</small>
                                        </div>
                                        <span class="delivery-status status-pending">Pendiente</span>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="delivery-item">
                                <em>No hay entregas asignadas</em>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="no-route-message">
                    <p>No hay ruta asignada actualmente.</p>
                </div>
            @endif

            <div id="error-message"></div>
        </div>
    </div>

    <div id="loading">
        <h2>Cargando sistema avanzado...</h2>
    </div>

    <!-- Scripts - Orden corregido y URLs verificadas -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>
    
    <!-- Leaflet Routing Machine - Versión específica con todas las dependencias -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <!-- Leaflet Realtime -->
    <script src="https://unpkg.com/leaflet-realtime@2.2.0/dist/leaflet-realtime.js"></script>
    
    <!-- Leaflet GPS - Versión GitHub directa -->
    <script src="https://cdn.jsdelivr.net/gh/stefanocudini/leaflet-gps@master/dist/leaflet-gps.min.js"></script>
    
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Script para verificar carga de plugins con timeouts -->
    <script>
        // Función para verificar plugins con timeout
        function checkPluginsAvailability() {
            const checks = {
                'Leaflet': typeof L !== 'undefined',
                'Polyline': typeof polyline !== 'undefined',
                'Routing Machine': typeof L !== 'undefined' && typeof L.Routing !== 'undefined',
                'Routing OSRM': typeof L !== 'undefined' && typeof L.Routing !== 'undefined' && typeof L.Routing.osrm !== 'undefined',
                'Realtime': typeof L !== 'undefined' && typeof L.realtime !== 'undefined',
                'GPS': typeof L !== 'undefined' && typeof L.Control !== 'undefined' && typeof L.Control.Gps !== 'undefined',
                'Axios': typeof axios !== 'undefined'
            };

            console.log('Estado de plugins:');
            Object.entries(checks).forEach(([name, available]) => {
                console.log(`${name}: ${available ? '✅' : '❌'}`);
            });

            return checks;
        }

        // Verificar cada segundo hasta que estén disponibles (max 10 segundos)
        let checkCount = 0;
        const maxChecks = 10;

        function waitForPlugins() {
            checkCount++;
            const plugins = checkPluginsAvailability();
            
            if (plugins['Routing OSRM'] || checkCount >= maxChecks) {
                console.log(checkCount >= maxChecks ? 
                    '⚠️ Timeout esperando plugins - usando fallback' : 
                    '✅ Plugins listos');
                
                // Inicializar aplicación directamente aquí
                initializeAppDirect();
                return;
            }
            
            setTimeout(waitForPlugins, 1000);
        }

        // Función de inicialización directa
        function initializeAppDirect() {
            console.log('🚀 Inicializando aplicación directamente...');
            
            // Verificar qué plugins están disponibles
            const hasRouting = typeof L !== 'undefined' && 
                              typeof L.Routing !== 'undefined' && 
                              typeof L.Routing.osrm !== 'undefined';
            
            console.log('Leaflet Routing Machine disponible:', hasRouting);
            
            if (!hasRouting) {
                console.warn('⚠️ Leaflet Routing Machine no disponible, usando solo polylines');
                // Deshabilitar botón de navegación si no hay routing
                const navButton = document.getElementById('navigation-button');
                if (navButton) {
                    navButton.disabled = true;
                    navButton.textContent = '🧭 No disponible';
                    navButton.style.opacity = '0.5';
                }
            }

            // Inicializar seguimiento en tiempo real
            if (driverRoute) {
                initializeRealTimeTracking();
            }

            // Inicializar GPS
            initializeGPS();

            // Probar conectividad OSRM
            testOSRMConnectivity();

            // Mostrar ruta inicial
            if (driverRoute) {
                setTimeout(() => {
                    console.log('📍 Mostrando ruta inicial...');
                    showOptimizedRoute();
                }, 1000);
            } else {
                // Ocultar loading si no hay ruta
                document.getElementById('loading').style.display = 'none';
            }

            // IMPORTANTE: Siempre ocultar loading después de inicializar
            setTimeout(() => {
                document.getElementById('loading').style.display = 'none';
                console.log('✅ Aplicación inicializada completamente');
            }, 2000);
        }

        // Iniciar verificación después de cargar el DOM
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔄 DOM cargado, iniciando verificación de plugins...');
            setTimeout(waitForPlugins, 500);
        });

        // Fallback adicional por si DOMContentLoaded no funciona
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(waitForPlugins, 500);
            });
        } else {
            // DOM ya está cargado
            setTimeout(waitForPlugins, 500);
        }
    </script>

    <script>
        document.getElementById('loading').style.display = 'flex';

        // Datos del motorista y su ruta
        const driverRoute = @json($route);
        const jobsData = @json($jobs);

        // Inicializar mapa
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Variables globales para navegación en tiempo real
        let routingControl = null;
        let realtimeLayer = null;
        let gpsControl = null;
        let currentMarkers = [];
        let isRealTimeActive = false;
        let deliveryStatus = {};
        let currentDeliveryIndex = 0;
        let watchId = null;
        let currentLocationMarker = null;
        
        // Variables para navegación turn-by-turn
        let isNavigating = false;
        let navigationRoute = null;
        let routeCoordinates = [];
        let completedPath = [];
        let remainingPath = [];
        let currentRouteIndex = 0;
        let navigationInterval = null;
        let currentStepIndex = 0;
        let routeSteps = [];
        let completedPolyline = null;
        let remainingPolyline = null;
        let nextTurnMarker = null;
        let voiceEnabled = true;

        // Función para inicializar GPS de forma segura
        function initializeGPS() {
            try {
                // Verificar si el plugin GPS está disponible
                if (typeof L.Control.Gps !== 'undefined') {
                    gpsControl = new L.Control.Gps({
                        autoStart: false,
                        transform: function(gpsData) {
                            return gpsData;
                        }
                    });
                    map.addControl(gpsControl);
                    console.log('GPS Control inicializado correctamente');
                } else {
                    console.warn('Plugin GPS no disponible, usando geolocalización nativa');
                    gpsControl = null;
                }
            } catch (error) {
                console.error('Error inicializando GPS:', error);
                gpsControl = null;
            }
        }

        // Inicializar GPS cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            initializeGPS();
        });

        // Función de geolocalización nativa como fallback
        function startNativeGeolocation() {
            if (!navigator.geolocation) {
                showError('Geolocalización no soportada por este navegador');
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };

            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    updateCurrentLocation(position.coords.latitude, position.coords.longitude);
                },
                function(error) {
                    console.error('Error de geolocalización:', error);
                    showError('Error obteniendo ubicación: ' + error.message);
                },
                options
            );
        }

        function stopNativeGeolocation() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        }

        function updateCurrentLocation(lat, lng) {
            // Actualizar o crear marcador de ubicación actual
            if (currentLocationMarker) {
                currentLocationMarker.setLatLng([lat, lng]);
            } else {
                currentLocationMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'current-location-marker',
                        html: '<div style="background-color: #4285F4; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); animation: pulse 1s infinite;"></div>',
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    })
                }).addTo(map);
                
                currentLocationMarker.bindPopup("Tu ubicación actual").openPopup();
            }
            
            // Centrar mapa en la ubicación actual solo si estamos navegando
            if (isNavigating) {
                map.setView([lat, lng], 18);
            }
        }

        // Inicializar seguimiento en tiempo real
        if (driverRoute) {
            initializeRealTimeTracking();
        }

        // Función para toggle del panel en móviles
        function togglePanel() {
            if (window.innerWidth <= 768) {
                const content = document.getElementById('panel-content');
                const icon = document.getElementById('toggle-icon');
                
                if (content.classList.contains('collapsed')) {
                    content.classList.remove('collapsed');
                    icon.classList.remove('collapsed');
                    icon.textContent = '▼';
                } else {
                    content.classList.add('collapsed');
                    icon.classList.add('collapsed');
                    icon.textContent = '▲';
                }
            }
        }

        // Función para mostrar error
        function showError(message) {
            const errorEl = document.getElementById('error-message');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 5000);
        }

        // Funciones utilitarias (deben estar antes de ser usadas)
        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // Función para encontrar índice del punto más cercano
        function findClosestPointIndex(points, lat, lng) {
            let closestIndex = 0;
            let minDistance = Infinity;
            
            points.forEach((point, index) => {
                const distance = getDistanceFromLatLonInKm(lat, lng, point[0], point[1]);
                if (distance < minDistance) {
                    minDistance = distance;
                    closestIndex = index;
                }
            });
            
            return closestIndex;
        }

        // Función para centrar en ubicación
        function centerOnLocation() {
            if (currentLocationMarker) {
                map.setView(currentLocationMarker.getLatLng(), 18);
            } else {
                showError('No se ha establecido la ubicación actual');
            }
        }

        // Función para probar conectividad con servidor OSRM
        function testOSRMConnectivity() {
            const osrmUrl = 'http://154.38.191.25:5000';
            
            // Prueba simple con coordenadas de Honduras
            const testUrl = `${osrmUrl}/route/v1/driving/-87.2065,14.0821;-87.1875,14.0667?overview=full&geometries=polyline`;
            
            console.log('Probando conectividad con servidor OSRM propio...');
            
            fetch(testUrl)
                .then(response => {
                    if (response.ok) {
                        console.log('✅ Servidor OSRM propio conectado correctamente');
                        updateConnectionStatus(true);
                        return response.json();
                    } else {
                        console.warn('⚠️ Servidor OSRM propio respondió con error:', response.status);
                        updateConnectionStatus(false);
                    }
                })
                .then(data => {
                    if (data && data.routes && data.routes.length > 0) {
                        console.log('🗺️ Servidor OSRM funcionando correctamente');
                        console.log('Rutas disponibles:', data.routes.length);
                    }
                })
                .catch(error => {
                    console.error('❌ Error conectando con servidor OSRM propio:', error);
                    console.log('Usaremos servidores de respaldo automáticamente');
                    updateConnectionStatus(false);
                });
        }

        // Función mejorada para mostrar ruta optimizada
        function showOptimizedRoute() {
            if (!driverRoute || !driverRoute.geometry) {
                showError('No hay ruta disponible');
                return;
            }

            try {
                // Limpiar ruta anterior
                if (routingControl) {
                    map.removeControl(routingControl);
                }

                // Verificar disponibilidad completa de Leaflet Routing Machine
                if (typeof L.Routing === 'undefined' || 
                    typeof L.Routing.control === 'undefined' || 
                    typeof L.Routing.osrm === 'undefined') {
                    console.warn('Leaflet Routing Machine no completamente disponible, usando ruta directa');
                    showRouteWithPolyline();
                    return;
                }

                // Crear waypoints desde los steps
                const waypoints = [];
                driverRoute.steps.forEach(step => {
                    if (step.location) {
                        waypoints.push(L.latLng(step.location[1], step.location[0]));
                    }
                });

                if (waypoints.length === 0) {
                    showError('No hay waypoints válidos en la ruta');
                    return;
                }

                // Usar directamente tu servidor OSRM (más simple y confiable)
                console.log('Usando servidor OSRM propio: http://154.38.191.25:5000');
                
                const router = L.Routing.osrm({
                    serviceUrl: 'http://154.38.191.25:5000/route/v1',
                    timeout: 20000,
                    profile: 'driving'
                });

                // Crear control de routing
                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: false,
                    addWaypoints: false,
                    show: false, // No mostrar panel de instrucciones
                    lineOptions: {
                        styles: [
                            {color: '#007bff', weight: 6, opacity: 0.8}
                        ]
                    },
                    createMarker: function(i, wp, nWps) {
                        return createCustomMarker(i, wp, nWps);
                    },
                    router: router
                });

                // Manejar eventos de routing
                routingControl.on('routingerror', function(e) {
                    console.error('Error de routing:', e.error);
                    showError('Error con servidor OSRM, usando ruta alternativa');
                    showRouteWithPolyline();
                });

                routingControl.on('routesfound', function(e) {
                    console.log('Rutas encontradas exitosamente');
                    updateConnectionStatus(true);
                    
                    // Guardar ruta para navegación
                    if (e.routes && e.routes.length > 0) {
                        navigationRoute = e.routes[0];
                        console.log('Ruta guardada para navegación:', navigationRoute);
                    }
                });

                // Agregar al mapa
                routingControl.addTo(map);

                // Ocultar error si todo está bien
                document.getElementById('error-message').style.display = 'none';

            } catch (error) {
                console.error('Error al mostrar ruta con Routing Machine:', error);
                console.log('Intentando con polyline directa...');
                showRouteWithPolyline();
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Función para crear marcadores personalizados
        function createCustomMarker(i, wp, nWps) {
            const step = driverRoute.steps[i];
            if (!step) return null;
            
            let markerColor = '#007bff';
            let markerIcon = '📍';
            
            if (step.type === 'start') {
                markerColor = '#28a745';
                markerIcon = '🏠';
            } else if (step.type === 'end') {
                markerColor = '#dc3545';
                markerIcon = '🏁';
            } else if (step.type === 'job') {
                markerColor = deliveryStatus[step.job] === 'completed' ? '#28a745' : '#ffc107';
                markerIcon = deliveryStatus[step.job] === 'completed' ? '✅' : '📦';
            }

            const marker = L.marker(wp.latLng, {
                icon: L.divIcon({
                    className: 'custom-delivery-marker',
                    html: `<div style="background-color: ${markerColor}; width: 30px; height: 30px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 16px;">${markerIcon}</div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            });

            // Agregar popup con información
            let popupContent = `<b>${driverRoute.driver_name}</b><br>`;
            popupContent += `<small>${step.type.toUpperCase()}</small>`;
            if (step.job_details) {
                popupContent += `<br>Cliente: ${step.job_details.cliente}`;
            }
            marker.bindPopup(popupContent);

            return marker;
        }

        // Función alternativa usando polyline directa
        function showRouteWithPolyline() {
            console.log('🗺️ Mostrando ruta con polyline directa');
            
            try {
                if (!driverRoute || !driverRoute.geometry) {
                    showError('No hay ruta disponible');
                    return;
                }

                // Limpiar marcadores anteriores
                currentMarkers.forEach(marker => {
                    if (map.hasLayer(marker)) {
                        map.removeLayer(marker);
                    }
                });
                currentMarkers = [];

                // Decodificar la polilínea
                const decoded = polyline.decode(driverRoute.geometry);
                const latlngs = decoded.map(p => L.latLng(p[0], p[1]));

                console.log('📍 Coordenadas decodificadas:', latlngs.length, 'puntos');

                // Dibujar solo ruta de ida (azul) - SIN RUTA DE REGRESO
                if (latlngs.length > 1) {
                    const routePolyline = L.polyline(latlngs, {
                        color: '#007bff',
                        weight: 6,
                        opacity: 0.8
                    });
                    routePolyline.addTo(map);
                    currentMarkers.push(routePolyline);
                    console.log('🔵 Ruta de entregas dibujada:', latlngs.length, 'puntos');
                }

                // Añadir marcadores para los pasos
                if (driverRoute.steps && Array.isArray(driverRoute.steps)) {
                    driverRoute.steps.forEach((step, index) => {
                        if (!step.location || !Array.isArray(step.location)) return;
                        
                        const [lng, lat] = step.location;
                        
                        // Diferentes iconos para diferentes tipos de pasos
                        let markerColor = '#007bff';
                        let markerIcon = '📍';
                        
                        if (step.type === 'start') {
                            markerColor = '#28a745';
                            markerIcon = '🏠';
                        } else if (step.type === 'end') {
                            markerColor = '#dc3545';
                            markerIcon = '🏁';
                        } else if (step.type === 'job') {
                            markerColor = '#ffc107';
                            markerIcon = '📦';
                        }

                        const marker = L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: `<div style="background-color: ${markerColor}; width: 30px; height: 30px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold;">${markerIcon}</div>`,
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        });
                        
                        marker.addTo(map);
                        currentMarkers.push(marker);

                        let popup = `<b>${driverRoute.driver_name}</b><br>`;
                        popup += `<small>${step.type.toUpperCase()}</small>`;
                        if (step.job_details) {
                            popup += `<br>Cliente: ${step.job_details.cliente}`;
                        }
                        popup += `<br><small>Parada ${index + 1}</small>`;

                        marker.bindPopup(popup);
                        
                        console.log(`📍 Marcador ${index + 1} agregado:`, step.type, 'en', [lat, lng]);
                    });
                }

                // Ajustar vista para mostrar toda la ruta - FORZAR BOUNDS
                if (latlngs.length > 0) {
                    const allBounds = new L.LatLngBounds();
                    latlngs.forEach(coord => allBounds.extend(coord));
                    
                    // Aplicar bounds con padding
                    map.fitBounds(allBounds, { 
                        padding: [50, 50],
                        maxZoom: 16
                    });
                    
                    console.log('🗺️ Mapa ajustado a bounds:', allBounds.toBBoxString());
                }

                // Habilitar navegación ya que tenemos ruta
                const navButton = document.getElementById('navigation-button');
                if (navButton) {
                    navButton.disabled = false;
                    navButton.textContent = '🧭 Navegación';
                    navButton.style.opacity = '1';
                    console.log('🧭 Botón de navegación habilitado');
                }

                // Ocultar error si todo está bien
                document.getElementById('error-message').style.display = 'none';
                
                console.log('✅ Ruta de entregas mostrada correctamente (sin regreso)');
                console.log('📊 Elementos en el mapa:', currentMarkers.length);

            } catch (error) {
                console.error('Error al mostrar ruta con polyline:', error);
                showError(`Error técnico: ${error.message}`);
            } finally {
                // Asegurar que el loading se oculte
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Inicializar seguimiento en tiempo real
        function initializeRealTimeTracking() {
            realtimeLayer = L.realtime({
                url: '{{ route("gps.data") }}',
                crossOrigin: true,
                type: 'json'
            }, {
                interval: 3000, // 3 segundos
                onEachFeature: function(feature, layer) {
                    const pulsingIcon = L.divIcon({
                        className: 'pulsing-marker',
                        html: '<div style="background-color: #4285F4; width: 15px; height: 15px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); animation: pulse 1s infinite;"></div>',
                        iconSize: [15, 15],
                        iconAnchor: [7, 7]
                    });
                    layer.setIcon(pulsingIcon);
                }
            });

            realtimeLayer.on('update', function(e) {
                updateConnectionStatus(true);
                // Actualizar estadísticas y estado
                updateDeliveryProgress();
            });

            realtimeLayer.on('error', function(e) {
                updateConnectionStatus(false);
                console.error('Error en realtime:', e);
            });
        }

        // Función para toggle de navegación en tiempo real
        function toggleNavigation() {
            const button = document.getElementById('navigation-button');
            const panel = document.getElementById('navigation-panel');
            
            if (isNavigating) {
                // Detener navegación
                stopNavigation();
                button.textContent = '🧭 Navegación';
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');
                panel.style.display = 'none';
            } else {
                // Iniciar navegación
                startNavigation();
                button.textContent = '⏹️ Detener Nav';
                button.classList.remove('btn-success');
                button.classList.add('btn-danger');
                panel.style.display = 'block';
            }
        }

        // Función para iniciar navegación turn-by-turn
        function startNavigation() {
            if (!driverRoute || !driverRoute.steps) {
                showError('No hay ruta disponible para navegación');
                return;
            }

            console.log('🧭 Iniciando navegación - LIMPIANDO MAPA PRIMERO');
            
            // 🧹 LIMPIAR COMPLETAMENTE EL MAPA ANTES DE INICIAR NAVEGACIÓN
            clearMapCompletely();
            
            isNavigating = true;
            
            // Obtener ubicación actual
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const currentLat = position.coords.latitude;
                    const currentLng = position.coords.longitude;
                    
                    console.log('📍 Navegación iniciada desde ubicación limpia:', currentLat, currentLng);
                    
                    // Calcular ruta desde ubicación actual (nueva funcionalidad)
                    calculateRouteFromCurrentLocation(currentLat, currentLng);
                    
                    // Iniciar seguimiento GPS continuo
                    startGPSTracking();
                    
                    updateNavigationDisplay();
                    
                }, function(error) {
                    showError('No se pudo obtener la ubicación para navegación');
                    stopNavigation();
                });
            } else {
                showError('Geolocalización no disponible');
                stopNavigation();
            }
        }

        // Función para limpiar COMPLETAMENTE el mapa
        function clearMapCompletely() {
            console.log('🧹 Limpiando mapa completamente para navegación...');
            
            // Limpiar todos los marcadores en currentMarkers
            currentMarkers.forEach(marker => {
                if (map.hasLayer(marker)) {
                    map.removeLayer(marker);
                }
            });
            currentMarkers = [];
            
            // Limpiar polylines de navegación
            if (completedPolyline) {
                map.removeLayer(completedPolyline);
                completedPolyline = null;
            }
            if (remainingPolyline) {
                map.removeLayer(remainingPolyline);
                remainingPolyline = null;
            }
            
            // Limpiar marcador de ubicación actual
            if (currentLocationMarker) {
                map.removeLayer(currentLocationMarker);
                currentLocationMarker = null;
            }
            
            // Limpiar todas las capas del mapa (excepto tiles)
            map.eachLayer(function(layer) {
                // Solo mantener la capa de tiles (mapa base)
                if (layer instanceof L.TileLayer) {
                    return; // Mantener tiles
                }
                // Remover todo lo demás
                map.removeLayer(layer);
            });
            
            // Reset de variables de navegación
            completedPath = [];
            remainingPath = [];
            currentRouteIndex = 0;
            currentStepIndex = 0;
            routeSteps = [];
            navigationRoute = null;
            
            console.log('✅ Mapa limpiado completamente - listo para navegación');
        }

        // Función para calcular ruta de navegación (fallback)
        function calculateNavigationRoute(currentLat, currentLng) {
            try {
                // Decodificar la ruta existente
                const decoded = polyline.decode(driverRoute.geometry);
                routeCoordinates = decoded.map(p => [p[0], p[1]]); // [lat, lng]
                
                // Encontrar el punto más cercano en la ruta
                const closestIndex = findClosestPointIndex(routeCoordinates, currentLat, currentLng);
                
                // Dividir la ruta en completada y restante
                completedPath = routeCoordinates.slice(0, closestIndex);
                remainingPath = routeCoordinates.slice(closestIndex);
                currentRouteIndex = closestIndex;
                
                // Procesar pasos de navegación
                processNavigationSteps();
                
                // Dibujar ruta inicial
                drawDynamicRoute();
                
                console.log('Navegación iniciada:', {
                    totalPoints: routeCoordinates.length,
                    completedPoints: completedPath.length,
                    remainingPoints: remainingPath.length
                });
                
            } catch (error) {
                console.error('Error calculando ruta de navegación:', error);
                showError('Error al calcular ruta de navegación');
            }
        }

        // Función para calcular ruta desde ubicación actual
        function calculateRouteFromCurrentLocation(currentLat, currentLng) {
            console.log('🧭 Calculando ruta SOLO desde ubicación actual:', currentLat, currentLng);
            
            // Obtener puntos de entrega restantes (NO incluir punto de inicio del vehículo)
            const deliveryPoints = [];
            
            if (driverRoute && driverRoute.steps) {
                driverRoute.steps.forEach(step => {
                    // SOLO incluir entregas (jobs), NO start ni end
                    if (step.type === 'job' && step.location) {
                        deliveryPoints.push({
                            lat: step.location[1],
                            lng: step.location[0],
                            cliente: step.job_details?.cliente || 'Cliente desconocido',
                            id: step.job || 'unknown'
                        });
                    }
                });
            }

            if (deliveryPoints.length === 0) {
                showError('No hay puntos de entrega disponibles');
                return;
            }

            console.log('📦 Entregas encontradas:', deliveryPoints.length);

            // Llamar a tu servidor para calcular ruta SOLO desde ubicación actual
            const requestData = {
                current_location: [currentLng, currentLat], // lng, lat para OSRM
                delivery_points: deliveryPoints
            };

            console.log('📤 Solicitud de ruta desde ubicación actual:', requestData);

            axios.post('/get-optimized-route', requestData)
                .then(response => {
                    if (response.data.success && response.data.route) {
                        console.log('✅ Ruta recalculada desde ubicación GPS');
                        
                        // Actualizar ruta global
                        navigationRoute = response.data.route;
                        
                        // Procesar nueva ruta LIMPIA (solo desde ubicación actual)
                        processNewCleanRoute(response.data.route, currentLat, currentLng);
                    } else {
                        console.warn('⚠️ Error en respuesta, usando fallback sin start');
                        createFallbackRouteFromLocation(currentLat, currentLng);
                    }
                })
                .catch(error => {
                    console.error('❌ Error calculando ruta desde servidor:', error);
                    console.log('🔄 Creando ruta fallback desde ubicación actual');
                    createFallbackRouteFromLocation(currentLat, currentLng);
                });
        }

        // Función para crear ruta fallback desde ubicación actual (sin start del vehículo)
        function createFallbackRouteFromLocation(currentLat, currentLng) {
            console.log('🔄 Creando ruta fallback LIMPIA desde ubicación actual');
            
            try {
                // Extraer solo los puntos de entrega (asegurar mapa limpio)
                const deliveryCoords = [];
                
                // Agregar ubicación actual como primer punto
                deliveryCoords.push([currentLat, currentLng]);
                
                // Agregar solo las entregas (jobs)
                if (driverRoute && driverRoute.steps) {
                    driverRoute.steps.forEach(step => {
                        if (step.type === 'job' && step.location) {
                            deliveryCoords.push([step.location[1], step.location[0]]);
                        }
                    });
                }
                
                // Establecer como ruta de navegación
                routeCoordinates = deliveryCoords;
                completedPath = [[currentLat, currentLng]]; // Solo ubicación actual
                remainingPath = deliveryCoords.slice(1); // Desde segunda posición (entregas)
                currentRouteIndex = 0;
                
                // Procesar pasos de navegación
                processNavigationSteps();
                
                // Dibujar ruta en mapa limpio
                drawDynamicRoute();
                
                console.log('✅ Ruta fallback LIMPIA creada desde ubicación actual');
                console.log('📊 Total entregas:', remainingPath.length);
                
            } catch (error) {
                console.error('Error creando ruta fallback:', error);
                showError('Error calculando ruta de navegación');
            }
        }

        // Función para procesar nueva ruta limpia (solo desde ubicación actual)
        function processNewCleanRoute(route, currentLat, currentLng) {
            try {
                // Decodificar la nueva geometría
                const decoded = polyline.decode(route.geometry);
                routeCoordinates = decoded.map(p => [p[0], p[1]]); // [lat, lng]
                
                console.log('🗺️ Nueva ruta LIMPIA procesada:', routeCoordinates.length, 'puntos');
                console.log('🚫 SIN incluir ruta desde start del vehículo');
                
                // Inicializar progreso desde ubicación actual
                completedPath = [[currentLat, currentLng]]; // Solo ubicación actual al inicio
                remainingPath = routeCoordinates; // Toda la ruta nueva
                currentRouteIndex = 0;
                
                // Procesar pasos de navegación
                processNavigationSteps();
                
                // Dibujar ruta dinámica
                drawDynamicRoute();
                
                console.log('✅ Navegación iniciada SOLO desde tu ubicación GPS');
                
            } catch (error) {
                console.error('Error procesando nueva ruta limpia:', error);
                createFallbackRouteFromLocation(currentLat, currentLng);
            }
        }

        // Función para dibujar ruta dinámica (que se actualiza en tiempo real)
        function drawDynamicRoute() {
            console.log('🎨 Dibujando ruta dinámica...');
            
            // Limpiar rutas anteriores
            if (completedPolyline) {
                map.removeLayer(completedPolyline);
            }
            if (remainingPolyline) {
                map.removeLayer(remainingPolyline);
            }

            // Dibujar ruta completada (verde) - solo si hay progreso
            if (completedPath.length > 1) {
                completedPolyline = L.polyline(completedPath, {
                    color: '#28a745',
                    weight: 8,
                    opacity: 0.9
                }).addTo(map);
                
                console.log('🟢 Ruta completada dibujada:', completedPath.length, 'puntos');
            }

            // Dibujar ruta restante (azul)
            if (remainingPath.length > 1) {
                remainingPolyline = L.polyline(remainingPath, {
                    color: '#007bff',
                    weight: 6,
                    opacity: 0.8
                }).addTo(map);
                
                console.log('🔵 Ruta restante dibujada:', remainingPath.length, 'puntos');
            }

            // Agregar marcadores para próximos puntos importantes
            addDynamicNavigationMarkers();
        }

        // Función para agregar marcadores de navegación dinámicos
        function addDynamicNavigationMarkers() {
            // Limpiar marcadores de navegación anteriores
            currentMarkers = currentMarkers.filter(marker => {
                if (marker.options && marker.options.navigation) {
                    map.removeLayer(marker);
                    return false;
                }
                return true;
            });

            // Agregar marcadores para próximos pasos (solo los más cercanos)
            routeSteps.forEach((step, index) => {
                if (!step.completed && index <= currentStepIndex + 3) {
                    const marker = L.marker(step.location, {
                        navigation: true,
                        icon: L.divIcon({
                            className: 'navigation-marker',
                            html: `<div style="background-color: #ffc107; width: 35px; height: 35px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 8px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; font-size: 16px; animation: pulse 2s infinite;">📦</div>`,
                            iconSize: [35, 35],
                            iconAnchor: [17, 17]
                        })
                    }).addTo(map);

                    marker.bindPopup(`
                        <div style="text-align: center;">
                            <b>${step.instruction}</b><br>
                            <small>Distancia aprox: ${(getDistanceFromLatLonInKm(
                                completedPath[completedPath.length - 1][0],
                                completedPath[completedPath.length - 1][1],
                                step.location[0],
                                step.location[1]
                            ) * 1000).toFixed(0)}m</small>
                        </div>
                    `);
                    
                    currentMarkers.push(marker);
                }
            });
        }

        // Función para procesar pasos de navegación (solo entregas)
        function processNavigationSteps() {
            routeSteps = [];
            
            if (driverRoute && driverRoute.steps) {
                driverRoute.steps.forEach((step, index) => {
                    // SOLO procesar entregas (jobs), ignorar start y end
                    if (step.type === 'job' && step.location) {
                        routeSteps.push({
                            index: index,
                            location: [step.location[1], step.location[0]], // [lat, lng]
                            type: step.type,
                            instruction: getInstructionText(step),
                            completed: false
                        });
                    }
                });
            }
            
            console.log('📦 Pasos de navegación (solo entregas):', routeSteps.length);
        }

        // Función para generar texto de instrucciones (solo entregas)
        function getInstructionText(step) {
            if (step.type === 'job') {
                return `Entrega: ${step.job_details?.cliente || 'Cliente'}`;
            }
            return 'Continúa hacia la siguiente entrega';
        }

        // Función para dibujar ruta de navegación
        function drawNavigationRoute() {
            // Limpiar rutas anteriores
            if (completedPolyline) {
                map.removeLayer(completedPolyline);
            }
            if (remainingPolyline) {
                map.removeLayer(remainingPolyline);
            }

            // Dibujar ruta completada (verde)
            if (completedPath.length > 1) {
                completedPolyline = L.polyline(completedPath, {
                    color: '#28a745',
                    weight: 6,
                    opacity: 0.8
                }).addTo(map);
            }

            // Dibujar ruta restante (azul)
            if (remainingPath.length > 1) {
                remainingPolyline = L.polyline(remainingPath, {
                    color: '#007bff',
                    weight: 6,
                    opacity: 0.8
                }).addTo(map);
            }

            // Agregar marcadores para próximos puntos importantes
            addNavigationMarkers();
        }

        // Función para agregar marcadores de navegación
        function addNavigationMarkers() {
            // Limpiar marcadores anteriores
            currentMarkers.forEach(marker => {
                if (marker.options && marker.options.navigation) {
                    map.removeLayer(marker);
                }
            });

            // Agregar marcadores para próximos pasos
            routeSteps.forEach((step, index) => {
                if (!step.completed && index <= currentStepIndex + 2) {
                    const marker = L.marker(step.location, {
                        navigation: true,
                        icon: L.divIcon({
                            className: 'navigation-marker',
                            html: `<div style="background-color: #ffc107; width: 25px; height: 25px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 12px;">📦</div>`,
                            iconSize: [25, 25],
                            iconAnchor: [12, 12]
                        })
                    }).addTo(map);

                    marker.bindPopup(step.instruction);
                    currentMarkers.push(marker);
                }
            });
        }

        // Función para iniciar seguimiento GPS
        function startGPSTracking() {
            if (!navigator.geolocation) {
                showError('Geolocalización no disponible');
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            };

            watchId = navigator.geolocation.watchPosition(
                function(position) {
                    updateNavigationPosition(position.coords.latitude, position.coords.longitude);
                },
                function(error) {
                    console.error('Error GPS:', error);
                    showError('Error obteniendo ubicación GPS');
                },
                options
            );
        }

        // Función para actualizar posición en navegación
        function updateNavigationPosition(lat, lng) {
            if (!isNavigating) return;

            // Actualizar marcador de ubicación actual
            updateCurrentLocation(lat, lng);

            // Encontrar punto más cercano en la ruta restante
            const closestIndex = findClosestPointIndex(remainingPath, lat, lng);
            
            // Si hemos avanzado significativamente en la ruta
            if (closestIndex > 5) { // Threshold para evitar actualizaciones menores
                
                // Agregar segmento completado
                const newCompletedSegment = remainingPath.slice(0, closestIndex);
                completedPath = completedPath.concat(newCompletedSegment);
                
                // Actualizar ruta restante
                remainingPath = remainingPath.slice(closestIndex);
                currentRouteIndex += closestIndex;
                
                // Redibujar ruta con nueva división
                drawDynamicRoute();
                
                console.log('🎯 Progreso actualizado:', {
                    completedPoints: completedPath.length,
                    remainingPoints: remainingPath.length,
                    progress: ((completedPath.length / (completedPath.length + remainingPath.length)) * 100).toFixed(1) + '%'
                });
                
                // Verificar si hemos pasado algún paso
                checkStepCompletion(lat, lng);
                
                // Actualizar display
                updateNavigationDisplay();
                
                // Recalcular ruta si nos hemos desviado mucho
                const distanceToRoute = getDistanceFromLatLonInKm(
                    lat, lng,
                    remainingPath[0][0], remainingPath[0][1]
                );
                
                if (distanceToRoute > 0.1) { // Si estamos a más de 100m de la ruta
                    console.log('🔄 Recalculando ruta por desviación:', distanceToRoute.toFixed(3), 'km');
                    recalculateRouteFromPosition(lat, lng);
                }
            }

            // Centrar mapa en ubicación actual con zoom apropiado
            map.setView([lat, lng], 18);
        }

        // Función para recalcular ruta desde posición actual (solo entregas pendientes)
        function recalculateRouteFromPosition(lat, lng) {
            console.log('🔄 Recalculando ruta desde nueva posición (solo entregas pendientes)...');
            
            // Obtener SOLO entregas restantes (no completadas)
            const remainingDeliveries = routeSteps.filter(step => 
                !step.completed && step.type === 'job'
            );
            
            if (remainingDeliveries.length === 0) {
                console.log('✅ ¡Todas las entregas completadas!');
                announceStep({ instruction: 'Todas las entregas completadas. ¡Buen trabajo!' });
                return;
            }

            console.log('📦 Entregas restantes:', remainingDeliveries.length);

            // Llamar al servidor para nueva ruta SOLO con entregas pendientes
            const requestData = {
                current_location: [lng, lat],
                delivery_points: remainingDeliveries.map(step => ({
                    lat: step.location[0],
                    lng: step.location[1],
                    cliente: step.instruction,
                    id: step.index
                }))
            };

            axios.post('/get-optimized-route', requestData)
                .then(response => {
                    if (response.data.success && response.data.route) {
                        console.log('✅ Ruta recalculada (solo entregas pendientes)');
                        
                        // Procesar nueva ruta manteniendo el progreso actual
                        const decoded = polyline.decode(response.data.route.geometry);
                        const newRouteCoordinates = decoded.map(p => [p[0], p[1]]);
                        
                        // Mantener el progreso actual y actualizar solo la parte restante
                        remainingPath = newRouteCoordinates;
                        
                        // Redibujar
                        drawDynamicRoute();
                        
                        console.log('🎯 Ruta actualizada dinámicamente (solo entregas)');
                    }
                })
                .catch(error => {
                    console.error('Error recalculando ruta:', error);
                    // Continuar con ruta actual
                });
        }

        // Función para verificar completación de pasos
        function checkStepCompletion(lat, lng) {
            routeSteps.forEach((step, index) => {
                if (!step.completed) {
                    const distance = getDistanceFromLatLonInKm(lat, lng, step.location[0], step.location[1]);
                    
                    // Si estamos cerca del paso (menos de 50 metros)
                    if (distance < 0.05) {
                        step.completed = true;
                        currentStepIndex = index;
                        
                        // Anunciar llegada
                        announceStep(step);
                        
                        // Actualizar marcadores
                        addNavigationMarkers();
                        
                        console.log('Paso completado:', step.instruction);
                    }
                }
            });
        }

        // Función para anunciar paso
        function announceStep(step) {
            if (voiceEnabled && 'speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(step.instruction);
                utterance.lang = 'es-ES';
                utterance.rate = 0.9;
                speechSynthesis.speak(utterance);
            }
            
            // Mostrar notificación visual
            showNotification(step.instruction);
        }

        // Función para mostrar notificación
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 2000;
                font-weight: bold;
                max-width: 300px;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 3000);
        }

        // Función para actualizar display de navegación
        function updateNavigationDisplay() {
            const instructionEl = document.getElementById('current-instruction');
            const distanceEl = document.getElementById('distance-info');
            const nextTurnEl = document.getElementById('next-turn');
            
            if (instructionEl && distanceEl && nextTurnEl) {
                const nextStep = routeSteps.find(step => !step.completed);
                
                if (nextStep) {
                    instructionEl.textContent = nextStep.instruction;
                    
                    // Calcular distancia restante
                    const remainingDistance = (remainingPath.length * 0.01).toFixed(1); // Aproximación
                    distanceEl.textContent = `Distancia restante: ${remainingDistance} km`;
                    
                    // Próximo giro
                    const nextTurnStep = routeSteps.find((step, index) => !step.completed && index > currentStepIndex);
                    if (nextTurnStep) {
                        nextTurnEl.textContent = `Próximo: ${nextTurnStep.instruction}`;
                    } else {
                        nextTurnEl.textContent = 'Última parada';
                    }
                } else {
                    instructionEl.textContent = 'Ruta completada';
                    distanceEl.textContent = 'Distancia: 0 km';
                    nextTurnEl.textContent = 'Has llegado a tu destino';
                }
            }
        }

        // Función para dibujar ruta de navegación (renombrada para evitar conflictos)
        function drawNavigationRoute() {
            drawDynamicRoute();
        }

        // Función para detener navegación
        function stopNavigation() {
            isNavigating = false;
            
            console.log('🛑 Deteniendo navegación...');
            
            // Detener GPS tracking
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            
            // Limpiar completamente para volver al estado original
            clearMapCompletely();
            
            // Mostrar la ruta original nuevamente
            setTimeout(() => {
                console.log('🔄 Restaurando ruta original después de navegación...');
                if (driverRoute) {
                    showRouteWithPolyline();
                }
            }, 500);
            
            console.log('✅ Navegación detenida - mapa restaurado');
        }

        // Función para encontrar índice del punto más cercano
        function findClosestPointIndex(points, lat, lng) {
            let closestIndex = 0;
            let minDistance = Infinity;
            
            points.forEach((point, index) => {
                const distance = getDistanceFromLatLonInKm(lat, lng, point[0], point[1]);
                if (distance < minDistance) {
                    minDistance = distance;
                    closestIndex = index;
                }
            });
            
            return closestIndex;
        }

        // Función para usar cuando Routing Machine no está disponible
        function showBasicRouteOnly() {
            console.log('🗺️ Mostrando ruta básica sin Routing Machine');
            showRouteWithPolyline();
            
            // Si hay ruta, permitir navegación básica
            if (driverRoute) {
                const navButton = document.getElementById('navigation-button');
                if (navButton) {
                    navButton.disabled = false;
                    navButton.textContent = '🧭 Navegación';
                    navButton.style.opacity = '1';
                }
            }
        }

        // Función para centrar en ubicación
        // Función para toggle del seguimiento en tiempo real
        function toggleRealTimeTracking() {
            const button = document.getElementById('follow-button');
            
            if (isRealTimeActive) {
                // Detener seguimiento
                if (realtimeLayer) {
                    map.removeLayer(realtimeLayer);
                }
                
                // Detener GPS según el método disponible
                if (gpsControl && typeof gpsControl.stop === 'function') {
                    gpsControl.stop();
                } else {
                    stopNativeGeolocation();
                }
                
                // Remover marcador de ubicación actual
                if (currentLocationMarker) {
                    map.removeLayer(currentLocationMarker);
                    currentLocationMarker = null;
                }
                
                isRealTimeActive = false;
                button.textContent = '🚀 Seguimiento';
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');
                updateConnectionStatus(false);
            } else {
                // Iniciar seguimiento
                if (realtimeLayer) {
                    realtimeLayer.addTo(map);
                }
                
                // Iniciar GPS según el método disponible
                if (gpsControl && typeof gpsControl.start === 'function') {
                    gpsControl.start();
                } else {
                    startNativeGeolocation();
                }
                
                isRealTimeActive = true;
                button.textContent = '⏹️ Detener';
                button.classList.remove('btn-success');
                button.classList.add('btn-danger');
                updateConnectionStatus(true);
            }
        }

        // Función para recalcular ruta
        function recalculateRoute() {
            if (!navigator.geolocation) {
                showError('Geolocalización no disponible');
                return;
            }

            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '🔄 Calculando...';
            button.disabled = true;

            navigator.geolocation.getCurrentPosition(function(position) {
                // Actualizar waypoints con posición actual
                const currentLat = position.coords.latitude;
                const currentLng = position.coords.longitude;
                
                // Actualizar marcador de ubicación actual
                updateCurrentLocation(currentLat, currentLng);
                
                // Recalcular ruta
                showOptimizedRoute();
                
                button.textContent = originalText;
                button.disabled = false;
            }, function(error) {
                showError('No se pudo obtener la ubicación actual: ' + error.message);
                button.textContent = originalText;
                button.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        }

        // Función de emergencia
        function emergencyStop() {
            if (confirm('¿Estás seguro de que quieres activar el modo emergencia?')) {
                // Detener todo seguimiento
                if (realtimeLayer) {
                    map.removeLayer(realtimeLayer);
                }
                
                // Detener GPS
                if (gpsControl && typeof gpsControl.stop === 'function') {
                    gpsControl.stop();
                } else {
                    stopNativeGeolocation();
                }
                
                isRealTimeActive = false;
                
                // Obtener ubicación actual para emergencia
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const location = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                            timestamp: new Date().toISOString()
                        };
                        
                        // Enviar señal de emergencia al servidor
                        axios.post('/emergency', {
                            driver_id: {{ $driver->id ?? 'null' }},
                            location: location
                        }).then(response => {
                            alert('Señal de emergencia enviada correctamente');
                        }).catch(error => {
                            console.error('Error enviando emergencia:', error);
                            alert('Error al enviar señal de emergencia. Contacta por teléfono.');
                        });
                    }, function(error) {
                        // Enviar emergencia sin ubicación
                        axios.post('/emergency', {
                            driver_id: {{ $driver->id ?? 'null' }},
                            location: null,
                            error: 'No se pudo obtener ubicación'
                        }).then(response => {
                            alert('Señal de emergencia enviada sin ubicación');
                        }).catch(error => {
                            console.error('Error enviando emergencia:', error);
                            alert('Error al enviar señal de emergencia. Contacta por teléfono.');
                        });
                    });
                } else {
                    // Enviar emergencia sin geolocalización
                    axios.post('/emergency', {
                        driver_id: {{ $driver->id ?? 'null' }},
                        location: null,
                        error: 'Geolocalización no disponible'
                    }).then(response => {
                        alert('Señal de emergencia enviada');
                    }).catch(error => {
                        console.error('Error enviando emergencia:', error);
                        alert('Error al enviar señal de emergencia. Contacta por teléfono.');
                    });
                }
            }
        }

        // Actualizar estado de conexión
        function updateConnectionStatus(isConnected) {
            const indicator = document.getElementById('status-indicator');
            const status = document.getElementById('connection-status');
            
            if (isConnected) {
                indicator.className = 'status-indicator status-online';
                status.textContent = 'Conectado';
            } else {
                indicator.className = 'status-indicator status-offline';
                status.textContent = 'Desconectado';
            }
        }

        // Actualizar progreso de entregas
        function updateDeliveryProgress() {
            const completedCount = Object.values(deliveryStatus).filter(status => status === 'completed').length;
            const pendingCount = Object.keys(deliveryStatus).length - completedCount;
            
            document.getElementById('completed-count').textContent = completedCount;
            document.getElementById('pending-count').textContent = pendingCount;
        }

        // Cerrar panel automáticamente en móviles al cargar
        if (window.innerWidth <= 768) {
            document.getElementById('panel-content').classList.add('collapsed');
            document.getElementById('toggle-icon').classList.add('collapsed');
            document.getElementById('toggle-icon').textContent = '▲';
        }

        // La inicialización se maneja en initializeApp() que se llama desde el script de verificación de plugins

        // Agregar estilos para animación de pulso
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.1); opacity: 0.7; }
                100% { transform: scale(1); opacity: 1; }
            }
            .navigation-marker {
                animation: pulse 2s infinite;
            }
        `;
        document.head.appendChild(style);

        // Función para toggle de voz
        function toggleVoiceNavigation() {
            const button = document.getElementById('voice-button');
            voiceEnabled = !voiceEnabled;
            
            if (voiceEnabled) {
                button.textContent = '🔊 Voz ON';
                button.style.backgroundColor = '#28a745';
            } else {
                button.textContent = '🔇 Voz OFF';
                button.style.backgroundColor = '#dc3545';
            }
        }

        // Función de emergencia para ocultar loading si algo falla
        function forceHideLoading() {
            document.getElementById('loading').style.display = 'none';
            console.log('⚠️ Loading ocultado por emergencia');
        }

        // Ejecutar después de 10 segundos por si acaso
        setTimeout(forceHideLoading, 10000);

        // Si initializeApp no se ejecuta, ejecutar manualmente
        setTimeout(() => {
            if (document.getElementById('loading').style.display !== 'none') {
                console.log('🔄 Ejecutando inicialización manual...');
                initializeAppDirect();
            }
        }, 3000);

        // Función de debug para verificar elementos en el mapa
        function debugMapElements() {
            console.log('🔍 Debuggeando elementos del mapa...');
            console.log('Elementos en currentMarkers:', currentMarkers.length);
            
            currentMarkers.forEach((marker, index) => {
                console.log(`Elemento ${index}:`, marker.constructor.name, marker.options);
                if (marker.getLatLng) {
                    console.log('  Posición:', marker.getLatLng());
                }
                if (marker.getLatLngs) {
                    console.log('  Coordenadas:', marker.getLatLngs().length, 'puntos');
                }
            });
            
            console.log('Datos de la ruta:', driverRoute);
            
            // Verificar si hay polylines en el mapa
            map.eachLayer(function(layer) {
                if (layer instanceof L.Polyline) {
                    console.log('🔵 Polyline encontrada en el mapa:', layer.options.color);
                }
                if (layer instanceof L.Marker) {
                    console.log('📍 Marker encontrado en el mapa:', layer.getLatLng());
                }
            });
        }

        // Función simplificada para mostrar ruta inmediatamente
        function showRouteNow() {
            console.log('🚀 Mostrando ruta inmediatamente...');
            
            // Ocultar loading
            document.getElementById('loading').style.display = 'none';
            
            // Mostrar ruta si existe
            if (driverRoute) {
                showRouteWithPolyline();
                
                // Debug después de mostrar la ruta
                setTimeout(() => {
                    debugMapElements();
                }, 1000);
            } else {
                showError('No hay ruta disponible');
            }
        }

        // Función para forzar redibujado del mapa
        function forceMapRedraw() {
            console.log('🔄 Forzando redibujado del mapa...');
            
            // Invalidar el tamaño del mapa
            map.invalidateSize();
            
            // Redibujar todas las capas
            map.eachLayer(function(layer) {
                if (layer.redraw) {
                    layer.redraw();
                }
            });
            
            // Mostrar ruta nuevamente
            if (driverRoute) {
                showRouteWithPolyline();
            }
        }

        // Agregar función al window para acceso desde consola
        window.debugMapElements = debugMapElements;
        window.forceMapRedraw = forceMapRedraw;
        window.showRouteNow = showRouteNow;
        window.clearMapCompletely = clearMapCompletely; // Agregar nueva función

        // Función para limpiar mapa manualmente (disponible desde consola)
        function clearMapNow() {
            clearMapCompletely();
            console.log('🧹 Mapa limpiado manualmente desde consola');
        }
        window.clearMapNow = clearMapNow;

        // Ejecutar showRouteNow como último recurso
        setTimeout(showRouteNow, 5000);
    </script>
    </script>
</body>
</html>