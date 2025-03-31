<!DOCTYPE html>
<html>
<head>
    <title>Ruta Optimizada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 100vh; }
    </style>
</head>
<body>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>

    <script>
    const map = L.map('map').setView([14.0667, -87.1875], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    const routes = @json($result['routes']);
    const colors = ['blue', 'green', 'red', 'purple', 'orange']; // para cada vehículo

    routes.forEach((route, index) => {
        const encoded = route.geometry;
        const decodedCoords = polyline.decode(encoded);
        const latlngs = decodedCoords.map(p => L.latLng(p[0], p[1]));

        const polylineLine = L.polyline(latlngs, {
            color: colors[index % colors.length],
            weight: 4,
            opacity: 0.8
        }).addTo(map);

        map.fitBounds(polylineLine.getBounds());

        // Marcar puntos clave
        route.steps.forEach((step) => {
            const [lon, lat] = step.location;
            const label = `${step.type.toUpperCase()} ${step.job ? '#'+step.job : ''}`;
            L.marker([lat, lon]).addTo(map)
                .bindPopup(`Repartidor ${route.vehicle} - ${label}`);
        });
    });
</script>

</body>
</html>
