<!DOCTYPE html>
<html>
<head>
    <title>Rutas de Vehículos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 100vh; }
        .vehicle-selector {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        .vehicle-selector button {
            margin: 5px;
            padding: 5px 10px;
        }
        .active-route {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <div class="vehicle-selector">
        @foreach($routes as $route)
            <button onclick="showRoute({{ $route['vehicle'] }})" 
                    id="vehicle-{{ $route['vehicle'] }}">
                Vehículo {{ $route['vehicle'] }}
            </button>
        @endforeach
        <button onclick="showAllRoutes()">Mostrar Todos</button>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>

    <script>
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const routesData = @json($routes);
        const vehiclesData = @json($vehicles);
        const colors = ['#FF0000', '#0000FF', '#00FF00', '#FF00FF', '#FFFF00'];
        let currentPolylines = [];
        let currentMarkers = [];

        function showRoute(vehicleId) {
            // Limpiar mapa
            clearMap();
            
            // Activar botón
            document.querySelectorAll('.vehicle-selector button').forEach(btn => {
                btn.classList.remove('active-route');
            });
            document.getElementById(`vehicle-${vehicleId}`).classList.add('active-route');
            
            // Mostrar ruta del vehículo seleccionado
            const route = routesData.find(r => r.vehicle == vehicleId);
            if (route && route.geometry) {
                const decoded = polyline.decode(route.geometry);
                const latlngs = decoded.map(p => L.latLng(p[0], p[1]));
                
                const polyline = L.polyline(latlngs, {
                    color: colors[vehicleId - 1],
                    weight: 5
                }).addTo(map);
                
                currentPolylines.push(polyline);
                
                // Marcar puntos de la ruta
                route.steps.forEach(step => {
                    const [lng, lat] = step.location;
                    const marker = L.marker([lat, lng]).addTo(map);
                    currentMarkers.push(marker);
                    
                    let popup = `<b>Vehículo ${vehicleId}</b><br>`;
                    popup += `<small>${step.type.toUpperCase()}</small>`;
                    if (step.job) popup += `<br>Pedido: ${step.job}`;
                    
                    marker.bindPopup(popup);
                });
                
                map.fitBounds(polyline.getBounds());
            }
        }

        function showAllRoutes() {
            clearMap();
            
            // Resetear botones
            document.querySelectorAll('.vehicle-selector button').forEach(btn => {
                btn.classList.remove('active-route');
            });
            
            // Mostrar todas las rutas
            const bounds = [];
            
            routesData.forEach(route => {
                if (route.geometry) {
                    const decoded = polyline.decode(route.geometry);
                    const latlngs = decoded.map(p => L.latLng(p[0], p[1]));
                    
                    const polyline = L.polyline(latlngs, {
                        color: colors[route.vehicle - 1],
                        weight: 5
                    }).addTo(map);
                    
                    currentPolylines.push(polyline);
                    bounds.push(...latlngs);
                    
                    // Marcar punto de inicio
                    const vehicle = vehiclesData.find(v => v.id == route.vehicle);
                    if (vehicle) {
                        const marker = L.marker([vehicle.start[1], vehicle.start[0]])
                            .addTo(map)
                            .bindPopup(`<b>Base Vehículo ${route.vehicle}</b>`);
                        currentMarkers.push(marker);
                    }
                }
            });
            
            if (bounds.length > 0) {
                map.fitBounds(bounds);
            }
        }

        function clearMap() {
            currentPolylines.forEach(poly => map.removeLayer(poly));
            currentMarkers.forEach(marker => map.removeLayer(marker));
            currentPolylines = [];
            currentMarkers = [];
        }

        // Mostrar primer vehículo por defecto
        showRoute(1);
    </script>
</body>
</html>