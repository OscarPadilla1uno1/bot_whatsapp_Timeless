<!DOCTYPE html>
<html>

<head>
    <title>Rutas de Vehículos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        #map {
            height: 100vh;
        }

        .control-panel {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
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
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        
        /* Estilo para el botón de seguimiento activo */
        .following-active {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <div class="control-panel">
        <h3>Vehículos</h3>
        <div id="vehicle-buttons">
            @foreach($routes as $route)
                <button onclick="showRoute({{ $route['vehicle'] }})" id="vehicle-{{ $route['vehicle'] }}">
                    Vehículo {{ $route['vehicle'] }}
                </button>
            @endforeach
            <button onclick="showAllRoutes()">Mostrar Todos</button>
            <button id="follow-button" onclick="toggleFollowRoute()">Seguir Ruta</button>
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

        // Inicializar mapa
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const colors = ['#FF0000', '#0000FF', '#00FF00', '#FF00FF', '#FFFF00'];
        let currentPolylines = [];
        let currentMarkers = [];
        let isFollowingRoute = false;
        let followInterval = null;
        let currentRouteLine = null;
        let currentRouteCoords = [];
        let currentMarker = null;
        let directionLine = null;

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
            
            if (directionLine) {
                map.removeLayer(directionLine);
                directionLine = null;
            }
        }

        // Función para alternar el seguimiento de ruta
        function toggleFollowRoute() {
            const followButton = document.getElementById('follow-button');
            
            if (isFollowingRoute) {
                // Detener el seguimiento
                stopFollowing();
                followButton.textContent = 'Seguir Ruta';
                followButton.classList.remove('following-active');
            } else {
                // Iniciar el seguimiento
                startFollowing();
                followButton.textContent = 'Detener Seguimiento';
                followButton.classList.add('following-active');
            }
        }

        // Función para iniciar el seguimiento
        function startFollowing() {
            isFollowingRoute = true;
            updateRouteAndPosition();
            followInterval = setInterval(updateRouteAndPosition, 5000); // Actualizar cada 5 segundos
        }

        // Función para detener el seguimiento
        function stopFollowing() {
            isFollowingRoute = false;
            if (followInterval) {
                clearInterval(followInterval);
                followInterval = null;
            }
            
            // Limpiar marcadores y líneas de dirección
            if (currentMarker) {
                map.removeLayer(currentMarker);
                currentMarker = null;
            }
            if (directionLine) {
                map.removeLayer(directionLine);
                directionLine = null;
            }
        }

        // Función principal para actualizar la posición y ruta
        // Función principal para actualizar la posición y ruta
async function updateRouteAndPosition() {
    if (!navigator.geolocation) {
        showError("Geolocalización no soportada.");
        stopFollowing();
        return;
    }

    navigator.geolocation.getCurrentPosition(async position => {
        const currentLat = position.coords.latitude;
        const currentLng = position.coords.longitude;
        async function tryRequest(retries = 3) {
        try {
            // 1. Obtener la ruta desde el servidor con Axios
            const response = await axios.post('http://127.0.0.1:8000/seguir', { 
                current_location: [currentLng, currentLat] 
            },{ timeout: 30000 } ,{
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            });

            const data = response.data;

            if (!data.routes || data.routes.length === 0) {
                showError("No se pudo calcular la ruta.");
                stopFollowing();
                return;
            }

            // 2. Procesar la ruta recibida
            const coords = data.routes[0].geometry;
            const decoded = polyline.decode(coords);
            const latlngs = decoded.map(p => L.latLng(p[0], p[1]));

            // Si es la primera vez o la ruta cambió, actualizar el trazado
            if (!currentRouteLine || JSON.stringify(latlngs) !== JSON.stringify(currentRouteCoords)) {
                currentRouteCoords = latlngs;
                
                if (currentRouteLine) {
                    map.removeLayer(currentRouteLine);
                }
                
                currentRouteLine = L.polyline(latlngs, { 
                    color: '#00aaff', 
                    weight: 5,
                    opacity: 0.7
                }).addTo(map);
                
                // Ajustar la vista para mostrar toda la ruta
                map.fitBounds(currentRouteLine.getBounds());
            }

            // 3. Encontrar el punto más cercano en la ruta
            const closestPoint = findClosestPoint(currentRouteCoords, currentLat, currentLng);
            
            // 4. Actualizar o crear el marcador de posición
            if (currentMarker) {
                currentMarker.setLatLng(closestPoint);
            } else {
                currentMarker = L.marker(closestPoint, {
                    icon: L.divIcon({
                        className: 'current-location-marker',
                        html: '<div style="background-color: #4285F4; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                        iconSize: [26, 26],
                        iconAnchor: [13, 13]
                    })
                }).addTo(map).bindPopup("Tu ubicación").openPopup();
            }

            // 5. Dibujar línea desde tu posición actual al punto más cercano en la ruta
            if (directionLine) {
                map.removeLayer(directionLine);
            }
            
            directionLine = L.polyline([[currentLat, currentLng], [closestPoint.lat, closestPoint.lng]], {
                color: '#FF0000',
                weight: 2,
                dashArray: '5, 5'
            }).addTo(map);

            // 6. Ajustar la vista para mostrar tu posición y la ruta
            const bounds = new L.LatLngBounds();
            bounds.extend([currentLat, currentLng]);
            bounds.extend([closestPoint.lat, closestPoint.lng]);
            map.fitBounds(bounds, { padding: [50, 50] });

            // Ocultar mensajes de error si todo está bien
            document.getElementById('error-message').style.display = 'none';

        } catch (error) {
            console.error('Error al calcular la ruta:', error);
            
            // Manejo mejorado de errores con Axios
            let errorMessage = "Error al calcular la ruta";
            if (error.response) {
                // El servidor respondió con un código de estado fuera del rango 2xx
                errorMessage = error.response.data.message || 
                             error.response.data.error || 
                             `Error ${error.response.status}: ${error.response.statusText}`;
            } else if (error.request) {
                // La solicitud fue hecha pero no se recibió respuesta
                errorMessage = "No se recibió respuesta del servidor";
            }
            
            showError(errorMessage);
            stopFollowing();
        }
    }
    }, error => {
        showError("No se pudo obtener tu ubicación.");
        stopFollowing();
    }, {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
    });
} 

        // Función para encontrar el punto más cercano en la ruta
        function findClosestPoint(routeCoords, lat, lng) {
            let closest = null;
            let minDist = Infinity;

            routeCoords.forEach(coord => {
                const dist = getDistanceFromLatLonInKm(lat, lng, coord.lat, coord.lng);
                if (dist < minDist) {
                    minDist = dist;
                    closest = coord;
                }
            });

            return closest;
        }

        // Función para calcular distancia entre coordenadas
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