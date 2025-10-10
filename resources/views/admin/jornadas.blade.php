<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Distribución Automática de Pedidos') }}
        </h2>
    </x-slot>
    <script src="{{ asset('js/jornadas.js') }}"></script>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Header personalizado -->
                    <div
                        class="mb-6 bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-lg text-center">
                        <h1 class="text-2xl font-bold mb-2">Distribución Automática de Pedidos</h1>
                        <p>Distribuye automáticamente todos los pedidos despachados entre los repartidores disponibles
                        </p>
                    </div>


                    <style>
                        .drivers-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                            gap: 15px;
                            margin-top: 15px;
                        }

                        .driver-card {
                            background: white;
                            border: 2px solid #e9ecef;
                            border-radius: 8px;
                            padding: 15px;
                            transition: all 0.3s ease;
                        }

                        .driver-card.disabled {
                            background: #f8f9fa;
                            opacity: 0.7;
                        }

                        .driver-card.active-shift {
                            border-color: #28a745;
                            background: #f0fff4;
                        }

                        .driver-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            margin-bottom: 10px;
                        }

                        .driver-name {
                            font-weight: 600;
                            color: #333;
                        }

                        .driver-status {
                            font-size: 12px;
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-weight: 600;
                        }

                        .status-available {
                            background: #d4edda;
                            color: #155724;
                        }

                        .status-unavailable {
                            background: #f8d7da;
                            color: #721c24;
                        }

                        .toggle-switch {
                            position: relative;
                            display: inline-block;
                            width: 50px;
                            height: 24px;
                        }

                        .toggle-switch input {
                            opacity: 0;
                            width: 0;
                            height: 0;
                        }

                        .slider {
                            position: absolute;
                            cursor: pointer;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background-color: #ccc;
                            transition: .4s;
                            border-radius: 24px;
                        }

                        .slider:before {
                            position: absolute;
                            content: "";
                            height: 18px;
                            width: 18px;
                            left: 3px;
                            bottom: 3px;
                            background-color: white;
                            transition: .4s;
                            border-radius: 50%;
                        }

                        input:checked+.slider {
                            background-color: #28a745;
                        }

                        input:checked+.slider:before {
                            transform: translateX(26px);
                        }
                    </style>

                    <div class="drivers-management" style="margin-bottom: 20px;">
    <h3>Gestión de Conductores</h3>
    <div id="drivers-list" class="drivers-grid">
        <div>Cargando conductores...</div>
    </div>
</div>

                    <!-- Controles principales -->
                    <div class="bg-white border rounded-lg shadow-sm mb-6">
                        <div class="p-4 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">Control de Distribución</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap gap-4 items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <label for="date-filter" class="font-medium text-gray-700">Fecha:</label>
                                    <input type="date" id="date-filter" value="{{ date('Y-m-d') }}"
                                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button id="btn-distribuir"
                                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                                        🚚 Distribuir Automáticamente
                                    </button>
                                    <button id="btn-ver-distribucion"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                        👁️ Ver Distribución
                                    </button>
                                    <button id="btn-reiniciar"
                                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors">
                                        🔄 Reiniciar
                                    </button>
                                    <button id="btn-estado-sistema"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                                        ⚙️ Estado Sistema
                                    </button>
                                    <button id="btn-cargar-pedidos"
                                        class="px-4 py-2 bg-cyan-600 text-white rounded-md hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-colors">
                                        📦 Recargar Pedidos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área de contenido -->
                    <div class="bg-white border rounded-lg shadow-sm mb-6">
                        <div class="p-6">
                            <!-- Loading spinner -->
                            <div id="loading" class="hidden text-center py-12">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600">
                                </div>
                                <p class="mt-2 text-gray-600">Cargando...</p>
                            </div>

                            <!-- Contenido principal -->
                            <div id="content-area">
                                <div class="text-center py-12 text-gray-500">
                                    <div class="text-6xl mb-4">📦</div>
                                    <h3 class="text-xl font-semibold mb-2">Sistema de Distribución Automática</h3>
                                    <p>Selecciona una acción del menú superior para comenzar.</p>
                                    <p class="text-sm mt-2">El sistema distribuirá automáticamente todos los pedidos
                                        "despachados" entre los repartidores disponibles.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de pedidos -->
                    <div class="bg-white border rounded-lg shadow-sm" id="pedidos-section">
                        <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Resumen de Pedidos del Día</h3>
                        </div>
                        <div class="p-6" id="pedidos-del-dia-content" style="display: none;">
                            <!-- Métricas -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-blue-600" id="total-pedidos-dia">0</div>
                                    <div class="text-sm text-blue-600 font-medium">Total del Día</div>
                                </div>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-yellow-600" id="despachados-sin-jornada">0</div>
                                    <div class="text-sm text-yellow-600 font-medium">Sin Asignar</div>
                                </div>
                                <div class="bg-cyan-50 border border-cyan-200 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-cyan-600" id="despachados-en-jornada">0</div>
                                    <div class="text-sm text-cyan-600 font-medium">En Jornadas</div>
                                </div>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                    <div class="text-2xl font-bold text-green-600" id="pedidos-entregados">0</div>
                                    <div class="text-sm text-green-600 font-medium">Entregados</div>
                                </div>
                            </div>

                            <!-- Categorías de pedidos -->
                            <div id="pedidos-categorias"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notificaciones -->
    <div id="notifications" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
.jornada-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    background: white;
    transition: all 0.3s ease;
}

.jornada-card.estado-planificada { border-left: 4px solid #fbbf24; background: #fffbeb; }
.jornada-card.estado-asignada { border-left: 4px solid #3b82f6; background: #eff6ff; }
.jornada-card.estado-en_progreso { border-left: 4px solid #10b981; background: #f0fdf4; }
.jornada-card.estado-completada { border-left: 4px solid #6b7280; background: #f9fafb; }

.jornada-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e5e7eb;
}

.jornada-title { font-size: 18px; font-weight: 700; color: #1f2937; }

.jornada-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-planificada { background: #fef3c7; color: #92400e; }
.badge-asignada { background: #dbeafe; color: #1e40af; }
.badge-en_progreso { background: #d1fae5; color: #065f46; }
.badge-completada { background: #e5e7eb; color: #374151; }

.jornada-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 15px;
}

.jornada-info-item { background: #f9fafb; padding: 10px; border-radius: 8px; text-align: center; }
.jornada-info-label { font-size: 11px; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
.jornada-info-value { font-size: 16px; font-weight: 700; color: #1f2937; }

.pedidos-list { margin-top: 15px; border: 1px solid #e5e7eb; border-radius: 8px; }
.pedidos-list-header {
    background: #f3f4f6;
    padding: 10px 15px;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    color: #374151;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pedido-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.2s;
}
.pedido-item:hover { background: #f9fafb; }
.pedido-item:last-child { border-bottom: none; }

.pedido-info { flex: 1; }
.pedido-id { font-weight: 600; color: #1f2937; margin-bottom: 4px; }
.pedido-cliente { font-size: 14px; color: #6b7280; }

.pedido-estado-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.estado-despachado { background: #fef3c7; color: #92400e; }
.estado-en_ruta { background: #dbeafe; color: #1e40af; }
.estado-entregado { background: #d1fae5; color: #065f46; }
.estado-devuelto { background: #fee2e2; color: #991b1b; }
.no-pedidos { text-align: center; padding: 20px; color: #9ca3af; font-style: italic; }
`;
        document.head.appendChild(styleSheet);
        // Envolver todo en una función inmediatamente ejecutada para evitar conflictos
        (function () {
            'use strict';

            console.log('🔄 Iniciando sistema...');

            // Variables locales para evitar conflictos
            const distributionCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('Token CSRF:', distributionCsrfToken ? 'Encontrado' : 'NO encontrado');

            // Funciones de utilidad
            function showLoading() {
                const loading = document.getElementById('loading');
                const content = document.getElementById('content-area');
                if (loading && content) {
                    loading.classList.remove('hidden');
                    content.style.display = 'none';
                    console.log('Mostrando loading...');
                }
            }

            function hideLoading() {
                const loading = document.getElementById('loading');
                const content = document.getElementById('content-area');
                if (loading && content) {
                    loading.classList.add('hidden');
                    content.style.display = 'block';
                    console.log('Ocultando loading...');
                }
            }

            function showNotification(message, type = 'success') {
                const container = document.getElementById('notifications');
                if (!container) return;

                const notification = document.createElement('div');
                notification.className = `p-4 rounded-md shadow-lg transition-all duration-300 max-w-sm ${type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
                    type === 'error' ? 'bg-red-100 border border-red-400 text-red-700' :
                        type === 'warning' ? 'bg-yellow-100 border border-yellow-400 text-yellow-700' :
                            'bg-blue-100 border border-blue-400 text-blue-700'
                    }`;

                notification.innerHTML = `
                <div class="flex justify-between items-start">
                    <span class="flex-1">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>
            `;

                container.appendChild(notification);

                // Auto-remove después de 5 segundos
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);

                console.log(`Notificación ${type}:`, message);
            }

            // Funciones principales
            function distribuirAutomaticamente() {
                console.log('🚚 Función distribuirAutomaticamente ejecutada');

                if (!confirm(
                    '¿Estás seguro de que quieres distribuir todos los pedidos despachados entre los repartidores disponibles?'
                )) {
                    console.log('Usuario canceló la distribución');
                    return;
                }

                showLoading();
                const fecha = document.getElementById('date-filter').value;
                console.log('Fecha seleccionada:', fecha);

                fetch('/admin/distribuir-pedidos-automaticamente', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': distributionCsrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        fecha: fecha
                    })
                })
                    .then(response => {
                        console.log('Respuesta del servidor:', response.status, response.statusText);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos recibidos:', data);
                        hideLoading();

                        if (data.success) {
                            showNotification(data.message || 'Distribución completada exitosamente', 'success');
                            mostrarResultadoDistribucion(data);
                        } else {
                            showNotification(data.error || 'Error en la distribución', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error en la petición:', error);
                        hideLoading();
                        showNotification('Error de conexión: ' + error.message, 'error');
                    });
            }

            function verDistribucionActual() {
                console.log('👁️ Función verDistribucionActual ejecutada');

                showLoading();
                const fecha = document.getElementById('date-filter').value;

                fetch(`/admin/ver-distribucion-actual?fecha=${encodeURIComponent(fecha)}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': distributionCsrfToken,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Distribución actual:', data);
                        hideLoading();

                        if (data.success) {
                            mostrarDistribucionActual(data);
                            showNotification('Distribución actual cargada', 'success');
                        } else {
                            showNotification(data.error || 'Error obteniendo distribución', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideLoading();
                        showNotification('Error de conexión', 'error');
                    });
            }

            function reiniciarDistribucion() {
                console.log('🔄 Función reiniciarDistribucion ejecutada');

                if (!confirm(
                    '¿Estás seguro de que quieres reiniciar la distribución? Esto eliminará todas las jornadas actuales.'
                )) {
                    return;
                }

                showLoading();
                const fecha = document.getElementById('date-filter').value;

                fetch('/admin/reiniciar-distribucion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': distributionCsrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        fecha: fecha
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Reinicio:', data);
                        hideLoading();

                        if (data.success) {
                            showNotification(data.message || 'Distribución reiniciada correctamente', 'success');

                            // NUEVO: Recargar pedidos automáticamente
                            setTimeout(() => {
                                loadPedidosDelDia();
                            }, 500);

                            document.getElementById('content-area').innerHTML = `
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🔄</div>
                    <h3 class="text-xl font-semibold mb-2 text-green-600">Sistema Reiniciado</h3>
                    <p class="text-gray-600">Todos los pedidos han vuelto a estado "despachado" y las jornadas han sido eliminadas.</p>
                    <p class="text-gray-600">Puedes ejecutar una nueva distribución automática.</p>
                </div>
            `;
                        } else {
                            showNotification(data.error || 'Error al reiniciar', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideLoading();
                        showNotification('Error de conexión', 'error');
                    });
            }

            function checkSystemStatus() {
                console.log('⚙️ Función checkSystemStatus ejecutada');

                showLoading();

                fetch('/admin/debug-autoassign', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': distributionCsrfToken,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Estado del sistema:', data);
                        hideLoading();
                        mostrarEstadoSistema(data);
                        showNotification('Estado del sistema obtenido', 'success');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideLoading();
                        showNotification('Error verificando sistema', 'error');
                    });
            }

            function loadPedidosDelDia() {
                console.log('📦 Función loadPedidosDelDia ejecutada');

                const fecha = document.getElementById('date-filter').value;

                fetch(`/admin/pedidos-del-dia?fecha=${encodeURIComponent(fecha)}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': distributionCsrfToken,
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Pedidos del día:', data);

                        if (data.success) {
                            mostrarPedidosDelDia(data);
                            document.getElementById('pedidos-del-dia-content').style.display = 'block';
                            showNotification('Pedidos del día cargados', 'success');
                        } else {
                            showNotification(data.error || 'Error cargando pedidos', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error de conexión', 'error');
                    });
            }

            // Funciones de visualización
            function mostrarResultadoDistribucion(data) {
                const contentArea = document.getElementById('content-area');
                let html = `
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                    <h3 class="text-green-800 font-semibold">✅ Distribución Completada</h3>
                    <p class="text-green-700">${data.message}</p>
                </div>
            `;

                if (data.distribucion && data.distribucion.length > 0) {
                    html += '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
                    data.distribucion.forEach(dist => {
                        html += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                            <h4 class="font-semibold text-gray-800 mb-2">${dist.repartidor_name || 'Repartidor desconocido'}</h4>
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div class="text-center bg-blue-50 rounded p-2">
                                    <div class="font-bold text-blue-600">${dist.pedidos_asignados || 0}</div>
                                    <div class="text-xs text-blue-600">Pedidos</div>
                                </div>
                                <div class="text-center bg-gray-50 rounded p-2">
                                    <div class="font-bold text-gray-600">${dist.jornada_id || 'N/A'}</div>
                                    <div class="text-xs text-gray-600">Jornada ID</div>
                                </div>
                            </div>
                        </div>
                    `;
                    });
                    html += '</div>';
                }

                contentArea.innerHTML = html;
            }

            function mostrarDistribucionActual(data) {
                const contentArea = document.getElementById('content-area');
                let html = `
        <div class="mb-4 flex justify-between items-center">
            <h3 class="text-xl font-semibold">📊 Distribución Actual - ${data.fecha}</h3>
            <button onclick="verDistribucionActual()" class="px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm">
                🔄 Actualizar
            </button>
        </div>
    `;

                if (data.jornadas && data.jornadas.length > 0) {
                    data.jornadas.forEach(jornada => {
                        const estadoClass = `estado-${jornada.estado.replace(' ', '_')}`;
                        const badgeClass = `badge-${jornada.estado.replace(' ', '_')}`;

                        const totalPedidos = parseInt(jornada.pedidos_asignados) || 0;
                        const entregados = parseInt(jornada.pedidos_entregados) || 0;
                        const devueltos = parseInt(jornada.pedidos_devueltos) || 0;
                        const completados = entregados + devueltos;
                        const pendientes = totalPedidos - completados;
                        const progreso = totalPedidos > 0 ? (completados / totalPedidos * 100).toFixed(1) : 0;

                        html += `
                <div class="jornada-card ${estadoClass} mb-4">
                    <div class="jornada-header">
                        <div>
                            <div class="jornada-title">${jornada.jornada_nombre || 'Jornada #' + jornada.jornada_id}</div>
                            <div style="color: #6b7280; font-size: 14px; margin-top: 4px;">
                                👤 ${jornada.repartidor_nombre}
                            </div>
                        </div>
                        <span class="jornada-badge ${badgeClass}">${jornada.estado}</span>
                    </div>
                    
                    <div class="jornada-info-grid">
                        <div class="jornada-info-item">
                            <div class="jornada-info-label">ID Jornada</div>
                            <div class="jornada-info-value">#${jornada.jornada_id}</div>
                        </div>
                        <div class="jornada-info-item">
                            <div class="jornada-info-label">Total Pedidos</div>
                            <div class="jornada-info-value">${totalPedidos}</div>
                        </div>
                        <div class="jornada-info-item">
                            <div class="jornada-info-label">Entregados</div>
                            <div class="jornada-info-value" style="color: #10b981;">${entregados}</div>
                        </div>
                        <div class="jornada-info-item">
                            <div class="jornada-info-label">Devueltos</div>
                            <div class="jornada-info-value" style="color: #ef4444;">${devueltos}</div>
                        </div>
                        <div class="jornada-info-item">
                            <div class="jornada-info-label">Pendientes</div>
                            <div class="jornada-info-value" style="color: #f59e0b;">${pendientes}</div>
                        </div>
                    </div>
                    
                    <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                        <div class="bg-green-500 h-3 rounded-full transition-all" style="width: ${progreso}%"></div>
                    </div>
                    <div class="text-center text-sm text-gray-600 mb-4">Progreso: ${progreso}%</div>
                    
                    <div class="pedidos-list" id="pedidos-jornada-${jornada.jornada_id}">
                        <div class="pedidos-list-header">
                            <span>Pedidos de esta jornada</span>
                            <button onclick="loadPedidosJornada(${jornada.jornada_id})" 
                                    class="px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">
                                Ver pedidos (${totalPedidos})
                            </button>
                        </div>
                        <div class="text-center py-4 text-gray-500 text-sm">
                            Click en "Ver pedidos" para cargar los detalles
                        </div>
                    </div>
                </div>
            `;
                    });
                } else {
                    html +=
                        '<div class="text-center py-12 text-gray-500">No hay jornadas activas para esta fecha.</div>';
                }

                contentArea.innerHTML = html;
            }
            async function loadPedidosJornada(jornadaId) {
                const container = document.getElementById(`pedidos-jornada-${jornadaId}`);
                const listHeader = container.querySelector('.pedidos-list-header');

                // Mostrar loading
                container.innerHTML = listHeader.outerHTML +
                    '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div></div>';

                try {
                    const response = await fetch(`/admin/jornada/${jornadaId}/pedidos`, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': distributionCsrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success && data.pedidos) {
                        let pedidosHtml = listHeader.outerHTML;

                        if (data.pedidos.length > 0) {
                            data.pedidos.forEach(pedido => {
                                const estadoPedidoClass = `estado-${pedido.estado.replace(' ', '_')}`;
                                pedidosHtml += `
                        <div class="pedido-item">
                            <div class="pedido-info">
                                <div class="pedido-id">Pedido #${pedido.id}</div>
                                <div class="pedido-cliente">
                                    ${pedido.cliente_nombre || 'Cliente desconocido'}
                                    ${pedido.cliente_telefono ? ` - 📞 ${pedido.cliente_telefono}` : ''}
                                </div>
                            </div>
                            <div>
                                <span class="pedido-estado-badge ${estadoPedidoClass}">${pedido.estado}</span>
                                <div style="text-align: right; margin-top: 4px; font-weight: 700; color: #10b981;">
                                    L${parseFloat(pedido.total || 0).toFixed(2)}
                                </div>
                            </div>
                        </div>
                    `;
                            });
                        } else {
                            pedidosHtml += '<div class="no-pedidos">No hay pedidos asignados a esta jornada</div>';
                        }

                        container.innerHTML = pedidosHtml;
                    } else {
                        container.innerHTML = listHeader.outerHTML +
                            '<div class="text-center py-4 text-red-500">Error cargando pedidos</div>';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    container.innerHTML = listHeader.outerHTML +
                        '<div class="text-center py-4 text-red-500">Error de conexión</div>';
                }
            }

            function mostrarEstadoSistema(data) {
                const contentArea = document.getElementById('content-area');
                let html = '<h3 class="text-xl font-semibold mb-4">⚙️ Estado del Sistema</h3>';

                if (data.success && data.debug_info) {
                    const info = data.debug_info;
                    html += '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">';

                    html += `
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">📦 Sistema</h4>
                        <p><strong>Fecha:</strong> ${info.fecha_consultada || 'N/A'}</p>
                    </div>
                `;

                    if (info.repartidores_sistema) {
                        html += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">🚚 Repartidores</h4>
                            <p class="text-2xl font-bold text-blue-600">${info.repartidores_sistema.total || 0}</p>
                            <p class="text-sm text-gray-600">Total disponibles</p>
                        </div>
                    `;
                    }

                    if (info.jornadas_pendientes) {
                        html += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">📋 Jornadas</h4>
                            <p class="text-2xl font-bold text-yellow-600">${info.jornadas_pendientes.total || 0}</p>
                            <p class="text-sm text-gray-600">Pendientes</p>
                        </div>
                    `;
                    }

                    html += '</div>';
                } else {
                    html +=
                        '<div class="bg-red-50 border border-red-200 rounded-md p-4">Error obteniendo información del sistema</div>';
                }

                contentArea.innerHTML = html;
            }

            function mostrarPedidosDelDia(data) {
                if (data.resumen) {
                    document.getElementById('total-pedidos-dia').textContent = data.resumen.total || '0';
                    document.getElementById('despachados-sin-jornada').textContent = data.resumen
                        .despachados_sin_jornada || '0';
                    document.getElementById('despachados-en-jornada').textContent = data.resumen
                        .despachados_en_jornada || '0';
                    document.getElementById('pedidos-entregados').textContent = data.resumen.entregados || '0';
                }

                const categoriasContainer = document.getElementById('pedidos-categorias');
                let html = '';

                const categorias = [{
                    key: 'despachados_sin_jornada',
                    titulo: 'Pedidos Sin Asignar',
                    color: 'yellow'
                },
                {
                    key: 'despachados_en_jornada',
                    titulo: 'Pedidos en Jornadas',
                    color: 'cyan'
                },
                {
                    key: 'entregados',
                    titulo: 'Pedidos Entregados',
                    color: 'green'
                },
                {
                    key: 'devueltos',
                    titulo: 'Pedidos Devueltos',
                    color: 'red'
                }
                ];

                categorias.forEach(categoria => {
                    const pedidos = (data.categorias && data.categorias[categoria.key]) || [];
                    if (pedidos.length > 0) {
                        html += `
                <div class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-${categoria.color}-100 border-b border-${categoria.color}-200 p-3 flex justify-between items-center">
                        <h4 class="font-semibold text-${categoria.color}-800">${categoria.titulo}</h4>
                        <span class="bg-${categoria.color}-200 text-${categoria.color}-800 px-2 py-1 rounded-full text-sm font-bold">${pedidos.length}</span>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
            `;

                        pedidos.forEach(pedido => {
                            html += `
                    <div class="p-3 border-b border-gray-100 last:border-b-0 flex justify-between items-center hover:bg-gray-50">
                        <div>
                            <div class="font-semibold">Pedido #${pedido.id}</div>
                            <div class="text-sm text-gray-600">${pedido.cliente_nombre || 'Cliente desconocido'}</div>
                            ${pedido.repartidor_nombre ? `<div class="text-xs text-blue-600">Repartidor: ${pedido.repartidor_nombre}</div>` : ''}
                        </div>
                        <div class="font-bold text-green-600">L${pedido.total || '0'}</div>
                    </div>
                `;
                        });

                        html += '</div></div>';
                    }
                });

                if (html === '') {
                    html = '<div class="text-center py-8 text-gray-500">No hay pedidos para esta fecha</div>';
                }

                categoriasContainer.innerHTML = html;

                document.getElementById('pedidos-del-dia-content').style.display = 'block';
            }

            // Event Listeners - ESTO ES LO MÁS IMPORTANTE
            document.addEventListener('DOMContentLoaded', function () {
                console.log('🚀 DOM cargado, iniciando event listeners...');

                // Verificar que los botones existen
                const botones = ['btn-distribuir', 'btn-ver-distribucion', 'btn-reiniciar',
                    'btn-estado-sistema', 'btn-cargar-pedidos'
                ];
                botones.forEach(id => {
                    const btn = document.getElementById(id);
                    console.log(`Botón ${id}:`, btn ? 'Encontrado' : 'NO encontrado');
                });

                // Asignar event listeners
                document.getElementById('btn-distribuir')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Click en distribuir detectado');
                    distribuirAutomaticamente();
                });

                document.getElementById('btn-ver-distribucion')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Click en ver distribución detectado');
                    verDistribucionActual();
                });

                document.getElementById('btn-reiniciar')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Click en reiniciar detectado');
                    reiniciarDistribucion();
                });

                document.getElementById('btn-estado-sistema')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Click en estado sistema detectado');
                    checkSystemStatus();
                });

                document.getElementById('btn-cargar-pedidos')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('Click en cargar pedidos detectado');
                    loadPedidosDelDia();
                });

                console.log('✅ Event listeners configurados');
                loadPedidosDelDia();

                loadDriversWithAvailability();

                // Mostrar notificación de test
                setTimeout(() => {
                    showNotification('Sistema iniciado correctamente', 'success');
                }, 1000);
            });
            window.verDistribucionActual = verDistribucionActual;
            window.loadPedidosJornada = loadPedidosJornada;
            window.loadPedidosDelDia = loadPedidosDelDia;
        })(); // Fin de la función inmediatamente ejecutada
    </script>

    <script>
        // Función para cargar conductores con disponibilidad
        // Definir la función primero
       async function loadDriversWithAvailability() {
    try {
        console.log('Cargando conductores...');
        const response = await fetch('/admin/drivers/with-availability');
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        // DEBUG: Ver qué está retornando exactamente
        console.log('Respuesta completa:', data);
        console.log('Tipo de dato:', typeof data);
        console.log('Es array?', Array.isArray(data));
        console.log('Tiene drivers?', data.drivers);
        console.log('Tiene data?', data.data);
        
        // Manejar diferentes formatos de respuesta
        let drivers = data;
        
        // Si la respuesta es un objeto con propiedad "drivers"
        if (data && data.drivers && Array.isArray(data.drivers)) {
            drivers = data.drivers;
        }
        // Si la respuesta es un objeto con propiedad "data"  
        else if (data && data.data && Array.isArray(data.data)) {
            drivers = data.data;
        }
        // Si la respuesta ya es un array
        else if (Array.isArray(data)) {
            drivers = data;
        }
        // Si no es ninguno de los anteriores
        else {
            console.error('Formato de respuesta inesperado:', data);
            throw new Error('Formato de respuesta inesperado del servidor');
        }
        
        console.log('Drivers procesados:', drivers);
        renderDriversList(drivers);
        
    } catch (error) {
        console.error('Error loading drivers:', error);
        const container = document.getElementById('drivers-list');
        if (container) {
            container.innerHTML = '<div class="error">Error cargando conductores: ' + error.message + '</div>';
        }
    }
}

        // Luego, registrar el event listener
        document.addEventListener('DOMContentLoaded', function () {
            loadDriversWithAvailability();

            // Actualizar cada 30 segundos
            setInterval(loadDriversWithAvailability, 30000);
        });
}

        function renderDriversList(drivers) {
            const container = document.getElementById('drivers-list');

            container.innerHTML = drivers.map(driver => `
        <div class="driver-card ${driver.disponible_jornadas ? '' : 'disabled'} ${driver.jornadas_activas > 0 ? 'active-shift' : ''}">
            <div class="driver-header">
                <div class="driver-name">${driver.name}</div>
                <label class="toggle-switch">
                    <input type="checkbox" 
                           ${driver.disponible_jornadas ? 'checked' : ''} 
                           onchange="toggleDriverAvailability(${driver.id}, this.checked)"
                           ${driver.jornadas_activas > 0 ? 'disabled' : ''}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="driver-info">
                <small>${driver.email}</small>
            </div>
            <div style="margin-top: 10px;">
                <span class="driver-status ${driver.disponible_jornadas ? 'status-available' : 'status-unavailable'}">
                    ${driver.disponible_jornadas ? 'Disponible' : 'No disponible'}
                </span>
                ${driver.jornadas_activas > 0 ? `
                    <span class="driver-status status-active">
                        ${driver.jornadas_activas} jornada(s) activa
                    </span>
                ` : ''}
            </div>
        </div>
    `).join('');
        }

        // Renderizar lista de conductores
        function renderDriversList(drivers) {
            const container = document.getElementById('drivers-list');

            container.innerHTML = drivers.map(driver => `
        <div class="driver-card ${driver.disponible_jornadas ? '' : 'disabled'} ${driver.jornadas_activas > 0 ? 'active-shift' : ''}">
            <div class="driver-header">
                <div class="driver-name">${driver.name}</div>
                <label class="toggle-switch">
                    <input type="checkbox" 
                           ${driver.disponible_jornadas ? 'checked' : ''} 
                           onchange="toggleDriverAvailability(${driver.id}, this.checked)">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="driver-info">
                <small>${driver.email}</small>
            </div>
            <div style="margin-top: 10px;">
                <span class="driver-status ${driver.disponible_jornadas ? 'status-available' : 'status-unavailable'}">
                    ${driver.disponible_jornadas ? 'Disponible' : 'No disponible'}
                </span>
                ${driver.jornadas_activas > 0 ? `
                    <span class="driver-status" style="background: #fff3cd; color: #856404; margin-left: 5px;">
                        ${driver.jornadas_activas} jornada(s) activa(s)
                    </span>
                ` : ''}
            </div>
        </div>
    `).join('');
        }

        // Toggle disponibilidad de conductor
        public
        function toggleDriverAvailability(Request $request) {
            try {
                $request - > validate([
                    'driver_id' => 'required|exists:users,id',
                    'disponible' => 'required|boolean'
                ]);

                DB:: table('users') -
                > where('id', $request - > driver_id) -
                > update(['disponible_jornadas' => $request - > disponible]);

                return response() - > json([
                    'success' => true,
                    'message' => $request - > disponible ?
                        'Repartidor marcado como disponible' :
                        'Repartidor marcado como no disponible'
                ]);

            } catch (\Exception $e) {
                return response() - > json([
                    'success' => false,
                    'error' => 'Error actualizando disponibilidad: '.$e - > getMessage()
                ], 500);
            }
        }
        public
        function getDriversWithAvailability() {
            $drivers = DB:: table('users') -
            > join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id') -
            > join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id') -
            > where('permissions.name', 'Motorista') -
            > select(
                'users.id',
                'users.name',
                'users.email',
                'users.disponible_jornadas',
                DB:: raw(
                    '(SELECT COUNT(*) FROM jornadas_entrega WHERE driver_id = users.id AND estado = "en curso") as jornadas_activas'
                )
            ) -
            > get();

            return response() - > json($drivers);
        }

        // Cargar al iniciar la página
        document.addEventListener('DOMContentLoaded', function () {
            loadDriversWithAvailability();

            // Actualizar cada 30 segundos
            setInterval(loadDriversWithAvailability, 30000);
        });
    </script>
</x-app-layout>