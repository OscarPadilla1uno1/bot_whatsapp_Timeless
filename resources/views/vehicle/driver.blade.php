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

        /* Estilos mejorados para la lista de entregas */
        .delivery-item {
            padding: 12px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 13px;
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }

        .delivery-item:hover {
            background: #e9ecef;
            transform: translateX(2px);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .delivery-client {
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }

        .delivery-number {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .delivery-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .delivery-btn {
            flex: 1;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delivery-btn:hover {
            transform: translateY(-1px);
        }

        .btn-delivery {
            background-color: #28a745;
            color: white;
        }

        .btn-delivery:hover {
            background-color: #1e7e34;
        }

        .btn-return {
            background-color: #dc3545;
            color: white;
        }

        .btn-return:hover {
            background-color: #c82333;
        }

        /* Estados de entrega */
        .delivery-item.completed {
            background: #d4edda;
            border-left-color: #28a745;
            opacity: 0.8;
        }

        .delivery-item.completed .delivery-client {
            color: #155724;
        }

        .delivery-item.returned {
            background: #f8d7da;
            border-left-color: #dc3545;
            opacity: 0.8;
        }

        .delivery-item.returned .delivery-client {
            color: #721c24;
        }

        .delivery-status {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            text-align: center;
            margin-top: 4px;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-completed {
            background-color: #28a745;
        }

        .status-returned {
            background-color: #dc3545;
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

            .delivery-actions {
                flex-direction: column;
            }

            .delivery-btn {
                margin-bottom: 4px;
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

        /* Notificaciones */
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 2000;
            font-weight: bold;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification.warning {
            background: #ffc107;
            color: #212529;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <div class="driver-panel">
        <div class="panel-header" onclick="togglePanel()">
            <h3>
                <span class="status-indicator status-online" id="status-indicator"></span>
                {{ $driver->name }}
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
                                    <div class="delivery-item" data-delivery-id="{{ $step['job'] }}" id="delivery-{{ $step['job'] }}">
                                        <div class="delivery-header">
                                            <div class="delivery-client">{{ $step['job_details']['cliente'] ?? 'Cliente desconocido' }}</div>
                                            <div class="delivery-number">Parada {{ $index + 1 }}</div>
                                        </div>
                                        <div class="delivery-actions">
                                            <button class="delivery-btn btn-delivery" onclick="markDelivery('{{ $step['job'] }}', 'completed')">
                                                ✅ Entregado
                                            </button>
                                            <button class="delivery-btn btn-return" onclick="markDelivery('{{ $step['job'] }}', 'returned')">
                                                🔄 Devolución
                                            </button>
                                        </div>
                                        <div class="delivery-status status-pending">Pendiente</div>
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

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="https://unpkg.com/leaflet-realtime@2.2.0/dist/leaflet-realtime.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/stefanocudini/leaflet-gps@master/dist/leaflet-gps.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        // Variables globales
        const driverRoute = @json($route);
        const jobsData = @json($jobs);
        let deliveryStatuses = {}; // Para rastrear el estado de cada entrega

        // Inicializar mapa
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Variables del sistema (mismas que el código original)
        let routingControl = null;
        let realtimeLayer = null;
        let gpsControl = null;
        let currentMarkers = [];
        let isRealTimeActive = false;
        let currentDeliveryIndex = 0;
        let watchId = null;
        let currentLocationMarker = null;
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

        // Función principal para marcar entrega
        function markDelivery(deliveryId, status) {
            const deliveryItem = document.getElementById(`delivery-${deliveryId}`);
            if (!deliveryItem) return;

            // Actualizar estado interno
            deliveryStatuses[deliveryId] = status;

            // Remover clases anteriores
            deliveryItem.classList.remove('completed', 'returned');
            
            // Aplicar nueva clase y actualizar interfaz
            if (status === 'completed') {
                deliveryItem.classList.add('completed');
                updateDeliveryItemUI(deliveryItem, 'Entregado', 'status-completed');
                showNotification(`Entrega marcada como completada`, 'success');
            } else if (status === 'returned') {
                deliveryItem.classList.add('returned');
                updateDeliveryItemUI(deliveryItem, 'Devuelto', 'status-returned');
                showNotification(`Entrega marcada como devolución`, 'warning');
            }

            // Deshabilitar botones para esta entrega
            const buttons = deliveryItem.querySelectorAll('.delivery-btn');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
            });

            // Actualizar estadísticas
            updateDeliveryStats();

            // Enviar al servidor (opcional)
            sendDeliveryUpdate(deliveryId, status);

            // Actualizar marcador en el mapa si existe
            updateMapMarker(deliveryId, status);
        }

        // Función para actualizar la interfaz del item de entrega
        function updateDeliveryItemUI(deliveryItem, statusText, statusClass) {
            const statusElement = deliveryItem.querySelector('.delivery-status');
            if (statusElement) {
                statusElement.textContent = statusText;
                statusElement.className = `delivery-status ${statusClass}`;
            }
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 3000);
        }

        // Función para actualizar estadísticas
        function updateDeliveryStats() {
            const completed = Object.values(deliveryStatuses).filter(status => status === 'completed').length;
            const returned = Object.values(deliveryStatuses).filter(status => status === 'returned').length;
            const total = Object.keys(deliveryStatuses).length;
            const pending = total - completed - returned;

            document.getElementById('completed-count').textContent = completed;
            document.getElementById('pending-count').textContent = pending;
        }

        // Función para enviar actualización al servidor usando las rutas específicas
        function sendDeliveryUpdate(deliveryId, status) {
            if (!axios) {
                console.warn('Axios no disponible para enviar actualización');
                return;
            }

            // Configurar token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

            const baseData = {
                delivery_id: deliveryId,
                driver_id: {{ $driver->id ?? 'null' }},
                timestamp: new Date().toISOString(),
                latitude: currentLocationMarker ? currentLocationMarker.getLatLng().lat : null,
                longitude: currentLocationMarker ? currentLocationMarker.getLatLng().lng : null
            };

            let requestPromise;

            // Usar ruta específica según el estado
            if (status === 'completed') {
                // Usar ruta específica para completar entrega
                requestPromise = axios.post('/mark-delivery-completed', {
                    ...baseData,
                    notes: 'Entrega completada desde interfaz móvil'
                });
            } else if (status === 'returned') {
                // Usar ruta específica para devolver entrega
                requestPromise = axios.post('/mark-delivery-returned', {
                    ...baseData,
                    reason: 'Devolución registrada desde interfaz móvil'
                });
            } else {
                // Usar ruta general para otros estados
                requestPromise = axios.post('/update-delivery-status', {
                    ...baseData,
                    status: status
                });
            }

            requestPromise
                .then(response => {
                    console.log('Estado de entrega actualizado en servidor:', response.data);
                    if (response.data.success) {
                        showNotification(response.data.message || 'Actualización exitosa', 'success');
                    } else {
                        showNotification(response.data.error || 'Error en la respuesta', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error actualizando estado en servidor:', error);
                    console.error('Error response:', error.response);
                    console.error('Error data:', error.response?.data);
                    
                    let errorMessage = 'Error al sincronizar con servidor';
                    
                    if (error.response) {
                        console.error('Status:', error.response.status);
                        console.error('Response data:', error.response.data);
                        
                        if (error.response.status === 500) {
                            errorMessage = 'Error interno del servidor (500). Revisa los logs de Laravel.';
                        } else if (error.response.status === 404) {
                            errorMessage = 'Ruta no encontrada (404). Verifica que las rutas estén definidas.';
                        } else if (error.response.status === 422) {
                            errorMessage = 'Datos inválidos (422): ' + JSON.stringify(error.response.data.errors || error.response.data.message);
                        } else if (error.response.data) {
                            errorMessage = error.response.data.error || error.response.data.message || errorMessage;
                        }
                    }
                    
                    showNotification(errorMessage, 'error');
                });
        }

        // Función para actualizar marcador en el mapa
        function updateMapMarker(deliveryId, status) {
            // Buscar el marcador correspondiente en currentMarkers
            currentMarkers.forEach(marker => {
                if (marker.options && marker.options.deliveryId === deliveryId) {
                    let newIcon, newColor;
                    
                    if (status === 'completed') {
                        newIcon = '✅';
                        newColor = '#28a745';
                    } else if (status === 'returned') {
                        newIcon = '🔄';
                        newColor = '#dc3545';
                    }

                    // Actualizar icono del marcador
                    marker.setIcon(L.divIcon({
                        className: 'delivery-marker-updated',
                        html: `<div style="background-color: ${newColor}; width: 35px; height: 35px; border-radius: 50%; border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; font-size: 16px;">${newIcon}</div>`,
                        iconSize: [35, 35],
                        iconAnchor: [17, 17]
                    }));
                }
            });
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

        // Resto de funciones del sistema original (simplificadas para el ejemplo)
        function showOptimizedRoute() {
            console.log('Mostrando ruta optimizada...');
            document.getElementById('loading').style.display = 'none';
        }

        function toggleRealTimeTracking() {
            isRealTimeActive = !isRealTimeActive;
            const button = document.getElementById('follow-button');
            
            if (isRealTimeActive) {
                button.textContent = '⏹️ Detener';
                button.classList.remove('btn-success');
                button.classList.add('btn-danger');
                showNotification('Seguimiento en tiempo real activado');
            } else {
                button.textContent = '🚀 Seguimiento';
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');
                showNotification('Seguimiento en tiempo real desactivado');
            }
        }

        function toggleNavigation() {
            isNavigating = !isNavigating;
            const button = document.getElementById('navigation-button');
            const panel = document.getElementById('navigation-panel');
            
            if (isNavigating) {
                button.textContent = '⏹️ Detener Nav';
                button.classList.remove('btn-success');
                button.classList.add('btn-danger');
                panel.style.display = 'block';
                showNotification('Navegación activada');
            } else {
                button.textContent = '🧭 Navegación';
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');
                panel.style.display = 'none';
                showNotification('Navegación desactivada');
            }
        }

        function recalculateRoute() {
            showNotification('Recalculando ruta...');
            // Lógica de recálculo aquí
        }

        function emergencyStop() {
            if (confirm('¿Estás seguro de que quieres activar el modo emergencia?')) {
                showNotification('Señal de emergencia enviada', 'error');
                // Lógica de emergencia aquí
            }
        }

        function toggleVoiceNavigation() {
            voiceEnabled = !voiceEnabled;
            const button = document.getElementById('voice-button');
            
            if (voiceEnabled) {
                button.textContent = '🔊 Voz ON';
                button.style.backgroundColor = '#28a745';
            } else {
                button.textContent = '🔇 Voz OFF';
                button.style.backgroundColor = '#dc3545';
            }
        }

        function centerOnLocation() {
            if (currentLocationMarker) {
                map.setView(currentLocationMarker.getLatLng(), 18);
            } else {
                showNotification('No se ha establecido la ubicación actual', 'warning');
            }
        }

        // Inicialización del sistema
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar estados de entrega
            if (driverRoute && driverRoute.steps) {
                driverRoute.steps.forEach(step => {
                    if (step.type === 'job') {
                        deliveryStatuses[step.job] = 'pending';
                    }
                });
            }

            // Cerrar panel en móviles
            if (window.innerWidth <= 768) {
                document.getElementById('panel-content').classList.add('collapsed');
                document.getElementById('toggle-icon').classList.add('collapsed');
                document.getElementById('toggle-icon').textContent = '▲';
            }

            // Ocultar loading después de inicializar
            setTimeout(() => {
                document.getElementById('loading').style.display = 'none';
            }, 1000);

            console.log('Sistema de entregas inicializado correctamente');
        });

        // Funciones adicionales del sistema original que necesitas
        function showError(message) {
            const errorEl = document.getElementById('error-message');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 5000);
        }

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

        // Función para resetear una entrega usando la ruta específica
        function resetDelivery(deliveryId) {
            if (confirm('¿Estás seguro de que quieres resetear esta entrega?')) {
                const deliveryItem = document.getElementById(`delivery-${deliveryId}`);
                if (!deliveryItem) return;

                // Configurar token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

                // Llamar a la ruta específica de reset
                axios.post('/reset-delivery-status', {
                    delivery_id: deliveryId,
                    reason: 'Reset desde interfaz de motorista'
                })
                .then(response => {
                    if (response.data.success) {
                        // Resetear estado visual
                        delete deliveryStatuses[deliveryId];
                        deliveryItem.classList.remove('completed', 'returned');
                        
                        // Habilitar botones
                        const buttons = deliveryItem.querySelectorAll('.delivery-btn');
                        buttons.forEach(btn => {
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            btn.style.cursor = 'pointer';
                        });

                        // Resetear estado visual
                        updateDeliveryItemUI(deliveryItem, 'Pendiente', 'status-pending');
                        updateDeliveryStats();
                        showNotification(response.data.message || 'Entrega reseteada correctamente');
                    } else {
                        showNotification(response.data.error || 'Error al resetear entrega', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error reseteando entrega:', error);
                    let errorMessage = 'Error al resetear entrega';
                    
                    if (error.response && error.response.status === 403) {
                        errorMessage = 'No tienes permisos para resetear entregas';
                    } else if (error.response && error.response.data && error.response.data.error) {
                        errorMessage = error.response.data.error;
                    }
                    
                    showNotification(errorMessage, 'error');
                });
            }
        }

        // Función para obtener estado de entregas usando la ruta específica
        function loadDeliveryStatus(driverId = null) {
            const targetDriverId = driverId || {{ $driver->id ?? 'null' }};
            
            axios.get(`/delivery-status/${targetDriverId}`)
                .then(response => {
                    if (response.data.success) {
                        const data = response.data.data;
                        console.log('Estado de entregas cargado:', data);
                        
                        // Actualizar estadísticas en la interfaz
                        if (data.estadisticas) {
                            document.getElementById('completed-count').textContent = data.estadisticas.entregados || 0;
                            document.getElementById('pending-count').textContent = data.estadisticas.pendientes || 0;
                        }
                        
                        // Actualizar estado de cada entrega si existe en la interfaz
                        if (data.entregas) {
                            data.entregas.forEach(entrega => {
                                const deliveryItem = document.getElementById(`delivery-${entrega.id}`);
                                if (deliveryItem) {
                                    deliveryStatuses[entrega.id] = entrega.estado;
                                    
                                    // Actualizar visualmente según el estado
                                    if (entrega.estado === 'entregado') {
                                        deliveryItem.classList.add('completed');
                                        updateDeliveryItemUI(deliveryItem, 'Entregado', 'status-completed');
                                    } else if (entrega.estado === 'devuelto') {
                                        deliveryItem.classList.add('returned');
                                        updateDeliveryItemUI(deliveryItem, 'Devuelto', 'status-returned');
                                    }
                                }
                            });
                        }
                        
                        showNotification('Estados de entrega actualizados');
                    }
                })
                .catch(error => {
                    console.error('Error cargando estado de entregas:', error);
                    showNotification('Error al cargar estado de entregas', 'error');
                });
        }

        // Función para obtener historial de una entrega usando la ruta específica
        function getDeliveryHistory(deliveryId) {
            axios.get(`/delivery-history/${deliveryId}`)
                .then(response => {
                    if (response.data.success) {
                        const history = response.data.data.history;
                        console.log(`Historial de entrega ${deliveryId}:`, history);
                        
                        // Mostrar historial en consola o modal (implementar según necesidad)
                        showNotification(`Historial cargado (${history.length} eventos)`);
                    }
                })
                .catch(error => {
                    console.error('Error cargando historial:', error);
                    showNotification('Error al cargar historial', 'error');
                });
        }

        // Función para obtener resumen de entregas
        function getDeliverysSummary() {
            const completed = Object.values(deliveryStatuses).filter(status => status === 'completed').length;
            const returned = Object.values(deliveryStatuses).filter(status => status === 'returned').length;
            const total = Object.keys(deliveryStatuses).length;
            const pending = total - completed - returned;

            return {
                total: total,
                completed: completed,
                returned: returned,
                pending: pending,
                percentage: total > 0 ? Math.round((completed / total) * 100) : 0
            };
        }

        // Función para exportar datos de entregas (útil para reportes)
        function exportDeliveryData() {
            const summary = getDeliverysSummary();
            const data = {
                driver: {
                    name: '{{ $driver->name ?? "Motorista" }}',
                    email: '{{ $driver->email ?? "" }}'
                },
                summary: summary,
                deliveries: deliveryStatuses,
                timestamp: new Date().toISOString()
            };

            console.log('Datos de entregas:', data);
            showNotification('Datos exportados a consola');
            return data;
        }

        // Hacer funciones disponibles globalmente para debugging
        window.markDelivery = markDelivery;
        window.resetDelivery = resetDelivery;
        window.getDeliverysSummary = getDeliverysSummary;
        window.exportDeliveryData = exportDeliveryData;

        // Funciones del sistema de mapas y navegación (código original completo)
        
        // Función para inicializar GPS de forma segura
        function initializeGPS() {
            try {
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
            
            if (isNavigating) {
                map.setView([lat, lng], 18);
            }
        }

        // Funciones utilitarias
        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            const R = 6371;
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

        // Función para mostrar ruta con polyline
        function showRouteWithPolyline() {
            console.log('Mostrando ruta con polyline directa');
            
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

                console.log('Coordenadas decodificadas:', latlngs.length, 'puntos');

                // Dibujar ruta de entregas
                if (latlngs.length > 1) {
                    const routePolyline = L.polyline(latlngs, {
                        color: '#007bff',
                        weight: 6,
                        opacity: 0.8
                    });
                    routePolyline.addTo(map);
                    currentMarkers.push(routePolyline);
                    console.log('Ruta de entregas dibujada:', latlngs.length, 'puntos');
                }

                // Añadir marcadores para los pasos de entrega
                if (driverRoute.steps && Array.isArray(driverRoute.steps)) {
                    driverRoute.steps.forEach((step, index) => {
                        if (!step.location || !Array.isArray(step.location)) return;
                        
                        const [lng, lat] = step.location;
                        
                        let markerColor = '#007bff';
                        let markerIcon = '📍';
                        
                        if (step.type === 'start') {
                            markerColor = '#28a745';
                            markerIcon = '🏠';
                        } else if (step.type === 'end') {
                            markerColor = '#dc3545';
                            markerIcon = '🏁';
                        } else if (step.type === 'job') {
                            // Verificar estado de la entrega
                            const status = deliveryStatuses[step.job];
                            if (status === 'completed') {
                                markerColor = '#28a745';
                                markerIcon = '✅';
                            } else if (status === 'returned') {
                                markerColor = '#dc3545';
                                markerIcon = '🔄';
                            } else {
                                markerColor = '#ffc107';
                                markerIcon = '📦';
                            }
                        }

                        const marker = L.marker([lat, lng], {
                            deliveryId: step.job, // Agregar ID para poder actualizarlo
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: `<div style="background-color: ${markerColor}; width: 30px; height: 30px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold;">${markerIcon}</div>`,
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        });
                        
                        marker.addTo(map);
                        currentMarkers.push(marker);

                        let popup = `<b>${driverRoute.driver_name || 'Motorista'}</b><br>`;
                        popup += `<small>${step.type.toUpperCase()}</small>`;
                        if (step.job_details) {
                            popup += `<br>Cliente: ${step.job_details.cliente}`;
                        }
                        popup += `<br><small>Parada ${index + 1}</small>`;

                        // Agregar botones de acción si es una entrega y no tiene estado definido
                        if (step.type === 'job' && (!deliveryStatuses[step.job] || deliveryStatuses[step.job] === 'pending')) {
                            popup += `<br><div style="margin-top: 8px; display: flex; gap: 4px;">
                                <button onclick="markDelivery('${step.job}', 'completed')" style="flex: 1; padding: 4px 8px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 11px;">✅ Entregado</button>
                                <button onclick="markDelivery('${step.job}', 'returned')" style="flex: 1; padding: 4px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 11px;">🔄 Devolver</button>
                            </div>`;
                        } else if (step.type === 'job') {
                            // Mostrar estado actual si ya está definido
                            const currentStatus = deliveryStatuses[step.job];
                            const statusText = currentStatus === 'completed' ? 'Entregado' : currentStatus === 'returned' ? 'Devuelto' : 'Pendiente';
                            const statusColor = currentStatus === 'completed' ? '#28a745' : currentStatus === 'returned' ? '#dc3545' : '#ffc107';
                            
                            popup += `<br><div style="margin-top: 8px; text-align: center;">
                                <span style="padding: 4px 12px; background: ${statusColor}; color: white; border-radius: 12px; font-size: 11px; font-weight: bold;">${statusText}</span>
                            </div>`;
                        }

                        marker.bindPopup(popup);
                        
                        console.log(`Marcador ${index + 1} agregado:`, step.type, 'en', [lat, lng]);
                    });
                }

                // Ajustar vista para mostrar toda la ruta
                if (latlngs.length > 0) {
                    const allBounds = new L.LatLngBounds();
                    latlngs.forEach(coord => allBounds.extend(coord));
                    
                    map.fitBounds(allBounds, { 
                        padding: [50, 50],
                        maxZoom: 16
                    });
                    
                    console.log('Mapa ajustado a bounds:', allBounds.toBBoxString());
                }

                // Habilitar navegación
                const navButton = document.getElementById('navigation-button');
                if (navButton) {
                    navButton.disabled = false;
                    navButton.textContent = '🧭 Navegación';
                    navButton.style.opacity = '1';
                    console.log('Botón de navegación habilitado');
                }

                document.getElementById('error-message').style.display = 'none';
                console.log('Ruta de entregas mostrada correctamente');

            } catch (error) {
                console.error('Error al mostrar ruta:', error);
                showError(`Error técnico: ${error.message}`);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Inicializar sistema al cargar
        setTimeout(() => {
            initializeGPS();
            if (driverRoute) {
                showRouteWithPolyline();
            } else {
                document.getElementById('loading').style.display = 'none';
            }
        }, 1000);
    </script>
</body>
</html>