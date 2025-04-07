<!DOCTYPE html>
<html>
<head>
    <title>Ruta Vehículo #{{ $vehicleId }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 100vh; }
        .vehicle-nav {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <div class="vehicle-nav">
        @foreach(range(1, $totalVehicles) as $i)
            <a href="{{ route('vehicle.show', $i) }}" 
               style="{{ $i == $vehicleId ? 'background:#007bff;color:white' : '' }}">
                Vehículo {{ $i }}
            </a>
        @endforeach
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>

    <script>
        const map = L.map('map').setView([14.0821, -87.2065], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        // Datos de la ruta
        const route = @json($route);
        const color = ['red', 'blue'][{{ $vehicleId }} - 1];

        // Dibujar ruta
        if (route.geometry) {
            const decoded = polyline.decode(route.geometry);
            const latlngs = decoded.map(p => L.latLng(p[0], p[1]));
            L.polyline(latlngs, {color: color, weight: 5}).addTo(map);
            
            // Ajustar vista
            map.fitBounds(latlngs);
        }

        // Marcar puntos importantes
        route.steps.forEach(step => {
            const [lng, lat] = step.location;
            const marker = L.marker([lat, lng]).addTo(map);
            
            let popup = `<b>${step.type.toUpperCase()}</b>`;
            if (step.job) popup += `<br>Pedido ${step.job}`;
            
            marker.bindPopup(popup);
            if (step.type === 'start') marker.openPopup();
        });
    </script>
</body>
</html>