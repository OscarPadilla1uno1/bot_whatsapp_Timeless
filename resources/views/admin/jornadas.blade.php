<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Distribución Automática de Pedidos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Header personalizado -->
                    <div class="mb-6 bg-gradient-to-r from-blue-500 to-purple-600 text-white p-6 rounded-lg text-center">
                        <h1 class="text-2xl font-bold mb-2">Distribución Automática de Pedidos</h1>
                        <p>Distribuye automáticamente todos los pedidos despachados entre los repartidores disponibles</p>
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
                                        📦 Cargar Pedidos
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
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <p class="mt-2 text-gray-600">Cargando...</p>
                            </div>
                            
                            <!-- Contenido principal -->
                            <div id="content-area">
                                <div class="text-center py-12 text-gray-500">
                                    <div class="text-6xl mb-4">📦</div>
                                    <h3 class="text-xl font-semibold mb-2">Sistema de Distribución Automática</h3>
                                    <p>Selecciona una acción del menú superior para comenzar.</p>
                                    <p class="text-sm mt-2">El sistema distribuirá automáticamente todos los pedidos "despachados" entre los repartidores disponibles.</p>
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
        // Envolver todo en una función inmediatamente ejecutada para evitar conflictos
        (function() {
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
            notification.className = `p-4 rounded-md shadow-lg transition-all duration-300 max-w-sm ${
                type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
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
            
            if (!confirm('¿Estás seguro de que quieres distribuir todos los pedidos despachados entre los repartidores disponibles?')) {
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
                body: JSON.stringify({ fecha: fecha })
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
            
            if (!confirm('¿Estás seguro de que quieres reiniciar la distribución? Esto eliminará todas las jornadas actuales.')) {
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
                body: JSON.stringify({ fecha: fecha })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Reinicio:', data);
                hideLoading();
                
                if (data.success) {
                    showNotification(data.message || 'Distribución reiniciada correctamente', 'success');
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
            let html = `<h3 class="text-xl font-semibold mb-4">📊 Distribución Actual - ${data.fecha}</h3>`;

            if (data.jornadas && data.jornadas.length > 0) {
                html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                data.jornadas.forEach(jornada => {
                    const totalPedidos = parseInt(jornada.pedidos_asignados) || 0;
                    const completados = (parseInt(jornada.pedidos_entregados) || 0) + (parseInt(jornada.pedidos_devueltos) || 0);
                    const progreso = totalPedidos > 0 ? (completados / totalPedidos * 100).toFixed(1) : 0;

                    html += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">${jornada.repartidor_nombre} - ${jornada.estado}</h4>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <div class="text-center bg-blue-50 rounded p-2">
                                    <div class="font-bold text-blue-600">${totalPedidos}</div>
                                    <div class="text-xs text-blue-600">Asignados</div>
                                </div>
                                <div class="text-center bg-green-50 rounded p-2">
                                    <div class="font-bold text-green-600">${completados}</div>
                                    <div class="text-xs text-green-600">Completados</div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full transition-all" style="width: ${progreso}%"></div>
                            </div>
                            <div class="text-center text-sm text-gray-600 mt-1">Progreso: ${progreso}%</div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += '<div class="text-center py-12 text-gray-500">No hay jornadas activas para esta fecha.</div>';
            }

            contentArea.innerHTML = html;
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
                html += '<div class="bg-red-50 border border-red-200 rounded-md p-4">Error obteniendo información del sistema</div>';
            }

            contentArea.innerHTML = html;
        }

        function mostrarPedidosDelDia(data) {
            if (data.resumen) {
                document.getElementById('total-pedidos-dia').textContent = data.resumen.total || '0';
                document.getElementById('despachados-sin-jornada').textContent = data.resumen.despachados_sin_jornada || '0';
                document.getElementById('despachados-en-jornada').textContent = data.resumen.despachados_en_jornada || '0';
                document.getElementById('pedidos-entregados').textContent = data.resumen.entregados || '0';
            }

            const categoriasContainer = document.getElementById('pedidos-categorias');
            let html = '';

            const categorias = [
                { key: 'despachados_sin_jornada', titulo: 'Pedidos Sin Asignar', color: 'yellow' },
                { key: 'despachados_en_jornada', titulo: 'Pedidos en Jornadas', color: 'cyan' },
                { key: 'entregados', titulo: 'Pedidos Entregados', color: 'green' },
                { key: 'devueltos', titulo: 'Pedidos Devueltos', color: 'red' }
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
        }

        // Event Listeners - ESTO ES LO MÁS IMPORTANTE
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 DOM cargado, iniciando event listeners...');

            // Verificar que los botones existen
            const botones = ['btn-distribuir', 'btn-ver-distribucion', 'btn-reiniciar', 'btn-estado-sistema', 'btn-cargar-pedidos'];
            botones.forEach(id => {
                const btn = document.getElementById(id);
                console.log(`Botón ${id}:`, btn ? 'Encontrado' : 'NO encontrado');
            });

            // Asignar event listeners
            document.getElementById('btn-distribuir')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Click en distribuir detectado');
                distribuirAutomaticamente();
            });

            document.getElementById('btn-ver-distribucion')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Click en ver distribución detectado');
                verDistribucionActual();
            });

            document.getElementById('btn-reiniciar')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Click en reiniciar detectado');
                reiniciarDistribucion();
            });

            document.getElementById('btn-estado-sistema')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Click en estado sistema detectado');
                checkSystemStatus();
            });

            document.getElementById('btn-cargar-pedidos')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Click en cargar pedidos detectado');
                loadPedidosDelDia();
            });

            console.log('✅ Event listeners configurados');
            
            // Mostrar notificación de test
            setTimeout(() => {
                showNotification('Sistema iniciado correctamente', 'success');
            }, 1000);
        });

        })(); // Fin de la función inmediatamente ejecutada
    </script>
</x-app-layout>