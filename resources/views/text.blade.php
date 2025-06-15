<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador de Rutas</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>
    
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 300;
        }
        
        .route-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .info-card h3 {
            margin: 0 0 10px 0;
            color: #6c757d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-card .value {
            font-size: 1.5em;
            font-weight: bold;
            color: #495057;
        }
        
        .distance { color: #28a745; }
        .duration { color: #007bff; }
        .vehicle { color: #ffc107; }
        .steps { color: #6f42c1; }
        
        #map {
            height: 600px;
            width: 100%;
        }
        
        .form-section {
            padding: 20px;
            background: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗺️ Visualizador de Rutas con Leaflet</h1>
        </div>
        
        <div class="route-info" id="routeInfo" style="display: none;">
            <div class="info-card">
                <h3>Distancia</h3>
                <div class="value distance" id="distanceValue">-</div>
            </div>
            <div class="info-card">
                <h3>Duración</h3>
                <div class="value duration" id="durationValue">-</div>
            </div>
            <div class="info-card">
                <h3>Vehículo</h3>
                <div class="value vehicle" id="vehicleValue">-</div>
            </div>
            <div class="info-card">
                <h3>Pasos</h3>
                <div class="value steps" id="stepsValue">-</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="form-group">
                <label for="routeData">Datos de la Ruta (JSON):</label>
                <textarea id="routeData" placeholder='Pega aquí el JSON de la ruta, por ejemplo:
{
    "vehicle": 6,
    "geometryString": "kfbuAhxcsOHEPGpBy@xA...",
    "geometryLength": 1208,
    "geometryType": "string",
    "stepsCount": 4,
    "distance": 12678,
    "duration": 1103
}'></textarea>
                <button class="btn" onclick="loadRoute()">📍 Cargar Ruta en Mapa</button>
                <div id="error" class="error" style="display: none;"></div>
            </div>
        </div>
        
        <div id="map"></div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    
    <!-- Polyline decoder -->
    <script>
        // Decodificador de polylines (algoritmo de Google)
        function decodePolyline(str, precision = 5) {
            let index = 0,
                lat = 0,
                lng = 0,
                coordinates = [],
                shift = 0,
                result = 0,
                byte = null,
                latitude_change,
                longitude_change,
                factor = Math.pow(10, precision || 5);

            while (index < str.length) {
                // Decode latitude
                byte = null;
                shift = 0;
                result = 0;

                do {
                    byte = str.charCodeAt(index++) - 63;
                    result |= (byte & 0x1f) << shift;
                    shift += 5;
                } while (byte >= 0x20);

                latitude_change = ((result & 1) ? ~(result >> 1) : (result >> 1));

                // Decode longitude
                shift = 0;
                result = 0;

                do {
                    byte = str.charCodeAt(index++) - 63;
                    result |= (byte & 0x1f) << shift;
                    shift += 5;
                } while (byte >= 0x20);

                longitude_change = ((result & 1) ? ~(result >> 1) : (result >> 1));

                lat += latitude_change;
                lng += longitude_change;

                coordinates.push([lat / factor, lng / factor]);
            }

            return coordinates;
        }

        // Función para formatear duración
        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return `${hours}h ${minutes}m ${secs}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${secs}s`;
            } else {
                return `${secs}s`;
            }
        }

        // Función para formatear distancia
        function formatDistance(meters) {
            if (meters >= 1000) {
                return `${(meters / 1000).toFixed(2)} km`;
            } else {
                return `${meters} m`;
            }
        }

        // Función para obtener nombre del vehículo
        function getVehicleName(vehicleId) {
            const vehicles = {
                1: '🚗 Auto',
                2: '🚴 Bicicleta',
                3: '🚶 Peatón',
                4: '🏍️ Motocicleta',
                5: '🚛 Camión',
                6: '🚌 Autobús',
                7: '🚕 Taxi'
            };
            return vehicles[vehicleId] || `🚗 Vehículo ${vehicleId}`;
        }

        // Variable global para el mapa
        let map = null;
        let routeLayer = null;

        // Inicializar el mapa
        function initMap() {
            // Coordenadas de Tegucigalpa como centro por defecto
            map = L.map('map').setView([14.0723, -87.1921], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

        // Función para cargar la ruta
        function loadRoute() {
            const routeDataText = document.getElementById('routeData').value.trim();
            const errorDiv = document.getElementById('error');
            
            // Limpiar errores previos
            errorDiv.style.display = 'none';
            
            if (!routeDataText) {
                showError('Por favor, ingresa los datos de la ruta.');
                return;
            }

            try {
                const routeData = JSON.parse(routeDataText);
                
                // Validar que tenga los campos necesarios
                if (!routeData.geometryString) {
                    showError('Los datos de la ruta deben incluir "geometryString".');
                    return;
                }

                // Decodificar la geometría
                const coordinates = decodePolyline(routeData.geometryString);
                
                if (coordinates.length === 0) {
                    showError('No se pudieron decodificar las coordenadas de la ruta.');
                    return;
                }

                // Limpiar capa anterior si existe
                if (routeLayer) {
                    map.removeLayer(routeLayer);
                }

                // Crear la línea de la ruta
                routeLayer = L.polyline(coordinates, {
                    color: '#e74c3c',
                    weight: 5,
                    opacity: 0.8,
                    smoothFactor: 1
                }).addTo(map);

                // Agregar marcadores de inicio y fin
                const startPoint = coordinates[0];
                const endPoint = coordinates[coordinates.length - 1];

                L.marker(startPoint, {
                    icon: L.divIcon({
                        html: '🚀',
                        iconSize: [30, 30],
                        className: 'emoji-marker'
                    })
                }).addTo(map).bindPopup('Punto de Inicio');

                L.marker(endPoint, {
                    icon: L.divIcon({
                        html: '🏁',
                        iconSize: [30, 30],
                        className: 'emoji-marker'
                    })
                }).addTo(map).bindPopup('Punto Final');

                // Ajustar la vista del mapa a la ruta
                map.fitBounds(routeLayer.getBounds(), { padding: [20, 20] });

                // Actualizar la información de la ruta
                updateRouteInfo(routeData);

                console.log('Ruta cargada exitosamente:', routeData);

            } catch (error) {
                showError('Error al procesar los datos: ' + error.message);
                console.error('Error:', error);
            }
        }

        // Función para mostrar errores
        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        // Función para actualizar la información de la ruta
        function updateRouteInfo(routeData) {
            document.getElementById('distanceValue').textContent = formatDistance(routeData.distance || 0);
            document.getElementById('durationValue').textContent = formatDuration(routeData.duration || 0);
            document.getElementById('vehicleValue').textContent = getVehicleName(routeData.vehicle || 1);
            document.getElementById('stepsValue').textContent = routeData.stepsCount || 0;
            
            document.getElementById('routeInfo').style.display = 'grid';
        }

        // Inicializar cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            // Cargar la ruta de ejemplo automáticamente
            const exampleRoute = {
                "vehicle": 6,
                "geometryString": "kfbuAhxcsOHEPGpBy@xA\\nB`@^HbChBTJd@L|Bh@?l@_@lCCRE\\i@lEa@tAMz@Gd@OfADBBBH@F?BADYJu@pAEJAxBIZ?XAnBCnCEdBE~BEPAT?ZAbACbBCvBC`AA|BEfACLBHHDHFFJDH@J?HCFCDGDG@IJEHCP?X@NLFJ@J@PBfBAb@\\EhDe@TEFCJGNQNUl@w@b@o@^c@~@o@^SHAn@Qt@Cn@?lAD^@zCFd@@^Dr@L\\Dh@RrAd@RHJH`A`@FFBR[x@KXIRQb@ENITERCJ?L?J?LBJ\\bB\\zA|@vDXdAPV\\^xEdF`BjBb@j@JXHXDZ@^?PAPKZIZYd@g@b@O@KAQLIHGJQ`@E\\Kt@EVEVEXS|AAFALALAHCZ?V?V@V@XPvADTPj@DTT`BBNFJHDJDlATNBJBFBNHLHJJ`@b@VVTN\\Ln@N`@Ld@N\\Nr@`@PHLDZF^D`@?l@Ej@IdBYJCzAEfFu@dAI~@GrA?|BM|@Cl@KhDQJ?dAEL?nBGXAtFE~@Ax@Af@?p@Kd@In@Qz@a@p@]^Yn@g@DENSPYN[BGZ}@DQFm@V{APwADu@HsANsD@Q@Q?I@QJwBBc@Bg@VoFDaA@WDs@NuCHqAFwA@O@MD{@VkF\\BZB[C]CBeAHaC?cB?a@Ks@Ic@U}@IWK_@EKMe@QW]e@CE_AcAg@k@WWKWM]G[Ka@AKAGCUCQA]Ac@@W@]K?OLODMBS?Y@Q?KCGAEGWi@I}E?mCD]H[FSFMLKNGPAT?JBLJLJBFBL@LAPGNKNQLSJm@RO@yAF_@@aCNwCJsAFM@q@Dm@HO@C@WBeDRgBHcETu@AW@eAH]@m@DsCNG?{@HM?C@{@NQDm@TWHqAp@g@^SNc@b@g@f@aBrB}ClEm@z@s@dAEFy@jAc@l@OP]j@i@|@U`@c@p@ELUj@Uf@Qb@a@dAEH_@~@S`@S`@MRONYTYR[JkA\\g@JGEIAG@I@EDILIHOFC@G@iARs@PgAT[FSF_A\\iAT_@JmA\\YH_Cr@iCx@MW[kBk@uCu@{DEe@G}AAMAI?CCEGOe@y@Q]CCEAC?G?i@HK@M?a@EWCQAc@CYAQ?KEIECIGIIEICM?KBGBEDEFAFKJKBgAB}BDaA@wBBcBBcABKCKGCIAw@Ai@ASAk@CgBA_AA[IiFS?_AKiAOsAQk@IcC[sAS}AQQAC@CHCR_@fBER}Bi@e@MUKcCiB_@IoBa@yA]qBx@QFID",
                "geometryLength": 1208,
                "geometryType": "string",
                "first100chars": "kfbuAhxcsOHEPGpBy@xA\\nB`@^HbChBTJd@L|Bh@?l@_@lCCRE\\i@lEa@tAMz@Gd@OfADBBBH@F?BADYJu@pAEJAxBIZ?XAnBCnC",
                "last100chars": "BcABKCKGCIAw@Ai@ASAk@CgBA_AA[IiFS?_AKiAOsAQk@IcC[sAS}AQQAC@CHCR_@fBER}Bi@e@MUKcCiB_@IoBa@yA]qBx@QFID",
                "stepsCount": 4,
                "distance": 12678,
                "duration": 1103
            };
            
            document.getElementById('routeData').value = JSON.stringify(exampleRoute, null, 2);
            loadRoute();
        });
    </script>

    <style>
        .emoji-marker {
            background: none !important;
            border: none !important;
            text-align: center;
            font-size: 20px;
        }
    </style>
</body>
</html>