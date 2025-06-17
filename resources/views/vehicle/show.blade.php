<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VROOM - Sistema de Rutas de Entrega</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS de Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-truck"></i> VROOM - Optimización de Rutas
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class="fas fa-user"></i> {{ Auth::user()->name ?? 'Usuario' }}
                </span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <!-- Panel de Control -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-cogs"></i> Control de Optimización de Rutas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <button id="optimizeRoutes" class="btn btn-primary btn-lg w-100 mb-2">
                                    <i class="fas fa-magic"></i> Optimizar Rutas del Día
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button id="loadSummary" class="btn btn-info btn-lg w-100 mb-2">
                                    <i class="fas fa-chart-bar"></i> Ver Resumen del Día
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button id="clearAllRoutes" class="btn btn-warning btn-lg w-100 mb-2">
                                    <i class="fas fa-broom"></i> Limpiar Todas las Rutas
                                </button>
                            </div>
                        </div>
                        
                        <!-- Loading -->
                        <div id="optimizationLoading" class="text-center mt-3" style="display:none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Optimizando rutas...</span>
                            </div>
                            <p class="mt-2">Calculando rutas óptimas con VROOM...</p>
                        </div>

                        <!-- Resumen -->
                        <div id="summaryPanel" class="mt-3" style="display:none;">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-primary">
                                                <i class="fas fa-shopping-cart"></i>
                                                <span id="totalPedidos">0</span>
                                            </h5>
                                            <p class="card-text">Pedidos Despachados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-success">
                                                <i class="fas fa-motorcycle"></i>
                                                <span id="totalMotoristas">0</span>
                                            </h5>
                                            <p class="card-text">Motoristas Activos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-info">
                                                <i class="fas fa-road"></i>
                                                <span id="totalDistancia">0</span> km
                                            </h5>
                                            <p class="card-text">Distancia Total</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-warning">
                                                <i class="fas fa-clock"></i>
                                                <span id="totalTiempo">0</span> min
                                            </h5>
                                            <p class="card-text">Tiempo Total</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Panel de Motoristas -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Motoristas y Rutas</h5>
                    </div>
                    <div class="card-body" id="motoristasPanel">
                        <p class="text-muted text-center">
                            <i class="fas fa-info-circle"></i><br>
                            Haz clic en "Optimizar Rutas" para ver las asignaciones
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Mapa -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-map"></i> Mapa de Rutas Optimizadas</h5>
                        <div id="mapControls" style="display:none;">
                            <button class="btn btn-sm btn-outline-primary" id="showAllRoutes">
                                <i class="fas fa-eye"></i> Mostrar Todas
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="hideAllRoutes">
                                <i class="fas fa-eye-slash"></i> Ocultar Todas
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="map" style="height: 600px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript de Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <script>
    let map;
    let routeLayers = {};
    let allRoutes = [];
    let restaurantMarker;

    // Colores para diferenciar motoristas
    const colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
        '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
    ];

    function initMap() {
        // Crear mapa centrado en Tegucigalpa (restaurante)
        map = L.map('map').setView([14.0723, -87.1921], 13);

        // Agregar capa de mapa
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        // Marcador del restaurante
        restaurantMarker = L.marker([14.0723, -87.1921], {
            icon: L.divIcon({
                className: 'restaurant-marker',
                html: '<i class="fas fa-utensils" style="color: #dc3545; font-size: 20px;"></i>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(map);
        
        restaurantMarker.bindPopup('<strong>🏪 Restaurante</strong><br>Punto de partida y retorno');

        // Event listeners
        document.getElementById('optimizeRoutes').addEventListener('click', optimizeRoutes);
        document.getElementById('loadSummary').addEventListener('click', loadSummary);
        document.getElementById('clearAllRoutes').addEventListener('click', clearAllRoutes);
        document.getElementById('showAllRoutes').addEventListener('click', showAllRoutes);
        document.getElementById('hideAllRoutes').addEventListener('click', hideAllRoutes);

        // Cargar resumen al inicio
        loadSummary();
    }

    async function optimizeRoutes() {
        document.getElementById('optimizationLoading').style.display = 'block';
        document.getElementById('summaryPanel').style.display = 'none';
        
        try {
            const response = await fetch('/vroom/optimize-delivery-routes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();
            document.getElementById('optimizationLoading').style.display = 'none';

            if (data.success) {
                allRoutes = data.data;
                displayRoutes(allRoutes);
                displayMotoristas(allRoutes);
                updateSummary(data.summary);
                
                // Mostrar controles del mapa
                document.getElementById('mapControls').style.display = 'block';
                
                // Ajustar vista del mapa
                if (allRoutes.length > 0) {
                    fitMapToRoutes();
                }

                // Mostrar mensaje de éxito
                showAlert('success', `✅ Rutas optimizadas correctamente! ${data.summary.rutas_generadas} rutas generadas para ${data.summary.total_motoristas} motoristas.`);
            } else {
                showAlert('danger', '❌ ' + data.message);
            }
        } catch (error) {
            document.getElementById('optimizationLoading').style.display = 'none';
            console.error('Error:', error);
            showAlert('danger', '❌ Error al optimizar rutas: ' + error.message);
        }
    }

    function displayRoutes(routes) {
        // Limpiar rutas anteriores
        clearMapRoutes();

        routes.forEach((route, index) => {
            const color = colors[index % colors.length];
            const routeGroup = L.layerGroup();

            // Crear marcadores para cada pedido
            route.pedidos_asignados.forEach((pedido, pedidoIndex) => {
                const marker = L.marker([pedido.latitud, pedido.longitud], {
                    icon: L.divIcon({
                        className: 'delivery-marker',
                        html: `<div style="background-color: ${color}; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${pedido.orden_entrega}</div>`,
                        iconSize: [25, 25],
                        iconAnchor: [12, 12]
                    })
                });

                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <strong>📦 Pedido #${pedido.pedido_id}</strong><br>
                        <strong>👤 Cliente:</strong> ${pedido.cliente_nombre}<br>
                        <strong>📞 Teléfono:</strong> ${pedido.cliente_telefono}<br>
                        <strong>💰 Total:</strong> L. ${parseFloat(pedido.total).toFixed(2)}<br>
                        <strong>🚚 Motorista:</strong> ${route.motorista_nombre}<br>
                        <strong>📍 Orden:</strong> ${pedido.orden_entrega}° entrega
                    </div>
                `);

                routeGroup.addLayer(marker);
            });

            // Crear línea de ruta si hay geometría
            if (route.geometria) {
                try {
                    // Decodificar geometría de VROOM (polyline)
                    const decodedPath = L.Polyline.fromEncoded ? 
                        L.Polyline.fromEncoded(route.geometria) : 
                        decodePolyline(route.geometria);
                    
                    const routeLine = L.polyline(decodedPath, {
                        color: color,
                        weight: 4,
                        opacity: 0.8,
                        dashArray: '10, 5'
                    });

                    routeLine.bindPopup(`
                        <strong>🛣️ Ruta de ${route.motorista_nombre}</strong><br>
                        <strong>📏 Distancia:</strong> ${route.distancia_total_km} km<br>
                        <strong>⏱️ Tiempo:</strong> ${route.duracion_formateada}<br>
                        <strong>📦 Pedidos:</strong> ${route.total_pedidos}
                    `);

                    routeGroup.addLayer(routeLine);
                } catch (e) {
                    console.warn('No se pudo decodificar la geometría de la ruta:', e);
                }
            }

            // Guardar grupo de la ruta
            routeLayers[route.motorista_id] = routeGroup;
            routeGroup.addTo(map);
        });
    }

    function displayMotoristas(routes) {
        const panel = document.getElementById('motoristasPanel');
        
        if (routes.length === 0) {
            panel.innerHTML = '<p class="text-muted text-center">No hay rutas disponibles</p>';
            return;
        }

        let html = '<div class="accordion" id="motoristasAccordion">';
        
        routes.forEach((route, index) => {
            const color = colors[index % colors.length];
            const collapseId = `collapse${route.motorista_id}`;
            
            html += `
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                            <div class="d-flex align-items-center w-100">
                                <div class="me-3" style="width: 20px; height: 20px; background-color: ${color}; border-radius: 50%;"></div>
                                <div class="flex-grow-1">
                                    <strong>${route.motorista_nombre}</strong><br>
                                    <small class="text-muted">${route.total_pedidos} pedidos - ${route.distancia_total_km} km - ${route.duracion_formateada}</small>
                                </div>
                                <div class="me-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleRoute(${route.motorista_id})">
                                        <i class="fas fa-eye" id="eye-${route.motorista_id}"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="focusRoute(${route.motorista_id})">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="${collapseId}" class="accordion-collapse collapse" data-bs-parent="#motoristasAccordion">
                        <div class="accordion-body">
                            <h6><i class="fas fa-info-circle"></i> Información de la Ruta:</h6>
                            <ul class="list-unstyled">
                                <li><strong>📧 Email:</strong> ${route.motorista_email}</li>
                                <li><strong>📏 Distancia:</strong> ${route.distancia_total_km} km</li>
                                <li><strong>⏱️ Tiempo estimado:</strong> ${route.duracion_formateada}</li>
                                <li><strong>📦 Total pedidos:</strong> ${route.total_pedidos}</li>
                            </ul>
                            
                            <h6><i class="fas fa-list-ol"></i> Pedidos Asignados:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Cliente</th>
                                            <th>Teléfono</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
            
            route.pedidos_asignados.forEach(pedido => {
                html += `
                                        <tr>
                                            <td><span class="badge" style="background-color: ${color};">${pedido.orden_entrega}</span></td>
                                            <td>${pedido.cliente_nombre}</td>
                                            <td>${pedido.cliente_telefono}</td>
                                            <td>L. ${parseFloat(pedido.total).toFixed(2)}</td>
                                        </tr>`;
            });
            
            html += `
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-primary btn-sm" onclick="exportRoute(${route.motorista_id})">
                                    <i class="fas fa-download"></i> Exportar Ruta
                                </button>
                                <button class="btn btn-success btn-sm" onclick="sendRouteToDriver(${route.motorista_id})">
                                    <i class="fas fa-paper-plane"></i> Enviar al Motorista
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
        });
        
        html += '</div>';
        panel.innerHTML = html;
    }

    function updateSummary(summary) {
        document.getElementById('totalPedidos').textContent = summary.total_pedidos;
        document.getElementById('totalMotoristas').textContent = summary.total_motoristas;
        document.getElementById('summaryPanel').style.display = 'block';
    }

    async function loadSummary() {
        try {
            const response = await fetch('/vroom/daily-summary');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('totalPedidos').textContent = data.summary.pedidos_despachados;
                document.getElementById('totalMotoristas').textContent = data.summary.motoristas_con_rutas;
                document.getElementById('totalDistancia').textContent = data.summary.total_distancia_km;
                document.getElementById('totalTiempo').textContent = data.summary.total_duracion_minutos;
                document.getElementById('summaryPanel').style.display = 'block';
            }
        } catch (error) {
            console.error('Error al cargar resumen:', error);
        }
    }

    function clearAllRoutes() {
        if (confirm('¿Estás seguro de que deseas limpiar todas las rutas del mapa?')) {
            clearMapRoutes();
            document.getElementById('motoristasPanel').innerHTML = '<p class="text-muted text-center"><i class="fas fa-info-circle"></i><br>Haz clic en "Optimizar Rutas" para ver las asignaciones</p>';
            document.getElementById('mapControls').style.display = 'none';
            allRoutes = [];
            map.setView([14.0723, -87.1921], 13);
        }
    }

    function clearMapRoutes() {
        Object.values(routeLayers).forEach(layer => {
            map.removeLayer(layer);
        });
        routeLayers = {};
    }

    function toggleRoute(motoristaId) {
        const layer = routeLayers[motoristaId];
        const eyeIcon = document.getElementById(`eye-${motoristaId}`);
        
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
            eyeIcon.className = 'fas fa-eye-slash';
        } else {
            map.addLayer(layer);
            eyeIcon.className = 'fas fa-eye';
        }
    }

    function focusRoute(motoristaId) {
        const route = allRoutes.find(r => r.motorista_id == motoristaId);
        if (!route || route.pedidos_asignados.length === 0) return;

        // Crear bounds para la ruta específica
        const bounds = L.latLngBounds();
        bounds.extend([14.0723, -87.1921]); // Restaurante
        
        route.pedidos_asignados.forEach(pedido => {
            bounds.extend([pedido.latitud, pedido.longitud]);
        });

        map.fitBounds(bounds, { padding: [20, 20] });
        
        // Asegurar que la ruta esté visible
        const layer = routeLayers[motoristaId];
        if (!map.hasLayer(layer)) {
            map.addLayer(layer);
            document.getElementById(`eye-${motoristaId}`).className = 'fas fa-eye';
        }
    }

    function showAllRoutes() {
        Object.values(routeLayers).forEach(layer => {
            if (!map.hasLayer(layer)) {
                map.addLayer(layer);
            }
        });
        
        // Actualizar iconos
        allRoutes.forEach(route => {
            const eyeIcon = document.getElementById(`eye-${route.motorista_id}`);
            if (eyeIcon) eyeIcon.className = 'fas fa-eye';
        });
        
        fitMapToRoutes();
    }

    function hideAllRoutes() {
        Object.values(routeLayers).forEach(layer => {
            map.removeLayer(layer);
        });
        
        // Actualizar iconos
        allRoutes.forEach(route => {
            const eyeIcon = document.getElementById(`eye-${route.motorista_id}`);
            if (eyeIcon) eyeIcon.className = 'fas fa-eye-slash';
        });
    }

    function fitMapToRoutes() {
        if (allRoutes.length === 0) return;

        const bounds = L.latLngBounds();
        bounds.extend([14.0723, -87.1921]); // Restaurante

        allRoutes.forEach(route => {
            route.pedidos_asignados.forEach(pedido => {
                bounds.extend([pedido.latitud, pedido.longitud]);
            });
        });

        map.fitBounds(bounds, { padding: [30, 30] });
    }

    function exportRoute(motoristaId) {
        const route = allRoutes.find(r => r.motorista_id == motoristaId);
        if (!route) return;

        const exportData = {
            motorista: {
                id: route.motorista_id,
                nombre: route.motorista_nombre,
                email: route.motorista_email
            },
            resumen: {
                total_pedidos: route.total_pedidos,
                distancia_km: route.distancia_total_km,
                duracion: route.duracion_formateada
            },
            pedidos: route.pedidos_asignados,
            fecha_generacion: new Date().toISOString()
        };

        const blob = new Blob([JSON.stringify(exportData, null, 2)], {
            type: 'application/json'
        });
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ruta_${route.motorista_nombre.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        showAlert('success', `✅ Ruta de ${route.motorista_nombre} exportada correctamente`);
    }

    function sendRouteToDriver(motoristaId) {
        // Aquí puedes implementar el envío por email, WhatsApp, etc.
        const route = allRoutes.find(r => r.motorista_id == motoristaId);
        if (!route) return;

        // Ejemplo de implementación
        showAlert('info', `📱 Funcionalidad de envío en desarrollo. Ruta de ${route.motorista_nombre} lista para enviar.`);
        
        // Podrías hacer algo como:
        // fetch('/send-route-notification', {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        //     },
        //     body: JSON.stringify({ motorista_id: motoristaId })
        // });
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Función auxiliar para decodificar polyline (si es necesario)
    function decodePolyline(encoded) {
        // Implementación básica de decodificación de polyline
        // En un entorno real, podrías usar una librería como polyline
        const points = [];
        let index = 0;
        const len = encoded.length;
        let lat = 0;
        let lng = 0;

        while (index < len) {
            let b, shift = 0, result = 0;
            do {
                b = encoded.charCodeAt(index++) - 63;
                result |= (b & 0x1f) << shift;
                shift += 5;
            } while (b >= 0x20);
            const dlat = ((result & 1) ? ~(result >> 1) : (result >> 1));
            lat += dlat;

            shift = 0;
            result = 0;
            do {
                b = encoded.charCodeAt(index++) - 63;
                result |= (b & 0x1f) << shift;
                shift += 5;
            } while (b >= 0x20);
            const dlng = ((result & 1) ? ~(result >> 1) : (result >> 1));
            lng += dlng;

            points.push([lat / 1e5, lng / 1e5]);
        }

        return points;
    }

    // Inicializar cuando la página cargue
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });
    </script>

    <style>
    /* Estilos personalizados */
    #map {
        border-radius: 8px;
    }

    .restaurant-marker, .delivery-marker {
        background: transparent;
        border: none;
    }

    .accordion-button:not(.collapsed) {
        background-color: #e7f3ff;
        border-color: #b8daff;
    }

    .btn {
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .spinner-border {
        width: 2rem;
        height: 2rem;
    }

    .leaflet-routing-container {
        display: none !important;
    }

    .table-responsive {
        max-height: 300px;
        overflow-y: auto;
    }

    .alert {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: none;
    }

    .card-header {
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }

    .navbar-brand {
        font-weight: bold;
    }
    </style>
</body>
</html>