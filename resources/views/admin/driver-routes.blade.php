<!DOCTYPE html>
<html>
<head>
    <title>Panel Admin - Rutas de Motoristas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .admin-header {
            background: #343a40;
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .admin-content {
            margin-top: 70px;
            display: flex;
            height: calc(100vh - 70px);
        }

        .drivers-sidebar {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            padding: 20px;
        }

        .drivers-list {
            margin-bottom: 20px;
        }

        .driver-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .driver-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .driver-card.active {
            border-color: #007bff;
            background: #f0f8ff;
        }

        .driver-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .driver-email {
            color: #666;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .driver-vehicle {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            display: inline-block;
        }

        .driver-stats {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        .control-buttons {
            margin-bottom: 20px;
        }

        .control-buttons button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        #map {
            flex: 1;
            height: 100%;
        }

        .route-legend {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .legend-color {
            width: 20px;
            height: 4px;
            margin-right: 10px;
            border-radius: 2px;
        }

        .error-banner {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }

        .no-data-message {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px 20px;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1002;
        }
    </style>
</head>

<body>
    <div class="admin-header">
        <h1>Panel de Administración - Rutas de Motoristas</h1>
    </div>

    <div class="admin-content">
        <div class="drivers-sidebar">
            @if(session('error'))
                <div class="error-banner">
                    {{ session('error') }}
                </div>
            @endif

            <div class="control-buttons">
                <button class="btn-primary" onclick="showAllRoutes()">
                    Mostrar Todas las Rutas
                </button>
                <button class="btn-secondary" onclick="clearMap()">
                    Limpiar Mapa
                </button>
                <button class="btn-success" onclick="refreshRoutes()">
                    Actualizar Rutas
                </button>
            </div>

            @if(count($drivers) > 0)
                <div class="drivers-list">
                    <h3>Motoristas Disponibles ({{ count($drivers) }})</h3>
                    @foreach($drivers as $driver)
                        <div class="driver-card" onclick="showDriverRoute({{ $driver['driver_id'] }})" id="driver-card-{{ $driver['driver_id'] }}">
                            <div class="driver-name">{{ $driver['driver_name'] }}</div>
                            <div class="driver-email">{{ $driver['driver_email'] }}</div>
                            <div class="driver-vehicle">Vehículo #{{ $driver['id'] }}</div>
                            <div class="driver-stats">
                                @php
                                    $driverRoute = collect($routes)->firstWhere('driver_id', $driver['driver_id']);
                                    $deliveryCount = 0;
                                    if ($driverRoute && isset($driverRoute['steps']) && is_array($driverRoute['steps'])) {
                                        $deliveryCount = count(array_filter($driverRoute['steps'], function($step) { 
                                            return isset($step['type']) && $step['type'] === 'job'; 
                                        }));
                                    }
                                @endphp
                                <div>Entregas asignadas: {{ $deliveryCount }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-data-message">
                    No hay motoristas registrados en el sistema.
                </div>
            @endif
        </div>

        <div style="position: relative; flex: 1;">
            <div id="map"></div>
            
            @if(count($routes) > 0)
                <div class="route-legend">
                    <h4 style="margin: 0 0 10px 0;">Leyenda de Rutas</h4>
                    @foreach($routes as $index => $route)
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: {{ ['#FF0000', '#0000FF', '#00FF00', '#FF00FF', '#FFFF00', '#FFA500', '#800080', '#00FFFF'][$index % 8] }};"></div>
                            <span>{{ $route['driver_name'] }} (Vehículo #{{ $route['vehicle'] }})</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div id="loading" class="loading-overlay" style="display: none;">
                <h2>Actualizando rutas...</h2>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>

    <script>
        // Datos desde PHP
        const routesData = @json($routes);
        const driversData = @json($drivers);
        const jobsData = @json($jobs);

        // Inicializar mapa
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const colors = ['#FF0000', '#0000FF', '#00FF00', '#FF00FF', '#FFFF00', '#FFA500', '#800080', '#00FFFF'];
        let currentPolylines = [];
        let currentMarkers = [];

        // Función para limpiar el mapa
        function clearMap() {
            currentPolylines.forEach(poly => map.removeLayer(poly));
            currentMarkers.forEach(marker => map.removeLayer(marker));
            currentPolylines = [];
            currentMarkers = [];
            
            // Remover clase active de todas las tarjetas
            document.querySelectorAll('.driver-card').forEach(card => {
                card.classList.remove('active');
            });
        }

        // Función para mostrar la ruta de un motorista específico
        function showDriverRoute(driverId) {
            // Limpiar mapa
            clearMap();

            // Buscar la ruta del motorista
            const route = routesData.find(r => r.driver_id == driverId);
            if (!route) {
                alert(`No se encontró ruta para el motorista con ID ${driverId}`);
                return;
            }

            if (!route.geometry) {
                alert(`La ruta del motorista no tiene geometría válida`);
                return;
            }

            try {
                // Decodificar la polilínea
                const decoded = polyline.decode(route.geometry);
                const latlngs = decoded.map(p => L.latLng(p[0], p[1]));

                // Color basado en el índice del vehículo
                const colorIndex = (route.vehicle - 1) % colors.length;
                const routeColor = colors[colorIndex];

                // Dibujar la ruta
                const routeLine = L.polyline(latlngs, {
                    color: routeColor,
                    weight: 5,
                    opacity: 0.8
                }).addTo(map);

                currentPolylines.push(routeLine);

                // Añadir marcadores para los pasos
                route.steps.forEach((step, index) => {
                    const [lng, lat] = step.location;
                    const marker = L.marker([lat, lng]).addTo(map);
                    currentMarkers.push(marker);

                    let popup = `<b>${route.driver_name}</b><br>`;
                    popup += `<small>${step.type.toUpperCase()}</small>`;
                    if (step.job_details) {
                        popup += `<br>Cliente: ${step.job_details.cliente}`;
                    }
                    popup += `<br><small>Parada ${index + 1}</small>`;

                    marker.bindPopup(popup);
                });

                // Ajustar vista
                map.fitBounds(routeLine.getBounds());

                // Marcar tarjeta como activa
                document.querySelectorAll('.driver-card').forEach(card => {
                    card.classList.remove('active');
                });
                const driverCard = document.getElementById(`driver-card-${driverId}`);
                if (driverCard) {
                    driverCard.classList.add('active');
                }

            } catch (error) {
                console.error('Error al mostrar ruta:', error);
                alert(`Error técnico al mostrar la ruta: ${error.message}`);
            }
        }

        // Función para mostrar todas las rutas
        function showAllRoutes() {
            clearMap();

            if (routesData.length === 0) {
                alert('No hay rutas disponibles para mostrar');
                return;
            }

            const allBounds = new L.LatLngBounds();

            routesData.forEach((route, index) => {
                if (!route.geometry) {
                    console.warn('Ruta sin geometría:', route);
                    return;
                }

                try {
                    // Decodificar la polilínea
                    const decoded = polyline.decode(route.geometry);
                    const latlngs = decoded.map(p => L.latLng(p[0], p[1]));

                    // Color único para cada ruta
                    const colorIndex = index % colors.length;
                    const routeColor = colors[colorIndex];

                    // Dibujar la ruta
                    const routeLine = L.polyline(latlngs, {
                        color: routeColor,
                        weight: 4,
                        opacity: 0.8
                    }).addTo(map);

                    currentPolylines.push(routeLine);
                    allBounds.extend(routeLine.getBounds());

                    // Añadir marcadores para los pasos
                    route.steps.forEach((step, stepIndex) => {
                        const [lng, lat] = step.location;
                        const marker = L.marker([lat, lng]).addTo(map);
                        currentMarkers.push(marker);

                        let popup = `<b>${route.driver_name}</b><br>`;
                        popup += `<small>${step.type.toUpperCase()}</small>`;
                        if (step.job_details) {
                            popup += `<br>Cliente: ${step.job_details.cliente}`;
                        }
                        popup += `<br><small>Parada ${stepIndex + 1}</small>`;

                        marker.bindPopup(popup);
                    });

                } catch (error) {
                    console.error('Error al mostrar ruta:', error);
                }
            });

            // Ajustar vista para mostrar todas las rutas
            if (allBounds.isValid()) {
                map.fitBounds(allBounds, { padding: [20, 20] });
            }
        }

        // Función para refrescar rutas
        function refreshRoutes() {
            document.getElementById('loading').style.display = 'flex';
            window.location.reload();
        }

        // Mostrar todas las rutas al cargar si existen
        if (routesData.length > 0) {
            showAllRoutes();
        }
    </script>
</body>
</html>