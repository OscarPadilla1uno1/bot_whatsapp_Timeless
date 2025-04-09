<!DOCTYPE html>
<html>
<head>
    <title>Rutas de Vehículos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 100vh; }
        .control-panel {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            max-width: 300px;
        }
        .control-panel button {
            margin: 5px 0;
            padding: 8px 12px;
            width: 100%;
            cursor: pointer;
        }
        .active-route {
            background-color: #007bff;
            color: white;
        }
        #error-message {
            color: red;
            margin-top: 10px;
            padding: 10px;
            background: #ffeeee;
            border-radius: 4px;
            display: none;
        }
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <div class="control-panel">
        <h3>Vehículos</h3>
        <div id="vehicle-buttons">
            @foreach($routes as $route)
                <button onclick="showRoute({{ $route['vehicle'] }})" 
                        id="vehicle-{{ $route['vehicle'] }}">
                    Vehículo {{ $route['vehicle'] }}
                </button>
            @endforeach
            <button onclick="showAllRoutes()">Mostrar Todos</button>
        </div>
        
        <div id="error-message"></div>
    </div>

    <div id="loading">
        <h2>Cargando mapa y rutas...</h2>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>

    <script>
        // Mostrar loading inicial
        document.getElementById('loading').style.display = 'flex';
        
        // Datos desde PHP
        const routesData = @json($routes);
        const vehiclesData = @json($vehicles);
        const jobsData = @json($jobs);
        
        // Verificar datos en consola
        console.log('Datos de rutas:', routesData);
        console.log('Datos de vehículos:', vehiclesData);
        console.log('Datos de trabajos:', jobsData);

        // Inicializar mapa
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const colors = ['#FF0000', '#0000FF', '#00FF00', '#FF00FF', '#FFFF00'];
        let currentPolylines = [];
        let currentMarkers = [];
        
        // Función para mostrar error
        function showError(message) {
            const errorEl = document.getElementById('error-message');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        // Función para mostrar una ruta
        function showRoute(vehicleId) {
            try {
                // Limpiar mapa
                clearMap();
                
                // Buscar la ruta
                const route = routesData.find(r => r.vehicle == vehicleId);
                if (!route) {
                    showError(`No se encontró ruta para el vehículo ${vehicleId}`);
                    return;
                }
                
                if (!route.geometry) {
                    showError(`La ruta del vehículo ${vehicleId} no tiene geometría`);
                    return;
                }

                // Decodificar la polilínea
                const decoded = polyline.decode(route.geometry);
                const latlngs = decoded.map(p => L.latLng(p[0], p[1]));
                
                // Dibujar la ruta
                const routeLine = L.polyline(latlngs, {
                    color: colors[vehicleId - 1],
                    weight: 5
                }).addTo(map);
                
                currentPolylines.push(routeLine);
                
                // Añadir marcadores para los pasos
                route.steps.forEach(step => {
                    const [lng, lat] = step.location;
                    const marker = L.marker([lat, lng]).addTo(map);
                    currentMarkers.push(marker);
                    
                    let popup = `<b>Vehículo ${vehicleId}</b><br>`;
                    popup += `<small>${step.type.toUpperCase()}</small>`;
                    if (step.job) {
                        const job = jobsData.find(j => j.id == step.job);
                        if (job) popup += `<br>Cliente: ${job.cliente}`;
                    }
                    
                    marker.bindPopup(popup);
                });
                
                // Ajustar vista
                map.fitBounds(routeLine.getBounds());
                
                // Ocultar error si todo está bien
                document.getElementById('error-message').style.display = 'none';
                
            } catch (error) {
                console.error('Error al mostrar ruta:', error);
                showError(`Error técnico al mostrar la ruta: ${error.message}`);
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Función para limpiar el mapa
        function clearMap() {
            currentPolylines.forEach(poly => map.removeLayer(poly));
            currentMarkers.forEach(marker => map.removeLayer(marker));
            currentPolylines = [];
            currentMarkers = [];
        }

        // Mostrar la primera ruta al cargar
        if (routesData.length > 0) {
            showRoute(routesData[0].vehicle);
        } else {
            showError('No hay rutas disponibles para mostrar');
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>