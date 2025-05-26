<!DOCTYPE html>
<html>

<head>
    <title>Navegación en Tiempo Real - Rutas de Vehículos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet MarkerCluster -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }

        /* Panel de control principal */
        .control-panel {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            max-width: 320px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Panel de navegación en tiempo real */
        .navigation-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            min-width: 300px;
            display: none;
        }

        .navigation-panel.active {
            display: block;
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
        }

        .nav-title {
            font-size: 18px;
            font-weight: bold;
        }

        .nav-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 16px;
        }

        .nav-instruction {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .nav-distance {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .nav-progress {
            background: rgba(255,255,255,0.3);
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .nav-progress-bar {
            background: #4CAF50;
            height: 100%;
            transition: width 0.3s ease;
        }

        .nav-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .nav-stat {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 8px;
        }

        .nav-stat-value {
            font-size: 20px;
            font-weight: bold;
        }

        .nav-stat-label {
            font-size: 12px;
            opacity: 0.8;
        }

        /* Botones principales */
        .control-panel button {
            margin: 5px 0;
            padding: 12px 16px;
            width: 100%;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .control-panel button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .active-route {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%) !important;
        }

        .following-active {
            background: linear-gradient(135deg, #FF6B6B 0%, #ee5a52 100%) !important;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 107, 107, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
        }

        /* Botón de navegación especial */
        .nav-button {
            background: linear-gradient(135deg, #FF6B6B 0%, #ee5a52 100%) !important;
            font-size: 16px !important;
            padding: 15px 20px !important;
            margin: 10px 0 !important;
        }

        /* Panel de información */
        #user-info {
            margin-bottom: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }

        #route-info {
            margin-top: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            display: none;
        }

        /* Mensajes de error y carga */
        #error-message {
            color: #dc3545;
            margin-top: 10px;
            padding: 12px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
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
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Marcadores personalizados */
        .start-marker {
            background-color: #4CAF50;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }

        .end-marker {
            background-color: #F44336;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }

        .job-marker {
            background-color: #2196F3;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }

        .current-position-marker {
            background-color: #FF9800;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            animation: currentLocationPulse 2s infinite;
        }

        @keyframes currentLocationPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Controles del mapa */
        .map-controls {
            position: absolute;
            bottom: 20px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .map-controls button {
            margin: 5px 0;
            padding: 12px;
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .map-controls button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        /* Indicador de velocidad */
        .speed-indicator {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            display: none;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .speed-value {
            font-size: 24px;
            font-weight: bold;
        }

        .speed-unit {
            font-size: 14px;
            opacity: 0.8;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .control-panel {
                left: 5px;
                right: 5px;
                max-width: none;
                top: 5px;
            }
            
            .navigation-panel {
                right: 5px;
                left: 5px;
                top: auto;
                bottom: 20px;
                max-width: none;
            }
            
            .map-controls {
                right: 5px;
                bottom: 180px;
            }
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <!-- Panel de Control Principal -->
    <div class="control-panel">
        <div id="user-info">
            @if($is_admin)
                <strong>🔧 Administrador</strong> - {{ $user_name }}
                <p>Acceso completo a todas las rutas</p>
            @elseif($is_motorista)
                <strong>🚛 Motorista</strong> - {{ $user_name }}
                <p>ID: {{ $user_id }} - Navegación disponible</p>
            @else
                <strong>👤 Usuario</strong>
                <p>Permisos limitados</p>
            @endif
        </div>

        <h3>🚗 Vehículos Disponibles</h3>
        <div id="vehicle-buttons">
            @if(count($routes) > 0)
                @foreach($routes as $index => $route)
                    <button onclick="showRoute({{ $route['vehicle'] }})" 
                            id="vehicle-{{ $route['vehicle'] }}"
                            class="{{ $route['vehicle'] == $user_id ? 'active-route' : '' }}">
                        🚛 Vehículo {{ $route['vehicle'] }} - {{ $route['vehicle_name'] }}
                        @if($route['vehicle'] == $user_id) (Tu ruta) @endif
                    </button>
                @endforeach
                
                @if($is_admin && count($routes) > 1)
                    <button onclick="showAllRoutes()">🗺️ Mostrar Todas las Rutas</button>
                @endif
            @else
                <p>❌ No hay rutas disponibles para mostrar.</p>
            @endif
            
            <button id="nav-button" class="nav-button" onclick="startNavigation()">
                🧭 Iniciar Navegación
            </button>
        </div>

        <div id="route-info">
            <h4>📊 Información de la Ruta</h4>
            <div id="route-details"></div>
        </div>

        <div id="error-message"></div>
    </div>

    <!-- Panel de Navegación en Tiempo Real -->
    <div class="navigation-panel" id="navigation-panel">
        <div class="nav-header">
            <div class="nav-title">🧭 Navegación Activa</div>
            <button class="nav-close" onclick="stopNavigation()">✕</button>
        </div>
        
        <div class="nav-instruction" id="nav-instruction">
            Preparando navegación...
        </div>
        
        <div class="nav-distance" id="nav-distance">
            Calculando distancia...
        </div>
        
        <div class="nav-progress">
            <div class="nav-progress-bar" id="nav-progress-bar" style="width: 0%"></div>
        </div>
        
        <div class="nav-stats">
            <div class="nav-stat">
                <div class="nav-stat-value" id="nav-speed">0</div>
                <div class="nav-stat-label">km/h</div>
            </div>
            <div class="nav-stat">
                <div class="nav-stat-value" id="nav-eta">--:--</div>
                <div class="nav-stat-label">ETA</div>
            </div>
        </div>
    </div>

    <!-- Controles del Mapa -->
    <div class="map-controls">
        <button id="locate-me" title="Mi ubicación">📍 Ubicarme</button>
        <button id="zoom-fit" title="Ajustar vista">🔍 Ajustar Vista</button>
        <button id="toggle-satellite" title="Cambiar mapa">🌍 Satélite</button>
        <button id="voice-toggle" title="Activar/Desactivar voz">🔊 Voz ON</button>
    </div>

    <!-- Indicador de Velocidad -->
    <div class="speed-indicator" id="speed-indicator">
        <div class="speed-value" id="speed-display">0</div>
        <div class="speed-unit">km/h</div>
    </div>

    <!-- Pantalla de Carga -->
    <div id="loading">
        <div class="loading-spinner"></div>
        <h2>🗺️ Cargando navegación...</h2>
        <p>Preparando tu ruta optimizada</p>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        // ========== CONFIGURACIÓN GLOBAL ==========
        document.getElementById('loading').style.display = 'flex';

        // Datos desde PHP
        const routesData = @json($routes);
        const vehiclesData = @json($vehicles);
        const jobsData = @json($jobs);
        const isAdmin = @json($is_admin);
        const isMotorista = @json($is_motorista);
        const userId = @json($user_id);
        
        console.log('🚀 Iniciando aplicación de navegación');
        console.log('📊 Datos de rutas:', routesData);
        console.log('👤 Usuario ID:', userId);

        // Variables globales
        let map;
        let tileLayer;
        let satelliteLayer;
        let isSatelliteView = false;
        const colors = ['#FF0000', '#0000FF', '#00FF00', '#FF00FF', '#FFFF00', '#00FFFF', '#FFA500', '#800080'];
        
        // Control de elementos del mapa
        let currentPolylines = [];
        let currentMarkers = [];
        let markerClusters = null;
        let routeBounds = null;
        let currentVehicleId = null;
        
        // Variables de navegación
        let isNavigating = false;
        let navigationInterval = null;
        let currentPosition = null;
        let currentRoute = null;
        let currentStep = 0;
        let totalSteps = 0;
        let voiceEnabled = true;
        let lastSpokenInstruction = null;
        let speechSynthesis = window.speechSynthesis;
        
        // Marcadores de navegación
        let userMarker = null;
        let routeLine = null;
        let nextStepMarker = null;

        // ========== INICIALIZACIÓN DEL MAPA ==========
        function initMap() {
            console.log('🗺️ Inicializando mapa...');
            
            map = L.map('map', {
                center: [14.0821, -87.2065], // Tegucigalpa
                zoom: 13,
                zoomControl: false
            });
            
            L.control.zoom({
                position: 'topright'
            }).addTo(map);
            
            // Capas de mapa
            tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri'
            });
            
            markerClusters = L.markerClusterGroup({
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                disableClusteringAtZoom: 16
            });
            
            map.addLayer(markerClusters);
            
            // Eventos del mapa
            setupMapEvents();
            console.log('✅ Mapa inicializado correctamente');
        }

        function setupMapEvents() {
            // Eventos de botones
            document.getElementById('locate-me').addEventListener('click', locateUser);
            document.getElementById('zoom-fit').addEventListener('click', fitMapToRoute);
            document.getElementById('toggle-satellite').addEventListener('click', toggleMapView);
            document.getElementById('voice-toggle').addEventListener('click', toggleVoice);
            
            // Evento de clic en el mapa para debug
            map.on('click', function(e) {
                console.log("🎯 Coordenadas:", e.latlng.lat, e.latlng.lng);
            });
        }

        // ========== FUNCIONES DE NAVEGACIÓN PRINCIPAL ==========
        async function startNavigation() {
            console.log('🧭 Iniciando navegación...');
            
            if (isNavigating) {
                stopNavigation();
                return;
            }
            
            // Verificar que hay una ruta seleccionada
            if (!currentVehicleId) {
                showError('Por favor selecciona una ruta primero');
                return;
            }
            
            // Verificar geolocalización
            if (!navigator.geolocation) {
                showError('Tu dispositivo no soporta geolocalización');
                return;
            }
            
            try {
                document.getElementById('loading').style.display = 'flex';
                
                // Obtener ubicación actual
                const position = await getCurrentPosition();
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed || 0,
                    heading: position.coords.heading || 0
                };
                
                console.log('📍 Ubicación obtenida:', currentPosition);
                
                // Obtener ruta optimizada desde la ubicación actual
                await calculateNavigationRoute();
                
                // Iniciar navegación
                isNavigating = true;
                showNavigationPanel();
                updateNavigationButton();
                
                // Configurar seguimiento GPS
                startGPSTracking();
                
                // Anuncio inicial
                speak('Navegación iniciada. Sigue las instrucciones.');
                
                document.getElementById('loading').style.display = 'none';
                console.log('✅ Navegación iniciada exitosamente');
                
            } catch (error) {
                console.error('❌ Error al iniciar navegación:', error);
                document.getElementById('loading').style.display = 'none';
                showError('Error al iniciar navegación: ' + error.message);
            }
        }

        function stopNavigation() {
            console.log('🛑 Deteniendo navegación...');
            
            isNavigating = false;
            
            // Detener seguimiento GPS
            if (navigationInterval) {
                clearInterval(navigationInterval);
                navigationInterval = null;
            }
            
            // Ocultar panel de navegación
            hideNavigationPanel();
            updateNavigationButton();
            
            // Limpiar marcadores de navegación
            clearNavigationMarkers();
            
            // Cancelar síntesis de voz
            if (speechSynthesis.speaking) {
                speechSynthesis.cancel();
            }
            
            // Ocultar indicador de velocidad
            document.getElementById('speed-indicator').style.display = 'none';
            
            console.log('✅ Navegación detenida');
        }

        async function calculateNavigationRoute() {
            console.log('🛣️ Calculando ruta de navegación...');
            
            const requestData = {
                current_location: [currentPosition.lat, currentPosition.lng]
            };
            
            // Si es admin y hay un vehículo específico seleccionado
            if (currentVehicleId !== null && isAdmin) {
                requestData.vehicle_id = currentVehicleId;
            }
            
            const response = await axios.post('/routes/seguir', requestData, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                timeout: 30000
            });
            
            if (!response.data.success || !response.data.routes || response.data.routes.length === 0) {
                throw new Error('No se pudo calcular la ruta de navegación');
            }
            
            currentRoute = response.data.routes[0];
            
            if (!currentRoute.geometry) {
                throw new Error('La ruta no contiene información geométrica');
            }
            
            // Decodificar geometría
            const decoded = polyline.decode(currentRoute.geometry);
            currentRoute.coordinates = decoded.map(p => L.latLng(p[0], p[1]));
            
            // Procesar pasos de navegación
            if (currentRoute.steps && currentRoute.steps.length > 0) {
                totalSteps = currentRoute.steps.length;
                currentStep = 0;
                
                console.log(`✅ Ruta calculada con ${totalSteps} pasos`);
            } else {
                throw new Error('La ruta no contiene pasos de navegación');
            }
            
            // Dibujar ruta en el mapa
            drawNavigationRoute();
        }

        function drawNavigationRoute() {
            console.log('🎨 Dibujando ruta de navegación...');
            
            // Limpiar ruta anterior
            if (routeLine) {
                map.removeLayer(routeLine);
            }
            
            // Dibujar nueva ruta
            routeLine = L.polyline(currentRoute.coordinates, {
                color: '#4CAF50',
                weight: 6,
                opacity: 0.8,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);
            
            // Añadir marcadores para los pasos importantes
            if (currentRoute.steps) {
                currentRoute.steps.forEach((step, index) => {
                    if (step.type === 'job' && step.location) {
                        const [lng, lat] = step.location;
                        
                        const marker = L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: 'job-marker',
                                html: `<div style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">${index + 1}</div>`,
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        }).addTo(map);
                        
                        // Información del pedido
                        if (step.job_details) {
                            marker.bindPopup(`
                                <div>
                                    <h4>📦 Entrega ${index + 1}</h4>
                                    <p><strong>Cliente:</strong> ${step.job_details.cliente}</p>
                                    <p><strong>ID:</strong> ${step.job}</p>
                                </div>
                            `);
                        }
                        
                        currentMarkers.push(marker);
                    }
                });
            }
            
            // Ajustar vista
            if (currentRoute.coordinates.length > 0) {
                const routeBounds = L.latLngBounds(currentRoute.coordinates);
                map.fitBounds(routeBounds, { padding: [50, 50] });
            }
        }

        // ========== SEGUIMIENTO GPS EN TIEMPO REAL ==========
        function startGPSTracking() {
            console.log('📡 Iniciando seguimiento GPS...');
            
            // Actualización inicial
            updateNavigationStatus();
            
            // Configurar intervalo de actualización
            navigationInterval = setInterval(updateNavigationStatus, 2000); // Cada 2 segundos
            
            // Mostrar indicador de velocidad
            document.getElementById('speed-indicator').style.display = 'block';
        }

        async function updateNavigationStatus() {
            if (!isNavigating) return;
            
            try {
                // Obtener nueva posición
                const position = await getCurrentPosition();
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed || 0,
                    heading: position.coords.heading || 0
                };
                
                // Actualizar marcador de usuario
                updateUserMarker();
                
                // Actualizar información de navegación
                updateNavigationInfo();
                
                // Verificar si llegamos al siguiente paso
                checkStepCompletion();
                
                // Actualizar velocidad
                updateSpeedDisplay();
                
            } catch (error) {
                console.warn('⚠️ Error al actualizar GPS:', error.message);
            }
        }

        function updateUserMarker() {
            if (!currentPosition) return;
            
            if (userMarker) {
                userMarker.setLatLng([currentPosition.lat, currentPosition.lng]);
            } else {
                userMarker = L.marker([currentPosition.lat, currentPosition.lng], {
                    icon: L.divIcon({
                        className: 'current-position-marker',
                        html: '<div style="width: 20px; height: 20px; border-radius: 50%; background: #FF9800;"></div>',
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    })
                }).addTo(map);
            }
            
            // Centrar mapa en la posición del usuario (opcional)
            // map.setView([currentPosition.lat, currentPosition.lng], map.getZoom());
        }

        function updateNavigationInfo() {
            if (!currentRoute || !currentRoute.steps || currentStep >= currentRoute.steps.length) {
                return;
            }
            
            const nextStep = currentRoute.steps[currentStep];
            const distanceToNext = calculateDistanceToStep(nextStep);
            
            // Actualizar instrucción
            const instruction = getStepInstruction(nextStep, distanceToNext);
            document.getElementById('nav-instruction').textContent = instruction;
            
            // Actualizar distancia
            document.getElementById('nav-distance').textContent = formatDistance(distanceToNext);
            
            // Actualizar progreso
            const progress = ((currentStep) / totalSteps) * 100;
            document.getElementById('nav-progress-bar').style.width = progress + '%';
            
            // Actualizar ETA
            updateETA();
            
            // Anuncio de voz para instrucciones importantes
            checkVoiceAnnouncement(instruction, distanceToNext);
        }

        function calculateDistanceToStep(step) {
            if (!step.location || !currentPosition) return 0;
            
            const [lng, lat] = step.location;
            return getDistanceFromLatLonInKm(
                currentPosition.lat,
                currentPosition.lng,
                lat,
                lng
            );
        }

        function getStepInstruction(step, distance) {
            if (!step) return 'Continúa por la ruta';
            
            if (step.type === 'job') {
                if (distance < 0.1) { // Menos de 100 metros
                    return `🎯 Has llegado - ${step.job_details ? step.job_details.cliente : 'Entrega'}`;
                } else if (distance < 0.5) { // Menos de 500 metros
                    return `📦 Próxima entrega - ${step.job_details ? step.job_details.cliente : 'Cliente'}`;
                } else {
                    return `🚛 Dirígete hacia - ${step.job_details ? step.job_details.cliente : 'Próxima entrega'}`;
                }
            } else if (step.type === 'start') {
                return '🚀 Inicio de ruta';
            } else if (step.type === 'end') {
                return '🏁 Fin de ruta completado';
            }
            
            return 'Continúa por la ruta';
        }

        function checkStepCompletion() {
            if (!currentRoute || !currentRoute.steps || currentStep >= currentRoute.steps.length) {
                return;
            }
            
            const currentStepData = currentRoute.steps[currentStep];
            const distanceToStep = calculateDistanceToStep(currentStepData);
            
            // Si estamos a menos de 50 metros del paso actual, pasar al siguiente
            if (distanceToStep < 0.05) { // 50 metros
                console.log(`✅ Paso ${currentStep + 1} completado`);
                
                // Anuncio de llegada
                if (currentStepData.type === 'job') {
                    const clientName = currentStepData.job_details ? currentStepData.job_details.cliente : 'Cliente';
                    speak(`Has llegado a tu destino: ${clientName}`);
                    
                    // Mostrar notificación visual
                    showArrivalNotification(clientName);
                }
                
                currentStep++;
                
                // Verificar si completamos toda la ruta
                if (currentStep >= totalSteps) {
                    completeNavigation();
                }
            }
        }

        function completeNavigation() {
            console.log('🎉 Navegación completada');
            
            speak('Felicitaciones! Has completado todas las entregas.');
            
            // Mostrar mensaje de completado
            document.getElementById('nav-instruction').textContent = '🎉 ¡Ruta completada!';
            document.getElementById('nav-distance').textContent = 'Todas las entregas realizadas';
            
            // Detener navegación después de 5 segundos
            setTimeout(() => {
                stopNavigation();
            }, 5000);
        }

        function updateSpeedDisplay() {
            if (!currentPosition) return;
            
            // Convertir de m/s a km/h
            const speedKmh = Math.round((currentPosition.speed || 0) * 3.6);
            
            document.getElementById('nav-speed').textContent = speedKmh;
            document.getElementById('speed-display').textContent = speedKmh;
        }

        function updateETA() {
            if (!currentRoute || !currentRoute.steps) return;
            
            // Calcular ETA estimado basado en pasos restantes y velocidad actual
            const remainingSteps = totalSteps - currentStep;
            const avgTimePerStep = 300; // 5 minutos promedio por entrega
            const estimatedSeconds = remainingSteps * avgTimePerStep;
            
            const now = new Date();
            const eta = new Date(now.getTime() + estimatedSeconds * 1000);
            
            document.getElementById('nav-eta').textContent = eta.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // ========== FUNCIONES DE VOZ ==========
        function speak(text) {
            if (!voiceEnabled || !speechSynthesis) return;
            
            // Evitar repetir la misma instrucción
            if (text === lastSpokenInstruction) return;
            lastSpokenInstruction = text;
            
            // Cancelar cualquier síntesis en curso
            speechSynthesis.cancel();
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'es-ES';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            utterance.volume = 0.8;
            
            speechSynthesis.speak(utterance);
            console.log('🔊 Anuncio de voz:', text);
        }

        function checkVoiceAnnouncement(instruction, distance) {
            // Anunciar cuando estemos cerca de una entrega
            if (distance < 0.2 && distance > 0.1) { // Entre 100-200 metros
                if (instruction.includes('Próxima entrega') || instruction.includes('Dirígete hacia')) {
                    speak(instruction);
                }
            }
        }

        function toggleVoice() {
            voiceEnabled = !voiceEnabled;
            const button = document.getElementById('voice-toggle');
            button.textContent = voiceEnabled ? '🔊 Voz ON' : '🔇 Voz OFF';
            button.style.opacity = voiceEnabled ? '1' : '0.6';
            
            if (voiceEnabled) {
                speak('Instrucciones de voz activadas');
            }
        }

        // ========== FUNCIONES DE INTERFAZ ==========
        function showNavigationPanel() {
            const panel = document.getElementById('navigation-panel');
            panel.classList.add('active');
        }

        function hideNavigationPanel() {
            const panel = document.getElementById('navigation-panel');
            panel.classList.remove('active');
        }

        function updateNavigationButton() {
            const button = document.getElementById('nav-button');
            if (isNavigating) {
                button.textContent = '🛑 Detener Navegación';
                button.classList.add('following-active');
            } else {
                button.textContent = '🧭 Iniciar Navegación';
                button.classList.remove('following-active');
            }
        }

        function showArrivalNotification(clientName) {
            // Crear notificación temporal
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                color: white;
                padding: 20px 30px;
                border-radius: 15px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.3);
                z-index: 3000;
                font-size: 18px;
                font-weight: bold;
                text-align: center;
                animation: bounceIn 0.5s ease-out;
            `;
            notification.innerHTML = `
                <div>🎯 ¡Has llegado!</div>
                <div style="font-size: 14px; margin-top: 5px;">📦 ${clientName}</div>
            `;
            
            document.body.appendChild(notification);
            
            // Remover después de 3 segundos
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // ========== FUNCIONES AUXILIARES ==========
        async function getCurrentPosition() {
            return new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    reject,
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 5000
                    }
                );
            });
        }

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

        function formatDistance(km) {
            if (km < 1) {
                return Math.round(km * 1000) + ' m';
            } else {
                return km.toFixed(1) + ' km';
            }
        }

        function clearNavigationMarkers() {
            if (userMarker) {
                map.removeLayer(userMarker);
                userMarker = null;
            }
            
            if (routeLine) {
                map.removeLayer(routeLine);
                routeLine = null;
            }
            
            if (nextStepMarker) {
                map.removeLayer(nextStepMarker);
                nextStepMarker = null;
            }
        }

        // ========== FUNCIONES DE MAPA BÁSICAS ==========
        function locateUser() {
            if (navigator.geolocation) {
                map.locate({
                    setView: true,
                    maxZoom: 16,
                    enableHighAccuracy: true
                });
                
                map.once('locationfound', function(e) {
                    const radius = e.accuracy / 2;
                    
                    const locationMarker = L.marker(e.latlng, {
                        icon: L.divIcon({
                            className: 'current-position-marker',
                            html: '<div style="width: 16px; height: 16px;"></div>',
                            iconSize: [16, 16],
                            iconAnchor: [8, 8]
                        })
                    }).addTo(map);
                    
                    const accuracyCircle = L.circle(e.latlng, {
                        radius: radius,
                        color: '#ff9800',
                        fillColor: '#ff9800',
                        fillOpacity: 0.15
                    }).addTo(map);
                    
                    locationMarker.bindPopup("📍 Tu ubicación actual (precisión: " + Math.round(radius) + "m)").openPopup();
                    
                    currentMarkers.push(locationMarker);
                    currentMarkers.push(accuracyCircle);
                });
                
                map.once('locationerror', function(e) {
                    showError("❌ Error al obtener ubicación: " + e.message);
                });
            } else {
                showError("❌ Tu navegador no soporta geolocalización");
            }
        }

        function fitMapToRoute() {
            if (routeBounds && routeBounds.isValid()) {
                map.fitBounds(routeBounds, {
                    padding: [50, 50]
                });
            } else if (currentPolylines.length > 0) {
                const allBounds = L.latLngBounds([]);
                currentPolylines.forEach(line => {
                    if (line.getBounds) {
                        allBounds.extend(line.getBounds());
                    }
                });
                
                if (allBounds.isValid()) {
                    map.fitBounds(allBounds, {
                        padding: [50, 50]
                    });
                }
            } else {
                showError("❌ No hay rutas para ajustar la vista");
            }
        }

        function toggleMapView() {
            if (isSatelliteView) {
                map.removeLayer(satelliteLayer);
                tileLayer.addTo(map);
                isSatelliteView = false;
                document.getElementById('toggle-satellite').textContent = '🌍 Satélite';
            } else {
                map.removeLayer(tileLayer);
                satelliteLayer.addTo(map);
                isSatelliteView = true;
                document.getElementById('toggle-satellite').textContent = '🗺️ Mapa';
            }
        }

        function showError(message) {
            const errorEl = document.getElementById('error-message');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 5000);
            
            console.error('❌ Error:', message);
        }

        function clearMap() {
            // Limpiar polylines
            currentPolylines.forEach(poly => {
                if (map.hasLayer(poly)) {
                    map.removeLayer(poly);
                }
            });
            currentPolylines = [];
            
            // Limpiar marcadores
            currentMarkers.forEach(marker => {
                if (map.hasLayer(marker)) {
                    map.removeLayer(marker);
                }
            });
            currentMarkers = [];
            
            // Limpiar clusters
            markerClusters.clearLayers();
            
            routeBounds = null;
            document.getElementById('route-info').style.display = 'none';
        }

        // ========== FUNCIONES DE RUTAS (MODO BÁSICO) ==========
        function findRouteById(vehicleId) {
            const searchId = Number(vehicleId);
            return routesData.find(route => Number(route.vehicle) === searchId);
        }

        function showRoute(vehicleId) {
            try {
                if (isMotorista && !isAdmin && vehicleId != userId) {
                    vehicleId = userId;
                }
                
                const route = findRouteById(vehicleId);
                currentVehicleId = vehicleId;
                
                clearMap();

                if (!route) {
                    showError(`❌ No se encontró ruta para el vehículo ${vehicleId}`);
                    return;
                }

                if (!route.geometry) {
                    showError(`❌ La ruta del vehículo ${vehicleId} no tiene geometría`);
                    return;
                }

                // Decodificar la polilínea
                const decoded = polyline.decode(route.geometry);
                const latlngs = decoded.map(p => L.latLng(p[0], p[1]));
                
                routeBounds = L.latLngBounds(latlngs);

                // Dibujar la ruta
                const routeColor = colors[vehicleId % colors.length];
                const routeLine = L.polyline(latlngs, {
                    color: routeColor,
                    weight: 5,
                    opacity: 0.7,
                    lineCap: 'round',
                    lineJoin: 'round'
                }).addTo(map);
                
                currentPolylines.push(routeLine);

                // Añadir marcadores para los pasos
                if (route.steps && Array.isArray(route.steps)) {
                    route.steps.forEach((step, index) => {
                        if (!step.location || !Array.isArray(step.location) || step.location.length < 2) {
                            return;
                        }

                        const [lng, lat] = step.location;
                        
                        let iconClass = 'job-marker';
                        let iconHtml = '';
                        
                        if (step.type === 'start') {
                            iconClass = 'start-marker';
                            iconHtml = '<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">S</div>';
                        } else if (step.type === 'end') {
                            iconClass = 'end-marker';
                            iconHtml = '<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">E</div>';
                        } else if (step.type === 'job') {
                            iconHtml = '<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">' + (index) + '</div>';
                        }
                        
                        const marker = L.marker([lat, lng], {
                            icon: L.divIcon({
                                className: iconClass,
                                html: iconHtml,
                                iconSize: [24, 24],
                                iconAnchor: [12, 12]
                            })
                        });

                        let popupContent = `
                            <div>
                                <h4>🚛 Vehículo ${vehicleId} - ${route.vehicle_name}</h4>
                                <div><strong>Tipo:</strong> ${step.type.toUpperCase()}</div>
                        `;
                        
                        if (step.job && jobsData) {
                            const job = jobsData.find(j => j.id == step.job);
                            if (job) {
                                popupContent += `
                                    <div><strong>Cliente:</strong> ${job.cliente}</div>
                                    <div><strong>ID Pedido:</strong> ${step.job}</div>
                                `;
                            }
                        }
                        
                        popupContent += `</div>`;
                        
                        marker.bindPopup(popupContent);
                        markerClusters.addLayer(marker);
                        
                        if (index === 0 || index === route.steps.length - 1) {
                            map.addLayer(marker);
                            currentMarkers.push(marker);
                        }
                    });
                }

                map.fitBounds(routeBounds);
                
                // Resaltar botón activo
                document.querySelectorAll('#vehicle-buttons button').forEach(btn => {
                    btn.classList.remove('active-route');
                });
                const activeButton = document.getElementById(`vehicle-${vehicleId}`);
                if (activeButton) {
                    activeButton.classList.add('active-route');
                }
                
                updateRouteInfo(route);
                document.getElementById('error-message').style.display = 'none';

            } catch (error) {
                console.error('❌ Error al mostrar ruta:', error);
                showError(`Error técnico: ${error.message}`);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function updateRouteInfo(route) {
            const routeInfoEl = document.getElementById('route-info');
            const routeDetailsEl = document.getElementById('route-details');
            
            if (!route || !route.steps || route.steps.length === 0) {
                routeInfoEl.style.display = 'none';
                return;
            }
            
            let totalDistance = 0;
            let totalDuration = 0;
            let jobsCount = 0;
            
            route.steps.forEach(step => {
                if (step.distance) totalDistance += step.distance;
                if (step.duration) totalDuration += step.duration;
                if (step.type === 'job') jobsCount++;
            });
            
            const formattedDistance = (totalDistance / 1000).toFixed(2) + ' km';
            const formattedDuration = secondsToTime(totalDuration);
            
            let infoHtml = `
                <div><strong>🚛 Vehículo:</strong> ${route.vehicle_name}</div>
                <div><strong>📏 Distancia:</strong> ${formattedDistance}</div>
                <div><strong>⏱️ Tiempo estimado:</strong> ${formattedDuration}</div>
                <div><strong>📦 Entregas:</strong> ${jobsCount}</div>
            `;
            
            routeDetailsEl.innerHTML = infoHtml;
            routeInfoEl.style.display = 'block';
        }

        function secondsToTime(seconds) {
            if (!seconds) return 'N/A';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            
            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } else {
                return `${minutes} minutos`;
            }
        }

        function showAllRoutes() {
            if (!isAdmin) {
                showError("❌ No tienes permiso para ver todas las rutas");
                return;
            }

            try {
                currentVehicleId = null;
                clearMap();
                
                const allBounds = L.latLngBounds([]);

                routesData.forEach((route, index) => {
                    if (!route.geometry) return;

                    const decoded = polyline.decode(route.geometry);
                    const latlngs = decoded.map(p => L.latLng(p[0], p[1]));
                    
                    latlngs.forEach(point => allBounds.extend(point));

                    const routeColor = colors[index % colors.length];
                    const routeLine = L.polyline(latlngs, {
                        color: routeColor,
                        weight: 5,
                        opacity: 0.7
                    }).addTo(map);
                    
                    routeLine.bindTooltip(`🚛 ${route.vehicle_name} (ID: ${route.vehicle})`);
                    currentPolylines.push(routeLine);

                    if (route.steps && route.steps.length > 0) {
                        const startStep = route.steps[0];
                        const endStep = route.steps[route.steps.length - 1];
                        
                        [startStep, endStep].forEach((step, stepIdx) => {
                            if (!step.location || !Array.isArray(step.location) || step.location.length < 2) {
                                return;
                            }

                            const [lng, lat] = step.location;
                            
                            const iconClass = stepIdx === 0 ? 'start-marker' : 'end-marker';
                            const iconHtml = `<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">${stepIdx === 0 ? 'S' : 'E'}</div>`;
                            
                            const marker = L.marker([lat, lng], {
                                icon: L.divIcon({
                                    className: iconClass,
                                    html: iconHtml,
                                    iconSize: [24, 24],
                                    iconAnchor: [12, 12]
                                })
                            });
                            
                            let popupContent = `
                                <div>
                                    <h4>🚛 Vehículo ${route.vehicle} - ${route.vehicle_name}</h4>
                                    <div><strong>Tipo:</strong> ${step.type.toUpperCase()}</div>
                            `;
                            
                            if (step.job && jobsData) {
                                const job = jobsData.find(j => j.id == step.job);
                                if (job) {
                                    popupContent += `
                                        <div><strong>Cliente:</strong> ${job.cliente}</div>
                                        <div><strong>ID Pedido:</strong> ${step.job}</div>
                                    `;
                                }
                            }
                            
                            popupContent += `</div>`;
                            
                            marker.bindPopup(popupContent);
                            markerClusters.addLayer(marker);
                        });
                    }
                });

                routeBounds = allBounds;
                
                if (routeBounds.isValid()) {
                    map.fitBounds(routeBounds, {
                        padding: [50, 50]
                    });
                }

                document.querySelectorAll('#vehicle-buttons button').forEach(btn => {
                    btn.classList.remove('active-route');
                });

                showAllRoutesInfo();
                document.getElementById('error-message').style.display = 'none';

            } catch (error) {
                console.error('❌ Error al mostrar rutas:', error);
                showError(`Error técnico: ${error.message}`);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        function showAllRoutesInfo() {
            const routeInfoEl = document.getElementById('route-info');
            const routeDetailsEl = document.getElementById('route-details');
            
            if (!routesData || routesData.length === 0) {
                routeInfoEl.style.display = 'none';
                return;
            }
            
            let totalJobs = 0;
            let totalVehicles = routesData.length;
            
            routesData.forEach(route => {
                if (route.steps) {
                    route.steps.forEach(step => {
                        if (step.type === 'job') totalJobs++;
                    });
                }
            });
            
            let infoHtml = `
                <div><strong>🚛 Vehículos:</strong> ${totalVehicles}</div>
                <div><strong>📦 Entregas totales:</strong> ${totalJobs}</div>
                <div><strong>👁️ Vista:</strong> Todas las rutas</div>
            `;
            
            routeDetailsEl.innerHTML = infoHtml;
            routeInfoEl.style.display = 'block';
        }

        // ========== INICIALIZACIÓN ==========
        function initializeView() {
            initMap();
            
            document.getElementById('loading').style.display = 'none';
            
            if (routesData.length === 0) {
                showError('❌ No hay rutas disponibles para mostrar');
                return;
            }
            
            console.log('🚀 Rutas disponibles:', routesData.map(r => r.vehicle));
            
            if (isMotorista && !isAdmin) {
                const userRoute = findRouteById(userId);
                
                if (userRoute) {
                    console.log('👤 Mostrando ruta del motorista:', userRoute);
                    showRoute(userId);
                    return;
                } else {
                    showError(`❌ No se encontró una ruta asignada para ti.`);
                    return;
                }
            }
            
            if (isAdmin && routesData.length > 1) {
                console.log('👨‍💼 Admin: Mostrando todas las rutas');
                showAllRoutes();
                return;
            }
            
            console.log('📍 Mostrando primera ruta disponible');
            showRoute(routesData[0].vehicle);
        }

        // ========== INICIO DE LA APLICACIÓN ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Iniciando aplicación de navegación...');
            
            // Verificar bibliotecas necesarias
            if (typeof polyline === 'undefined') {
                showError('❌ Error: Biblioteca de polyline no disponible');
                return;
            }
            
            if (typeof L === 'undefined') {
                showError('❌ Error: Leaflet no disponible');
                return;
            }
            
            // Verificar soporte de síntesis de voz
            if (!speechSynthesis) {
                console.warn('⚠️ Síntesis de voz no disponible');
                voiceEnabled = false;
                document.getElementById('voice-toggle').style.display = 'none';
            }
            
            try {
                initializeView();
                console.log('✅ Aplicación inicializada correctamente');
            } catch (error) {
                console.error('❌ Error al inicializar:', error);
                showError('Error al inicializar: ' + error.message);
                document.getElementById('loading').style.display = 'none';
            }
        });

        // Agregar CSS para las animaciones
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounceIn {
                0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
                50% { transform: translate(-50%, -50%) scale(1.05); }
                70% { transform: translate(-50%, -50%) scale(0.9); }
                100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>