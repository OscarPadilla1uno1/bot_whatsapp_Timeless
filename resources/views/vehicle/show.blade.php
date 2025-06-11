</html><!DOCTYPE html>
<html lang="es">
<head>
    <title>Navegación GPS en Tiempo Real - Sistema de Entregas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            overflow: hidden;
        }

        #map {
            height: 100vh;
            width: 100%;
            position: relative;
        }

        /* ========== PANEL PRINCIPAL DE CONTROL ========== */
        .main-control-panel {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 2000;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            max-width: 350px;
            min-width: 300px;
        }

        .panel-header {
            padding: 20px 20px 0 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }

        .motorist-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .motorist-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .motorist-details h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .motorist-details p {
            font-size: 12px;
            color: #64748b;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-online {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-navigating {
            background: #dbeafe;
            color: #2563eb;
            animation: pulse 2s infinite;
        }

        .panel-content {
            padding: 0 20px 20px 20px;
        }

        /* ========== BOTONES PRINCIPALES ========== */
        .main-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn-primary {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            justify-content: center;
        }

        .btn-navigate {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .btn-navigate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-navigate.active {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            animation: pulse 2s infinite;
        }

        .btn-assignments {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-assignments:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-complete {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            display: none;
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        /* ========== PANEL DE NAVEGACIÓN GPS ========== */
        .navigation-panel {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 2000;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            min-width: 320px;
            display: none;
            backdrop-filter: blur(10px);
        }

        .navigation-panel.active {
            display: block;
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-close {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .nav-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-content {
            padding: 20px;
        }

        .current-instruction {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            text-align: center;
        }

        .instruction-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .instruction-distance {
            font-size: 14px;
            opacity: 0.8;
        }

        .progress-section {
            margin-bottom: 20px;
        }

        .progress-bar {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-fill {
            background: linear-gradient(90deg, #10b981, #34d399);
            height: 100%;
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 8px;
        }

        .progress-text {
            font-size: 12px;
            text-align: center;
            opacity: 0.8;
        }

        .nav-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .nav-stat {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.8;
        }

        .next-delivery {
            background: rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .delivery-client {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delivery-info {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .delivery-actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-start {
            background: #f59e0b;
            color: white;
        }

        .btn-complete-delivery {
            background: #10b981;
            color: white;
        }

        .btn-small:hover {
            transform: translateY(-1px);
        }

        /* ========== PANEL DE ASIGNACIONES ========== */
        .assignments-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 2000;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            max-width: 350px;
            min-width: 300px;
            max-height: 400px;
            display: none;
            overflow: hidden;
        }

        .assignments-panel.active {
            display: block;
            animation: slideInUp 0.5s ease-out;
        }

        @keyframes slideInUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .assignments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .assignments-content {
            padding: 16px;
            max-height: 280px;
            overflow-y: auto;
        }

        .assignment-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
            border-left: 4px solid #10b981;
            transition: all 0.3s ease;
        }

        .assignment-item:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .assignment-item.completed {
            border-left-color: #10b981;
            opacity: 0.7;
        }

        .assignment-item.in-progress {
            border-left-color: #f59e0b;
            animation: pulse 2s infinite;
        }

        .assignment-client {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .assignment-details {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .assignment-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
        }

        /* ========== CONTROLES DEL MAPA ========== */
        .map-controls {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .map-control-btn {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            min-width: 100px;
            text-align: center;
        }

        .map-control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        /* ========== INDICADORES EN TIEMPO REAL ========== */
        .speed-indicator {
            position: fixed;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 16px;
            border-radius: 12px;
            display: none;
            backdrop-filter: blur(10px);
        }

        .speed-value {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 4px;
        }

        .speed-unit {
            font-size: 12px;
            text-align: center;
            opacity: 0.8;
        }

        .arrival-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 24px 32px;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
            z-index: 3000;
            display: none;
            text-align: center;
        }

        .arrival-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .arrival-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .arrival-client {
            font-size: 14px;
            opacity: 0.9;
        }

        /* ========== MARCADORES DEL MAPA ========== */
        .vehicle-marker {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #3b82f6, #1d4ed8);
            border-radius: 50% 50% 50% 0;
            border: 3px solid white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
            animation: vehiclePulse 2s infinite;
        }

        @keyframes vehiclePulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(59, 130, 246, 0.6); }
            50% { box-shadow: 0 6px 25px rgba(59, 130, 246, 0.9); }
        }

        .destination-marker {
            width: 35px;
            height: 35px;
            background: #ef4444;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            animation: destinationPulse 2s infinite;
        }

        @keyframes destinationPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .completed-marker {
            background: #10b981 !important;
            animation: none !important;
            opacity: 0.7;
        }

        /* ========== MENSAJES Y NOTIFICACIONES ========== */
        .message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 3000;
            display: none;
        }

        .message.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .message.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .message.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        /* ========== LOADING ========== */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 5000;
            backdrop-filter: blur(5px);
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .loading-subtitle {
            font-size: 14px;
            color: #64748b;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .main-control-panel {
                left: 10px;
                right: 10px;
                max-width: none;
                min-width: auto;
            }

            .navigation-panel {
                right: 10px;
                left: 10px;
                min-width: auto;
            }

            .assignments-panel {
                right: 10px;
                left: 10px;
                bottom: 10px;
                max-width: none;
                min-width: auto;
            }

            .map-controls {
                left: 10px;
                bottom: 10px;
            }

            .speed-indicator {
                left: 10px;
                top: auto;
                bottom: 120px;
                transform: none;
            }
        }

        /* ========== ANIMACIONES ADICIONALES ========== */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes bounceIn {
            0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
            50% { transform: translate(-50%, -50%) scale(1.05); }
            70% { transform: translate(-50%, -50%) scale(0.9); }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        .route-line {
            stroke-width: 6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .completed-route {
            stroke: #10b981;
            stroke-dasharray: none;
        }

        .remaining-route {
            stroke: #94a3b8;
            stroke-dasharray: 10, 5;
        }
    </style>
</head>

<body>
    <!-- Mapa -->
    <div id="map"></div>

    <!-- Datos desde el backend (ocultos) -->
    <script id="backend-data" type="application/json">
        {
            "motorist": {
                "id": {{ $user_id }},
                "name": "{{ $user_name }}",
                "is_admin": {{ $is_admin ? 'true' : 'false' }},
                "is_motorista": {{ $is_motorista ? 'true' : 'false' }}
            },
            "routes": @json($routes),
            "vehicles": @json($vehicles),
            "jobs": @json($jobs),
            "csrf_token": "{{ csrf_token() }}"
        }
    </script>

    <!-- Panel Principal de Control -->
    <div class="main-control-panel">
        <div class="panel-header">
            <div class="motorist-info">
                <div class="motorist-avatar">{{ substr($user_name, 0, 2) }}</div>
                <div class="motorist-details">
                    <h3>{{ $user_name }}</h3>
                    <p>ID: {{ $user_id }} - <span class="status-indicator status-online">🟢 En línea</span></p>
                </div>
            </div>
        </div>

        <div class="panel-content">
            <div class="main-actions">
                <button class="btn-primary btn-navigate" onclick="toggleNavigation()">
                    <span>🧭</span>
                    <span id="nav-btn-text">Iniciar Navegación GPS</span>
                </button>

                <button class="btn-primary btn-assignments" onclick="toggleAssignments()">
                    <span>📋</span>
                    <span>Ver Mis Entregas</span>
                </button>

                <button class="btn-primary btn-complete" id="complete-delivery-btn" onclick="completeCurrentDelivery()" style="display: none;">
                    <span>✅</span>
                    <span>Completar Entrega</span>
                </button>
            </div>

            <div class="quick-stats" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                <div style="background: #f1f5f9; padding: 12px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 20px; font-weight: bold; color: #3b82f6;" id="pending-count">0</div>
                    <div style="font-size: 12px; color: #64748b;">Pendientes</div>
                </div>
                <div style="background: #f1f5f9; padding: 12px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 20px; font-weight: bold; color: #10b981;" id="completed-count">0</div>
                    <div style="font-size: 12px; color: #64748b;">Completadas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Navegación GPS -->
    <div class="navigation-panel" id="navigation-panel">
        <div class="nav-header">
            <div class="nav-title">
                <span>🧭</span>
                <span>Navegación GPS Activa</span>
            </div>
            <button class="nav-close" onclick="stopNavigation()">✕</button>
        </div>

        <div class="nav-content">
            <!-- Próxima Entrega -->
            <div class="next-delivery">
                <div class="delivery-client">
                    📦 <span id="next-client">Cargando...</span>
                </div>
                <div class="delivery-info" id="next-info">
                    📍 Obteniendo información...
                </div>
                <div class="delivery-actions">
                    <button class="btn-small btn-start" onclick="startDelivery()">🚛 Iniciar</button>
                    <button class="btn-small btn-complete-delivery" onclick="completeDelivery()" style="display: none;">✅ Completar</button>
                </div>
            </div>

            <!-- Instrucción Actual -->
            <div class="current-instruction">
                <div class="instruction-text" id="instruction-text">
                    Iniciando navegación GPS...
                </div>
                <div class="instruction-distance" id="instruction-distance">
                    Calculando ruta...
                </div>
            </div>

            <!-- Progreso -->
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-text" id="progress-text">Progreso: 0%</div>
            </div>

            <!-- Estadísticas -->
            <div class="nav-stats">
                <div class="nav-stat">
                    <div class="stat-value" id="current-speed">0</div>
                    <div class="stat-label">km/h</div>
                </div>
                <div class="nav-stat">
                    <div class="stat-value" id="eta">--:--</div>
                    <div class="stat-label">ETA</div>
                </div>
            </div>

            <!-- Controles adicionales -->
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button class="btn-small" onclick="centerOnVehicle()" style="flex: 1; background: rgba(255,255,255,0.2); color: white;">
                    📍 Centrar
                </button>
                <button class="btn-small" onclick="recalculateRoute()" style="flex: 1; background: rgba(255,255,255,0.2); color: white;">
                    🔄 Recalcular
                </button>
                <button class="btn-small" onclick="toggleVoice()" style="flex: 1; background: rgba(255,255,255,0.2); color: white;" id="voice-btn">
                    🔊 Voz
                </button>
            </div>
        </div>
    </div>

    <!-- Panel de Asignaciones -->
    <div class="assignments-panel" id="assignments-panel">
        <div class="assignments-header">
            <div style="font-size: 16px; font-weight: 600;">📋 Mis Entregas</div>
            <button class="nav-close" onclick="toggleAssignments()">✕</button>
        </div>

        <div class="assignments-content" id="assignments-content">
            <!-- Las asignaciones se cargarán aquí dinámicamente -->
        </div>

        <div style="padding: 16px; border-top: 1px solid rgba(255,255,255,0.2);">
            <button onclick="refreshAssignments()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 12px; border-radius: 8px; width: 100%; cursor: pointer;">
                🔄 Actualizar Entregas
            </button>
        </div>
    </div>

    <!-- Controles del Mapa -->
    <div class="map-controls">
        <button class="map-control-btn" onclick="locateUser()">📍 Mi Ubicación</button>
        <button class="map-control-btn" onclick="fitMapToRoute()">🔍 Ajustar Vista</button>
        <button class="map-control-btn" onclick="toggleMapView()">🌍 Satélite</button>
    </div>

    <!-- Indicador de Velocidad -->
    <div class="speed-indicator" id="speed-indicator">
        <div class="speed-value" id="current-speed-display">0</div>
        <div class="speed-unit">km/h</div>
    </div>

    <!-- Notificación de Llegada -->
    <div class="arrival-notification" id="arrival-notification">
        <div class="arrival-icon">🎯</div>
        <div class="arrival-text">¡Has llegado!</div>
        <div class="arrival-client" id="arrival-client">Cliente</div>
    </div>

    <!-- Mensajes -->
    <div class="message success" id="success-message"></div>
    <div class="message error" id="error-message"></div>
    <div class="message warning" id="warning-message"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Iniciando Navegación GPS</div>
        <div class="loading-subtitle">Obteniendo tu ubicación y calculando ruta...</div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <script>
        // ========== CONFIGURACIÓN GLOBAL ==========
        console.log('🚀 Iniciando Sistema de Navegación GPS en Tiempo Real');

        // Cargar datos desde el backend
        const backendData = JSON.parse(document.getElementById('backend-data').textContent);
        console.log('📊 Datos del backend cargados:', backendData);

        // Variables globales del sistema
        let map;
        let tileLayer;
        let satelliteLayer;
        let isSatelliteView = false;

        // Control de navegación
        let isNavigating = false;
        let watchId = null;
        let navigationInterval = null;
        let currentPosition = null;
        let currentRoute = null;
        let voiceEnabled = true;
        let speechSynthesis = window.speechSynthesis;

        // Datos de rutas y entregas
        let currentAssignments = [];
        let currentDestinations = [];
        let currentDestinationIndex = 0;
        let routeCoordinates = [];
        let currentInstructions = [];

        // Marcadores y elementos del mapa
        let vehicleMarker = null;
        let routeLine = null;
        let completedRouteLine = null;
        let remainingRouteLine = null;
        let destinationMarkers = [];
        let currentMarkers = [];

        // Estados de la interfaz
        let isAssignmentsPanelOpen = false;
        let currentDeliveryId = null;

        // Variables para manejo de geometría de rutas reales
        let routeGeometry = null;
        let decodedRouteCoordinates = [];
        let routeSteps = [];
        let totalRouteDistance = 0;
        let totalRouteDuration = 0;

        // Datos del motorista desde el backend
        const demoData = {
            motoristId: backendData.motorist.id,
            motoristName: backendData.motorist.name,
            isAdmin: backendData.motorist.is_admin,
            isMotorista: backendData.motorist.is_motorista,
            assignments: [],
            currentRoute: null
        };

        // ========== INICIALIZACIÓN ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Inicializando aplicación...');
            
            // Agregar funciones de debug globales
            window.debugGeometry = logGeometryStatus;
            window.debugInfo = debugInfo;
            window.simulateArrival = simulateArrival;
            
            initializeApp();
        });

        async function initializeApp() {
            try {
                // Inicializar mapa
                initMap();
                
                // Verificar geolocalización
                checkGeolocationSupport();
                
                // Cargar datos iniciales con geometría real
                await loadInitialDataWithGeometry();
                
                // Configurar eventos
                setupEventListeners();
                
                // Ocultar loading
                hideLoading();
                
                console.log('✅ Aplicación inicializada correctamente');
                showSuccessMessage('🎯 Sistema GPS listo con rutas reales de VROOM. ¡Inicia tu navegación!');
                
            } catch (error) {
                console.error('❌ Error en inicialización:', error);
                showErrorMessage('Error al inicializar el sistema: ' + error.message);
                hideLoading();
            }
        }

        function initMap() {
            console.log('🗺️ Inicializando mapa...');
            
            map = L.map('map', {
                center: [14.0821, -87.2065], // Tegucigalpa
                zoom: 13,
                zoomControl: false
            });
            
            L.control.zoom({ position: 'topright' }).addTo(map);
            
            // Capas de mapa
            tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri'
            });
            
            console.log('✅ Mapa inicializado');
        }

        function checkGeolocationSupport() {
            if (!navigator.geolocation) {
                showErrorMessage('❌ Tu dispositivo no soporta geolocalización GPS');
                return false;
            }
            
            navigator.permissions?.query({name: 'geolocation'}).then(function(result) {
                console.log('📍 Estado de permisos GPS:', result.state);
                
                if (result.state === 'denied') {
                    showWarningMessage('⚠️ Permisos de ubicación denegados. Permite el acceso para usar la navegación.');
                }
            }).catch(function(error) {
                console.warn('⚠️ No se pudo verificar permisos GPS:', error);
            });
            
            return true;
        }

        // ========== CARGA DE DATOS CON GEOMETRÍA REAL ==========
async function loadInitialDataWithGeometry() {
    console.log('📊 Cargando datos iniciales con geometría real desde VROOM...');
    
    try {
        // Buscar ruta del usuario actual en los datos del backend
        const userRoute = backendData.routes.find(route => route.vehicle === demoData.motoristId);
        
        // 🔍 DEBUG: Mostrar todos los datos del backend
        console.log('🔍 DEBUG - Datos completos del backend:', {
            totalRoutes: backendData.routes.length,
            routesAvailable: backendData.routes.map(r => ({
                vehicle: r.vehicle,
                hasGeometry: !!r.geometry,
                geometryType: typeof r.geometry,
                geometryLength: r.geometry?.length || 0,
                geometryPreview: r.geometry?.substring(0, 50) + '...' || 'Sin geometría'
            })),
            currentMotorista: demoData.motoristId,
            userRouteFound: !!userRoute
        });
        
        if (userRoute && userRoute.geometry) {
            console.log('✅ Ruta con geometría encontrada en datos del backend');
            
            // 🔍 DEBUG: Mostrar geometría completa antes de procesar
            console.log('🗺️ DEBUG - GEOMETRÍA CRUDA DE VROOM:', {
                vehicle: userRoute.vehicle,
                geometryString: userRoute.geometry,
                geometryLength: userRoute.geometry.length,
                geometryType: typeof userRoute.geometry,
                first100chars: userRoute.geometry.substring(0, 100),
                last100chars: userRoute.geometry.substring(userRoute.geometry.length - 100),
                stepsCount: userRoute.steps?.length || 0,
                distance: userRoute.distance,
                duration: userRoute.duration
            });
            
            await processVroomRoute(userRoute);
        } else {
            console.log('⚠️ No se encontró ruta con geometría para el usuario', demoData.motoristId);
            
            // 🔍 DEBUG: Mostrar por qué no se encontró
            if (!userRoute) {
                console.log('❌ DEBUG - No existe ruta para vehicle_id:', demoData.motoristId);
                console.log('🔍 DEBUG - Vehículos disponibles:', backendData.routes.map(r => r.vehicle));
            } else if (!userRoute.geometry) {
                console.log('❌ DEBUG - Ruta existe pero sin geometría:', userRoute);
            }
            
            const assignmentsLoaded = await loadAssignmentsFromBackend();
            
            if (!assignmentsLoaded) {
                console.log('📝 Usando datos de ejemplo con geometría');
                await loadFallbackDataWithGeometry();
            }
        }
        
        // 2. Actualizar UI
        updateAssignmentCounts();
        updateNextDeliveryInfo();
        
        console.log('✅ Datos cargados exitosamente:', {
            assignments: currentAssignments.length,
            destinations: currentDestinations.length,
            hasGeometry: decodedRouteCoordinates.length > 0,
            geometryPoints: decodedRouteCoordinates.length
        });
        
    } catch (error) {
        console.error('❌ Error cargando datos:', error);
        await loadFallbackDataWithGeometry();
    }
}

// 2. En processVroomRoute() - DESPUÉS de línea 820 aproximadamente
async function processVroomRoute(route) {
    console.log('🛣️ Procesando ruta VROOM con geometría incluida...');
    
    demoData.currentRoute = route;
    routeGeometry = route.geometry;
    routeSteps = route.steps || [];
    totalRouteDistance = route.distance || 0;
    totalRouteDuration = route.duration || 0;
    
    // 🔍 DEBUG: Información detallada de la geometría
    console.log('📊 DEBUG - ANÁLISIS COMPLETO DE LA RUTA VROOM:', {
        // Información básica
        vehicle: route.vehicle,
        hasGeometry: !!routeGeometry,
        geometryType: typeof routeGeometry,
        geometryLength: routeGeometry?.length || 0,
        stepsCount: routeSteps.length,
        distance: `${(totalRouteDistance/1000).toFixed(1)} km (${totalRouteDistance}m)`,
        duration: `${Math.round(totalRouteDuration/60)} min (${totalRouteDuration}s)`,
        
        // Análisis de la geometría
        geometryAnalysis: {
            isEmpty: !routeGeometry || routeGeometry.length === 0,
            isString: typeof routeGeometry === 'string',
            startsWithUnderscore: routeGeometry?.startsWith('_') || false,
            containsBackslashes: routeGeometry?.includes('\\') || false,
            first50chars: routeGeometry?.substring(0, 50) || 'N/A',
            last50chars: routeGeometry?.substring(Math.max(0, (routeGeometry?.length || 0) - 50)) || 'N/A',
            // Intenta decodificar una pequeña muestra para ver si es válida
            sampleDecodeTest: (() => {
                try {
                    if (routeGeometry && typeof routeGeometry === 'string' && routeGeometry.length > 10) {
                        // Intenta decodificar solo los primeros 20 caracteres para test
                        const testSample = routeGeometry.substring(0, 20);
                        if (typeof polyline !== 'undefined' && polyline.decode) {
                            polyline.decode(testSample);
                            return 'VÁLIDA - Formato polyline correcto';
                        }
                        return 'LIBRERÍA POLYLINE NO DISPONIBLE';
                    }
                    return 'GEOMETRÍA VACÍA O INVÁLIDA';
                } catch (e) {
                    return `ERROR: ${e.message}`;
                }
            })()
        },
        
        // Análisis de los pasos
        stepsAnalysis: routeSteps.map((step, index) => ({
            index,
            type: step.type,
            hasLocation: !!step.location,
            location: step.location,
            distance: step.distance,
            duration: step.duration,
            jobId: step.job,
            jobDetails: step.job_details
        })),
        
        // Toda la ruta completa para debugging
        fullRouteObject: route
    });
    
    // Decodificar la geometría
    if (routeGeometry) {
        console.log('🔄 DEBUG - Iniciando decodificación de geometría...');
        console.log('🔍 DEBUG - Geometría a decodificar:', {
            fullGeometry: routeGeometry,
            length: routeGeometry.length,
            type: typeof routeGeometry
        });
        
        decodedRouteCoordinates = await decodeRouteGeometry(routeGeometry);
        
        console.log('📍 DEBUG - Resultado de decodificación:', {
            success: decodedRouteCoordinates.length > 0,
            pointsCount: decodedRouteCoordinates.length,
            firstPoint: decodedRouteCoordinates[0],
            lastPoint: decodedRouteCoordinates[decodedRouteCoordinates.length - 1],
            samplePoints: decodedRouteCoordinates.slice(0, 5), // Primeros 5 puntos
            coordinateTypes: decodedRouteCoordinates.slice(0, 3).map(coord => ({
                lat: coord.lat,
                lng: coord.lng,
                isLatLng: coord instanceof L.LatLng
            }))
        });
    } else {
        console.warn('⚠️ DEBUG - No se encontró geometría en la ruta de VROOM');
        decodedRouteCoordinates = [];
    }
    
    // Resto del procesamiento...
    currentDestinations = [];
    currentAssignments = [];
    
    for (const step of routeSteps) {
        if (step.type === 'job' && step.location) {
            const assignment = {
                id: step.job,
                status: 'pending',
                cliente: step.job_details?.cliente || 'Cliente desconocido',
                telefono: step.job_details?.telefono || '',
                direccion: step.job_details?.direccion || '',
                coordenadas: {
                    lat: step.location[1], // VROOM usa [lng, lat]
                    lng: step.location[0]
                }
            };
            
            currentAssignments.push(assignment);
            
            currentDestinations.push({
                id: step.job,
                cliente: assignment.cliente,
                telefono: assignment.telefono,
                direccion: assignment.direccion,
                lat: assignment.coordenadas.lat,
                lng: assignment.coordenadas.lng,
                status: 'pending',
                stepInfo: step
            });
        }
    }
    
    console.log('✅ DEBUG - Ruta VROOM procesada exitosamente:', {
        destinations: currentDestinations.length,
        assignments: currentAssignments.length,
        geometryPoints: decodedRouteCoordinates.length,
        hasRealRoute: decodedRouteCoordinates.length > 2,
        processingSuccess: true
    });
}

// 3. En decodeRouteGeometry() - REEMPLAZAR función completa
async function decodeRouteGeometry(geometry) {
    try {
        console.log('🔄 DEBUG - Iniciando decodificación de polyline...');
        console.log('🔍 DEBUG - Input para decodificación:', {
            geometry: geometry,
            type: typeof geometry,
            length: geometry?.length || 0,
            isEmpty: !geometry || geometry.length === 0,
            isString: typeof geometry === 'string'
        });
        
        // Verificar que tenemos una geometría válida
        if (!geometry || typeof geometry !== 'string' || geometry.length === 0) {
            console.error('❌ DEBUG - Geometría inválida para decodificación');
            return [];
        }
        
        // Si tienes la librería polyline disponible
        if (typeof polyline !== 'undefined' && polyline.decode) {
            console.log('✅ DEBUG - Usando librería polyline para decodificar');
            
            try {
                const decoded = polyline.decode(geometry);
                console.log('🔍 DEBUG - Resultado de polyline.decode():', {
                    success: true,
                    pointsCount: decoded.length,
                    firstPoints: decoded.slice(0, 3),
                    lastPoints: decoded.slice(-3),
                    samplePoint: decoded[0]
                });
                
                const leafletCoords = decoded.map(point => L.latLng(point[0], point[1]));
                console.log('✅ DEBUG - Conversión a Leaflet exitosa:', {
                    leafletCoordsCount: leafletCoords.length,
                    firstLeafletCoord: leafletCoords[0],
                    sampleCoordinate: {
                        lat: leafletCoords[0]?.lat,
                        lng: leafletCoords[0]?.lng
                    }
                });
                
                return leafletCoords;
            } catch (polylineError) {
                console.error('❌ DEBUG - Error en polyline.decode():', {
                    error: polylineError.message,
                    geometryPreview: geometry.substring(0, 100),
                    stack: polylineError.stack
                });
                throw polylineError;
            }
        } else {
            console.warn('⚠️ DEBUG - Librería polyline no disponible, usando decodificación manual');
            
            // Fallback: decodificación manual básica de polyline
            const manualResult = decodePolylineManual(geometry);
            console.log('🔍 DEBUG - Resultado de decodificación manual:', {
                success: manualResult.length > 0,
                pointsCount: manualResult.length,
                firstPoint: manualResult[0],
                lastPoint: manualResult[manualResult.length - 1]
            });
            
            return manualResult;
        }
        
    } catch (error) {
        console.error('❌ DEBUG - Error crítico decodificando geometría:', {
            error: error.message,
            stack: error.stack,
            geometryPreview: geometry?.substring(0, 100) || 'N/A',
            geometryLength: geometry?.length || 0
        });
        
        // Último recurso: usar los puntos de los pasos
        console.log('🔄 DEBUG - Usando coordenadas de pasos como último recurso');
        const coordinates = [];
        routeSteps.forEach((step, index) => {
            if (step.location) {
                coordinates.push(L.latLng(step.location[1], step.location[0]));
                console.log(`📍 DEBUG - Paso ${index}:`, {
                    type: step.type,
                    location: step.location,
                    converted: { lat: step.location[1], lng: step.location[0] }
                });
            }
        });
        
        console.log('✅ DEBUG - Coordenadas de pasos extraídas:', {
            pointsFromSteps: coordinates.length,
            coordinates: coordinates
        });
        
        return coordinates;
    }
}


        function decodePolylineManual(encoded) {
            const coordinates = [];
            let index = 0;
            let lat = 0;
            let lng = 0;
            
            while (index < encoded.length) {
                let result = 1;
                let shift = 0;
                let b;
                
                do {
                    b = encoded.charCodeAt(index++) - 63 - 1;
                    result += b << shift;
                    shift += 5;
                } while (b >= 0x1f);
                
                lat += (result & 1) ? (~result >> 1) : (result >> 1);
                
                result = 1;
                shift = 0;
                
                do {
                    b = encoded.charCodeAt(index++) - 63 - 1;
                    result += b << shift;
                    shift += 5;
                } while (b >= 0x1f);
                
                lng += (result & 1) ? (~result >> 1) : (result >> 1);
                
                coordinates.push(L.latLng(lat / 1e5, lng / 1e5));
            }
            
            return coordinates;
        }

        async function loadAssignmentsFromBackend() {
            try {
                console.log('🔄 Cargando asignaciones desde el controlador...');
                
                const response = await fetch(`/api/get-assigned-jobs/${demoData.motoristId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': backendData.csrf_token
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.assigned_jobs) {
                    currentAssignments = data.data.assigned_jobs.map(job => ({
                        id: job.id,
                        status: job.status || 'pending',
                        cliente: job.cliente || job.assignment_data?.cliente || 'Cliente desconocido',
                        telefono: job.assignment_data?.telefono || '',
                        direccion: job.assignment_data?.direccion || '',
                        coordenadas: {
                            lat: job.location[1],
                            lng: job.location[0]
                        }
                    }));
                    
                    currentDestinations = currentAssignments.map(assignment => ({
                        id: assignment.id,
                        cliente: assignment.cliente,
                        telefono: assignment.telefono,
                        direccion: assignment.direccion,
                        lat: assignment.coordenadas.lat,
                        lng: assignment.coordenadas.lng,
                        status: assignment.status
                    }));
                    
                    console.log('✅ Asignaciones cargadas desde el controlador:', currentAssignments.length);
                    return true;
                } else {
                    throw new Error(data.error || 'Error obteniendo asignaciones del controlador');
                }
            } catch (error) {
                console.error('❌ Error cargando asignaciones desde controlador:', error);
                return false;
            }
        }

        async function loadFallbackDataWithGeometry() {
            console.log('📝 Cargando datos de ejemplo con geometría detallada...');
            
            currentAssignments = [
                {
                    id: 3,
                    status: 'pending',
                    cliente: 'Carlos Martínez',
                    telefono: '+504 9999-9999',
                    direccion: 'Colonia Palmira, Tegucigalpa',
                    coordenadas: { lat: 14.094832223270211, lng: -87.18619506686059 }
                },
                {
                    id: 1,
                    status: 'pending',
                    cliente: 'Juan Pérez',
                    telefono: '+504 8888-8888',
                    direccion: 'Barrio El Centro, Tegucigalpa',
                    coordenadas: { lat: 14.0849932, lng: -87.1797737 }
                }
            ];
            
            currentDestinations = currentAssignments.map(assignment => ({
                id: assignment.id,
                cliente: assignment.cliente,
                telefono: assignment.telefono,
                direccion: assignment.direccion,
                lat: assignment.coordenadas.lat,
                lng: assignment.coordenadas.lng,
                status: assignment.status
            }));
            
            // Crear geometría detallada simulando polyline decodificado de VROOM
            decodedRouteCoordinates = [
                L.latLng(14.0667, -87.1875),    // Inicio
                L.latLng(14.0680, -87.1870),    // Punto 1
                L.latLng(14.0700, -87.1865),    // Punto 2
                L.latLng(14.0720, -87.1862),    // Punto 3
                L.latLng(14.0750, -87.1860),    // Punto 4
                L.latLng(14.0780, -87.1858),    // Punto 5
                L.latLng(14.0810, -87.1855),    // Punto 6
                L.latLng(14.0840, -87.1852),    // Punto 7
                L.latLng(14.0870, -87.1850),    // Punto 8
                L.latLng(14.0900, -87.1848),    // Punto 9
                L.latLng(14.094832223270211, -87.18619506686059), // Carlos
                L.latLng(14.0930, -87.1845),    // Salida de Carlos
                L.latLng(14.0910, -87.1840),    // Punto intermedio
                L.latLng(14.0890, -87.1835),    // Punto intermedio
                L.latLng(14.0870, -87.1830),    // Punto intermedio
                L.latLng(14.0849932, -87.1797737) // Juan
            ];
            
            // Simular datos de VROOM
            totalRouteDistance = 5500; // 5.5 km
            totalRouteDuration = 1080; // 18 minutos
            
            routeSteps = [
                {
                    type: 'start',
                    location: [-87.1875, 14.0667],
                    distance: 0,
                    duration: 0
                },
                {
                    type: 'job',
                    job: 3,
                    location: [-87.18619506686059, 14.094832223270211],
                    distance: 3000,
                    duration: 540,
                    job_details: { cliente: 'Carlos Martínez' }
                },
                {
                    type: 'job',
                    job: 1,
                    location: [-87.1797737, 14.0849932],
                    distance: 2500,
                    duration: 540,
                    job_details: { cliente: 'Juan Pérez' }
                }
            ];
            
            console.log('✅ Datos de ejemplo con geometría detallada cargados:', {
                destinations: currentDestinations.length,
                geometryPoints: decodedRouteCoordinates.length,
                steps: routeSteps.length
            });
        }

        function setupEventListeners() {
            // Eventos del mapa
            map.on('click', function(e) {
                console.log("🎯 Coordenadas:", e.latlng.lat, e.latlng.lng);
            });
        }

        // ========== NAVEGACIÓN GPS CON GEOMETRÍA REAL ==========
        async function toggleNavigation() {
            if (isNavigating) {
                stopNavigation();
            } else {
                await startNavigation();
            }
        }

        async function startNavigation() {
            console.log('🧭 Iniciando navegación GPS con geometría real...');
            
            if (!navigator.geolocation) {
                showErrorMessage('Tu dispositivo no soporta geolocalización');
                return;
            }
            
            try {
                showLoading('Iniciando Navegación GPS', 'Obteniendo tu ubicación...');
                
                // 1. Obtener ubicación actual
                const position = await getCurrentPosition();
                currentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed || 0,
                    heading: position.coords.heading || 0,
                    timestamp: Date.now()
                };
                
                console.log('📍 Ubicación obtenida:', currentPosition);
                
                // 2. Asegurar que tenemos geometría real
                showLoading('Iniciando Navegación GPS', 'Cargando ruta real desde VROOM...');
                const hasGeometry = await ensureRouteGeometry();
                
                if (!hasGeometry) {
                    console.warn('⚠️ No se pudo obtener geometría real, usando datos de ejemplo');
                    await loadFallbackDataWithGeometry();
                }
                
                // 3. Calcular ruta con geometría real
                await calculateRouteWithGeometry();
                
                // 4. Iniciar seguimiento en tiempo real
                startRealTimeTracking();
                
                // 5. Actualizar interfaz
                isNavigating = true;
                showNavigationPanel();
                updateNavigationButton();
                updateStatusIndicator('navigating');
                
                // 6. Anuncio de voz
                if (voiceEnabled && currentDestinations.length > 0) {
                    speak('Navegación GPS iniciada con ruta real. Dirigiéndote hacia ' + currentDestinations[0].cliente);
                }
                
                hideLoading();
                showSuccessMessage('🧭 Navegación GPS iniciada con ruta real de VROOM');
                
                console.log('✅ Navegación iniciada con geometría real');
                
            } catch (error) {
                console.error('❌ Error al iniciar navegación:', error);
                hideLoading();
                showErrorMessage('Error al iniciar navegación: ' + error.message);
            }
        }

        async function ensureRouteGeometry() {
            console.log('🔍 Verificando geometría de VROOM...');
            
            // Si ya tenemos geometría decodificada
            if (decodedRouteCoordinates.length > 0) {
                console.log('✅ Ya tenemos geometría decodificada con', decodedRouteCoordinates.length, 'puntos');
                return true;
            }
            
            // Si tenemos geometría cruda pero no decodificada
            if (routeGeometry) {
                console.log('🔄 Decodificando geometría existente...');
                decodedRouteCoordinates = await decodeRouteGeometry(routeGeometry);
                if (decodedRouteCoordinates.length > 0) {
                    console.log('✅ Geometría decodificada exitosamente');
                    return true;
                }
            }
            
            // Buscar en datos del backend nuevamente
            const userRoute = backendData.routes.find(route => route.vehicle === demoData.motoristId);
            if (userRoute && userRoute.geometry) {
                console.log('🔄 Procesando ruta desde datos del backend...');
                await processVroomRoute(userRoute);
                return decodedRouteCoordinates.length > 0;
            }
            
            // Solicitar nueva ruta al backend
            if (currentPosition) {
                console.log('🔄 Solicitando nueva ruta al backend...');
                const newRoute = await requestNewRouteFromBackend();
                if (newRoute) {
                    await processVroomRoute(newRoute);
                    return decodedRouteCoordinates.length > 0;
                }
            }
            
            console.warn('⚠️ No se pudo obtener geometría de VROOM');
            return false;
        }

        async function requestNewRouteFromBackend() {
            if (!currentPosition) {
                console.warn('⚠️ No hay posición actual para solicitar ruta');
                return null;
            }
            
            try {
                console.log('🔄 Solicitando nueva ruta con geometría al backend...');
                
                const response = await fetch('/vehicle/seguir-ruta', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': backendData.csrf_token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        current_location: [currentPosition.lat, currentPosition.lng],
                        vehicle_id: demoData.motoristId,
                        force_recalculate: true
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    
                    if (route.geometry) {
                        console.log('✅ Nueva ruta con geometría obtenida del backend');
                        return route;
                    } else {
                        console.warn('⚠️ Ruta del backend no contiene geometría');
                    }
                } else {
                    throw new Error(data.error || 'Respuesta inválida del backend');
                }
                
                return null;
                
            } catch (error) {
                console.error('❌ Error solicitando nueva ruta:', error);
                return null;
            }
        }

      async function calculateRouteWithGeometry() {
    console.log('🗺️ DEBUG - Iniciando calculateRouteWithGeometry()');
    
    if (currentDestinations.length === 0) {
        console.error('❌ DEBUG - No hay destinos disponibles');
        throw new Error('No hay destinos disponibles');
    }
    
    const destination = currentDestinations[currentDestinationIndex];
    console.log('🎯 DEBUG - Calculando ruta hacia:', {
        destination: destination,
        destinationIndex: currentDestinationIndex,
        totalDestinations: currentDestinations.length
    });
    
    // Verificar que tenemos geometría decodificada
    if (decodedRouteCoordinates.length === 0) {
        console.error('❌ DEBUG - No hay geometría decodificada disponible');
        console.log('🔍 DEBUG - Estado actual:', {
            decodedRouteCoordinatesLength: decodedRouteCoordinates.length,
            routeGeometryExists: !!routeGeometry,
            routeGeometryLength: routeGeometry?.length || 0
        });
        throw new Error('No hay geometría decodificada disponible');
    }
    
    console.log('✅ DEBUG - Usando geometría real de VROOM:', {
        originalGeometryLength: routeGeometry?.length || 0,
        decodedPointsCount: decodedRouteCoordinates.length,
        firstPoint: decodedRouteCoordinates[0],
        lastPoint: decodedRouteCoordinates[decodedRouteCoordinates.length - 1]
    });
    
    // Usar las coordenadas decodificadas directamente
    routeCoordinates = [...decodedRouteCoordinates];
    
    console.log('📋 DEBUG - Coordenadas asignadas a routeCoordinates:', {
        routeCoordinatesLength: routeCoordinates.length,
        copiedSuccessfully: routeCoordinates.length === decodedRouteCoordinates.length,
        firstCoordinate: routeCoordinates[0],
        lastCoordinate: routeCoordinates[routeCoordinates.length - 1]
    });
    
    
    // Generar instrucciones basadas en los pasos reales de VROOM
    currentInstructions = generateInstructionsFromSteps();
    
    // Dibujar la ruta real en el mapa
    console.log('🎨 DEBUG - Llamando a drawRoute() con geometría real');
    drawRoute();
    
    console.log(`🛣️ DEBUG - Ruta real procesada exitosamente:`, {
        distanceKm: (totalRouteDistance/1000).toFixed(1),
        durationMin: Math.round(totalRouteDuration/60),
        routeCoordinatesCount: routeCoordinates.length,
        instructionsCount: currentInstructions.length,
        drawRouteCompleted: true
    });
}

        function generateInstructionsFromSteps() {
            const instructions = [];
            
            if (routeSteps.length === 0) {
                return [{
                    instruction: 'Sigue la ruta indicada',
                    distance: totalRouteDistance / 2
                }];
            }
            
            routeSteps.forEach((step, index) => {
                let instruction = 'Continúa por la ruta';
                
                switch (step.type) {
                    case 'start':
                        instruction = '🚀 Inicia tu recorrido';
                        break;
                    case 'job':
                        const client = step.job_details?.cliente || 'Cliente';
                        instruction = `📦 Entrega para ${client}`;
                        break;
                    case 'end':
                        instruction = '🏁 Fin de ruta';
                        break;
                    default:
                        if (step.distance) {
                            if (step.distance > 1000) {
                                instruction = `→ Continúa ${(step.distance/1000).toFixed(1)} km`;
                            } else {
                                instruction = `→ Continúa ${step.distance} m`;
                            }
                        }
                }
                
                instructions.push({
                    instruction: instruction,
                    distance: step.distance || 0,
                    duration: step.duration || 0,
                    location: step.location || null,
                    stepIndex: index
                });
            });
            
            return instructions;
        }

        function stopNavigation() {
            console.log('🛑 Deteniendo navegación...');
            
            isNavigating = false;
            
            // Detener seguimiento GPS
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            
            // Detener intervalos
            if (navigationInterval) {
                clearInterval(navigationInterval);
                navigationInterval = null;
            }
            
            // Limpiar mapa
            clearMapElements();
            
            // Actualizar interfaz
            hideNavigationPanel();
            updateNavigationButton();
            updateStatusIndicator('online');
            hideSpeedIndicator();
            
            // Cancelar síntesis de voz
            if (speechSynthesis?.speaking) {
                speechSynthesis.cancel();
            }
            
            console.log('✅ Navegación detenida');
            showSuccessMessage('🛑 Navegación detenida');
        }

        async function getCurrentPosition() {
            return new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    reject,
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 5000
                    }
                );
            });
        }

        function startRealTimeTracking() {
            console.log('📡 Iniciando seguimiento GPS en tiempo real...');
            
            // Seguimiento continuo con alta precisión
            watchId = navigator.geolocation.watchPosition(
                updatePosition,
                handleLocationError,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 2000
                }
            );
            
            // Intervalo de respaldo
            navigationInterval = setInterval(updateNavigation, 2000);
            
            // Mostrar indicador de velocidad
            showSpeedIndicator();
        }

        function updatePosition(position) {
            if (!isNavigating) return;
            
            const newPosition = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
                speed: position.coords.speed || 0,
                heading: position.coords.heading || 0,
                timestamp: Date.now()
            };
            
            // Filtrar posiciones con baja precisión
            if (newPosition.accuracy > 100) {
                console.warn('⚠️ Posición con baja precisión:', newPosition.accuracy);
                return;
            }
            
            currentPosition = newPosition;
            
            // Actualizar elementos visuales
            updateVehicleMarker();
            updateRouteProgress();
            updateNavigationInfo();
            checkArrival();
            
            // Enviar ubicación al backend periódicamente
            sendLocationToBackend();
            
            console.log('📍 Posición actualizada:', currentPosition.lat.toFixed(6), currentPosition.lng.toFixed(6));
        }

        function handleLocationError(error) {
            console.warn('⚠️ Error GPS:', error.message);
            
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    showErrorMessage('Permiso de ubicación denegado');
                    break;
                case error.POSITION_UNAVAILABLE:
                    showWarningMessage('Ubicación no disponible, verifica tu GPS');
                    break;
                case error.TIMEOUT:
                    console.warn('Timeout GPS - continuando');
                    break;
            }
        }

        // ========== ACTUALIZACIÓN DE LA INTERFAZ ==========
        function updateNavigation() {
            if (!isNavigating || !currentPosition) return;
            
            updateNavigationInfo();
            updateSpeed();
            
            // Centrar mapa en vehículo
            if (vehicleMarker) {
                map.setView([currentPosition.lat, currentPosition.lng], map.getZoom(), {
                    animate: true,
                    duration: 0.5
                });
            }
        }

        function updateNavigationInfo() {
            if (currentDestinationIndex >= currentDestinations.length) {
                completeAllDeliveries();
                return;
            }
            
            const destination = currentDestinations[currentDestinationIndex];
            const distance = calculateDistance(currentPosition, destination);
            
            // Actualizar instrucción
            let instruction = 'Continúa hacia tu destino';
            if (distance < 0.1) {
                instruction = `🎯 Has llegado - ${destination.cliente}`;
            } else if (distance < 0.5) {
                instruction = `📦 Próxima entrega - ${destination.cliente}`;
            } else {
                instruction = `🚛 Dirigiéndote hacia ${destination.cliente}`;
            }
            
            document.getElementById('instruction-text').textContent = instruction;
            document.getElementById('instruction-distance').textContent = formatDistance(distance);
            
            // Actualizar progreso
            const totalDestinations = currentDestinations.length;
            const completedDestinations = currentDestinationIndex;
            const progress = (completedDestinations / totalDestinations) * 100;
            
            document.getElementById('progress-fill').style.width = progress + '%';
            document.getElementById('progress-text').textContent = `Progreso: ${Math.round(progress)}%`;
            
            // Actualizar ETA
            const averageSpeed = 30; // km/h estimado
            const remainingTime = (distance / averageSpeed) * 60; // minutos
            const eta = new Date(Date.now() + remainingTime * 60000);
            document.getElementById('eta').textContent = eta.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function updateSpeed() {
            if (!currentPosition) return;
            
            const speedKmh = Math.round((currentPosition.speed || 0) * 3.6);
            document.getElementById('current-speed').textContent = speedKmh;
            document.getElementById('current-speed-display').textContent = speedKmh;
        }

        function checkArrival() {
            if (currentDestinationIndex >= currentDestinations.length) return;
            
            const destination = currentDestinations[currentDestinationIndex];
            const distance = calculateDistance(currentPosition, destination);
            
            // Si estamos a menos de 50 metros
            if (distance < 0.05) {
                console.log('🎯 Llegada detectada:', destination.cliente);
                
                showArrivalNotification(destination.cliente);
                
                if (voiceEnabled) {
                    speak(`Has llegado a tu destino: ${destination.cliente}`);
                }
                
                // Marcar como entrega actual
                currentDeliveryId = destination.id;
                showCompleteButton();
                
                // Actualizar estado a "en progreso"
                updateDeliveryStatus(destination.id, 'in_progress');
            }
        }

        // ========== GESTIÓN DE ENTREGAS ==========
        function startDelivery() {
            if (currentDestinationIndex >= currentDestinations.length) return;
            
            const destination = currentDestinations[currentDestinationIndex];
            currentDeliveryId = destination.id;
            
            updateDeliveryStatus(destination.id, 'in_progress');
            showCompleteButton();
            
            showSuccessMessage(`🚛 Entrega iniciada para ${destination.cliente}`);
            console.log('🚛 Entrega iniciada:', destination.cliente);
        }

        function completeDelivery() {
            completeCurrentDelivery();
        }

        async function completeCurrentDelivery() {
            if (!currentDeliveryId) {
                showErrorMessage('No hay entrega activa para completar');
                return;
            }
            
            try {
                console.log('✅ Completando entrega:', currentDeliveryId);
                
                // Completar en el backend primero
                const backendSuccess = await completeDeliveryBackend(currentDeliveryId);
                
                if (!backendSuccess) {
                    const continueOffline = confirm('No se pudo conectar con el servidor. ¿Deseas marcar la entrega como completada localmente? Se sincronizará cuando se restaure la conexión.');
                    
                    if (!continueOffline) {
                        return;
                    }
                }
                
                // Actualizar estado localmente
                updateDeliveryStatus(currentDeliveryId, 'completed');
                
                // Avanzar al siguiente destino
                currentDestinationIndex++;
                currentDeliveryId = null;
                hideCompleteButton();
                
                if (currentDestinationIndex < currentDestinations.length) {
                    // Calcular ruta al siguiente destino
                    await calculateRouteWithGeometry();
                    updateNextDeliveryInfo();
                    
                    const nextDestination = currentDestinations[currentDestinationIndex];
                    showSuccessMessage(`🎉 Entrega completada! Siguiente: ${nextDestination.cliente}`);
                    
                    if (voiceEnabled) {
                        speak(`Entrega completada. Siguiente destino: ${nextDestination.cliente}`);
                    }
                } else {
                    // Todas las entregas completadas
                    completeAllDeliveries();
                }
                
                updateAssignmentCounts();
                
            } catch (error) {
                console.error('❌ Error completando entrega:', error);
                showErrorMessage('Error al completar entrega: ' + error.message);
            }
        }

        function completeAllDeliveries() {
            console.log('🎉 Todas las entregas completadas');
            
            showSuccessMessage('🎉 ¡Felicitaciones! Todas las entregas han sido completadas');
            
            if (voiceEnabled) {
                speak('¡Excelente trabajo! Has completado todas las entregas del día.');
            }
            
            // Actualizar interfaz
            document.getElementById('instruction-text').textContent = '🎉 ¡Todas las entregas completadas!';
            document.getElementById('instruction-distance').textContent = 'Misión cumplida';
            document.getElementById('progress-fill').style.width = '100%';
            document.getElementById('progress-text').textContent = 'Progreso: 100% - ¡Completado!';
            
            // Detener navegación después de unos segundos
            setTimeout(() => {
                stopNavigation();
            }, 5000);
        }

        function updateDeliveryStatus(deliveryId, newStatus) {
            // Actualizar en datos locales
            const assignment = currentAssignments.find(a => a.id === deliveryId);
            if (assignment) {
                assignment.status = newStatus;
            }
            
            const destination = currentDestinations.find(d => d.id === deliveryId);
            if (destination) {
                destination.status = newStatus;
            }
            
            // Actualizar en UI
            refreshAssignmentsList();
            
            console.log('📊 Estado actualizado:', deliveryId, '->', newStatus);
        }

        // ========== ELEMENTOS DEL MAPA ==========
       function drawRoute() {
    console.log('🎨 DEBUG - Iniciando drawRoute() con datos actuales:', {
        routeCoordinatesCount: routeCoordinates.length,
        decodedRouteCoordinatesCount: decodedRouteCoordinates.length,
        currentDestinationsCount: currentDestinations.length,
        hasRouteGeometry: !!routeGeometry,
        routeGeometryLength: routeGeometry?.length || 0
    });
    
    clearMapElements();
    
    if (routeCoordinates.length === 0) {
        console.warn('⚠️ DEBUG - No hay coordenadas de ruta para dibujar');
        console.log('🔍 DEBUG - Estado actual de coordenadas:', {
            routeCoordinates: routeCoordinates,
            decodedRouteCoordinates: decodedRouteCoordinates,
            routeGeometry: routeGeometry
        });
        return;
    }
    
    console.log('🗺️ DEBUG - Dibujando ruta en el mapa:', {
        pointsCount: routeCoordinates.length,
        firstPoint: routeCoordinates[0],
        lastPoint: routeCoordinates[routeCoordinates.length - 1],
        samplePoints: routeCoordinates.slice(0, 5),
        allCoordinates: routeCoordinates
    });
    
    // Línea de ruta completa (gris/azul)
    try {
        remainingRouteLine = L.polyline(routeCoordinates, {
            color: '#94a3b8',
            weight: 6,
            opacity: 0.7,
            className: 'remaining-route'
        }).addTo(map);
        
        console.log('✅ DEBUG - Polyline principal agregada al mapa exitosamente');
    } catch (polylineError) {
        console.error('❌ DEBUG - Error creando polyline principal:', {
            error: polylineError.message,
            routeCoordinates: routeCoordinates,
            stack: polylineError.stack
        });
        return;
    }
    
    // Línea de progreso completado (verde)
    completedRouteLine = L.polyline([], {
        color: '#10b981',
        weight: 6,
        opacity: 0.9,
        className: 'completed-route'
    }).addTo(map);
    
    // Marcador del vehículo
    createVehicleMarker();
    
    // Marcadores de destinos
    createDestinationMarkers();
    
    // Ajustar vista del mapa
    try {
        const bounds = L.latLngBounds(routeCoordinates);
        if (currentPosition) {
            bounds.extend([currentPosition.lat, currentPosition.lng]);
        }
        map.fitBounds(bounds, { padding: [50, 50] });
        
        console.log('✅ DEBUG - Vista del mapa ajustada:', {
            bounds: bounds.toBBoxString(),
            center: bounds.getCenter(),
            includesCurrentPosition: !!currentPosition
        });
    } catch (boundsError) {
        console.error('❌ DEBUG - Error ajustando bounds del mapa:', boundsError);
    }
    
    console.log('✅ DEBUG - Ruta dibujada en el mapa exitosamente');
}

        function createVehicleMarker() {
            if (vehicleMarker) {
                map.removeLayer(vehicleMarker);
            }
            
            if (!currentPosition) return;
            
            vehicleMarker = L.marker([currentPosition.lat, currentPosition.lng], {
                icon: L.divIcon({
                    className: 'vehicle-marker',
                    html: '🚛',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                }),
                zIndexOffset: 1000
            }).addTo(map);
            
            // Tooltip
            vehicleMarker.bindTooltip(`
                <div style="text-align: center;">
                    <strong>🚛 ${demoData.motoristName}</strong><br>
                    <span style="font-size: 14px;">${Math.round((currentPosition.speed || 0) * 3.6)} km/h</span><br>
                    <span style="font-size: 12px;">Precisión: ${Math.round(currentPosition.accuracy || 0)}m</span>
                </div>
            `, {
                permanent: true,
                direction: 'top',
                offset: [0, -50]
            });
        }

        function updateVehicleMarker() {
            if (!vehicleMarker || !currentPosition) return;
            
            vehicleMarker.setLatLng([currentPosition.lat, currentPosition.lng]);
            
            // Actualizar tooltip
            vehicleMarker.setTooltipContent(`
                <div style="text-align: center;">
                    <strong>🚛 ${demoData.motoristName}</strong><br>
                    <span style="font-size: 14px;">${Math.round((currentPosition.speed || 0) * 3.6)} km/h</span><br>
                    <span style="font-size: 12px;">Precisión: ${Math.round(currentPosition.accuracy || 0)}m</span>
                </div>
            `);
        }

        function createDestinationMarkers() {
            // Limpiar marcadores existentes
            destinationMarkers.forEach(marker => map.removeLayer(marker));
            destinationMarkers = [];
            
            currentDestinations.forEach((destination, index) => {
                let markerClass = 'destination-marker';
                let icon = '📦';
                
                if (destination.status === 'completed') {
                    markerClass += ' completed-marker';
                    icon = '✅';
                } else if (destination.status === 'in_progress') {
                    icon = '🚛';
                }
                
                const marker = L.marker([destination.lat, destination.lng], {
                    icon: L.divIcon({
                        className: markerClass,
                        html: icon,
                        iconSize: [35, 35],
                        iconAnchor: [17, 17]
                    })
                }).addTo(map);
                
                marker.bindPopup(`
                    <div style="min-width: 200px;">
                        <h4>📦 ${destination.cliente}</h4>
                        <p><strong>📍</strong> ${destination.direccion}</p>
                        <p><strong>📞</strong> ${destination.telefono}</p>
                        <p><strong>Estado:</strong> ${getStatusText(destination.status)}</p>
                        ${destination.status === 'pending' ? 
                            `<button onclick="startDelivery()" style="background: #f59e0b; color: white; border: none; padding: 8px 12px; border-radius: 6px; margin-right: 5px;">🚛 Iniciar</button>
                             <button onclick="completeDelivery()" style="background: #10b981; color: white; border: none; padding: 8px 12px; border-radius: 6px;">✅ Completar</button>` : ''
                        }
                    </div>
                `);
                
                destinationMarkers.push(marker);
            });
        }

        function updateRouteProgress() {
            if (!completedRouteLine || !currentPosition || routeCoordinates.length === 0) return;
            
            // Encontrar punto más cercano en la ruta
            let closestIndex = 0;
            let minDistance = Infinity;
            
            routeCoordinates.forEach((coord, index) => {
                const distance = calculateDistance(currentPosition, {
                    lat: coord.lat,
                    lng: coord.lng
                });
                
                if (distance < minDistance) {
                    minDistance = distance;
                    closestIndex = index;
                }
            });
            
            // Actualizar línea de progreso
            if (closestIndex > 0) {
                const completedCoords = routeCoordinates.slice(0, closestIndex + 1);
                completedCoords.push(L.latLng(currentPosition.lat, currentPosition.lng));
                completedRouteLine.setLatLngs(completedCoords);
                
                const remainingCoords = [
                    L.latLng(currentPosition.lat, currentPosition.lng),
                    ...routeCoordinates.slice(closestIndex + 1)
                ];
                remainingRouteLine.setLatLngs(remainingCoords);
            }
        }

        function clearMapElements() {
            if (vehicleMarker) {
                map.removeLayer(vehicleMarker);
                vehicleMarker = null;
            }
            
            if (routeLine) {
                map.removeLayer(routeLine);
                routeLine = null;
            }
            
            if (completedRouteLine) {
                map.removeLayer(completedRouteLine);
                completedRouteLine = null;
            }
            
            if (remainingRouteLine) {
                map.removeLayer(remainingRouteLine);
                remainingRouteLine = null;
            }
            
            destinationMarkers.forEach(marker => map.removeLayer(marker));
            destinationMarkers = [];
            
            currentMarkers.forEach(marker => map.removeLayer(marker));
            currentMarkers = [];
        }

        // ========== GESTIÓN DE PANELES ==========
        function showNavigationPanel() {
            document.getElementById('navigation-panel').classList.add('active');
        }

        function hideNavigationPanel() {
            document.getElementById('navigation-panel').classList.remove('active');
        }

        function toggleAssignments() {
            const panel = document.getElementById('assignments-panel');
            
            if (isAssignmentsPanelOpen) {
                panel.classList.remove('active');
                isAssignmentsPanelOpen = false;
            } else {
                panel.classList.add('active');
                isAssignmentsPanelOpen = true;
                refreshAssignmentsList();
            }
        }

        function refreshAssignmentsList() {
            const container = document.getElementById('assignments-content');
            
            let html = '';
            currentAssignments.forEach(assignment => {
                const statusClass = assignment.status.replace('_', '-');
                const statusText = getStatusText(assignment.status);
                let statusIcon = '📦';
                
                if (assignment.status === 'completed') statusIcon = '✅';
                else if (assignment.status === 'in_progress') statusIcon = '🚛';
                else if (assignment.status === 'cancelled') statusIcon = '❌';
                
                html += `
                    <div class="assignment-item ${statusClass}">
                        <div class="assignment-client">${statusIcon} ${assignment.cliente}</div>
                        <div class="assignment-details">
                            📍 ${assignment.direccion}<br>
                            📞 ${assignment.telefono}
                        </div>
                        <div class="assignment-status">${statusText}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        async function refreshAssignments() {
            console.log('🔄 Actualizando asignaciones...');
            
            try {
                const success = await loadAssignmentsFromBackend();
                if (success) {
                    showSuccessMessage('🔄 Asignaciones actualizadas desde el servidor');
                } else {
                    showSuccessMessage('🔄 Asignaciones actualizadas localmente');
                }
                refreshAssignmentsList();
                updateAssignmentCounts();
                updateNextDeliveryInfo();
            } catch (error) {
                console.error('Error actualizando asignaciones:', error);
                showErrorMessage('Error actualizando asignaciones');
            }
        }

        function updateAssignmentCounts() {
            const pending = currentAssignments.filter(a => a.status === 'pending').length;
            const completed = currentAssignments.filter(a => a.status === 'completed').length;
            
            document.getElementById('pending-count').textContent = pending;
            document.getElementById('completed-count').textContent = completed;
        }

        function updateNextDeliveryInfo() {
            if (currentDestinationIndex >= currentDestinations.length) {
                document.getElementById('next-client').textContent = 'Todas completadas';
                document.getElementById('next-info').textContent = '🎉 ¡Excelente trabajo!';
                return;
            }
            
            const next = currentDestinations[currentDestinationIndex];
            document.getElementById('next-client').textContent = next.cliente;
            document.getElementById('next-info').textContent = `📍 ${next.direccion} • 📞 ${next.telefono}`;
        }

        // ========== CONTROLES DE BOTONES ==========
        function updateNavigationButton() {
            const btn = document.querySelector('.btn-navigate');
            const text = document.getElementById('nav-btn-text');
            
            if (isNavigating) {
                btn.classList.add('active');
                text.textContent = 'Detener Navegación';
            } else {
                btn.classList.remove('active');
                text.textContent = 'Iniciar Navegación GPS';
            }
        }

        function showCompleteButton() {
            document.getElementById('complete-delivery-btn').style.display = 'flex';
            document.querySelector('.btn-complete-delivery').style.display = 'inline-block';
        }

        function hideCompleteButton() {
            document.getElementById('complete-delivery-btn').style.display = 'none';
            document.querySelector('.btn-complete-delivery').style.display = 'none';
        }

        function updateStatusIndicator(status) {
            const indicator = document.querySelector('.status-indicator');
            
            indicator.className = 'status-indicator';
            
            if (status === 'navigating') {
                indicator.classList.add('status-navigating');
                indicator.innerHTML = '🧭 Navegando';
            } else {
                indicator.classList.add('status-online');
                indicator.innerHTML = '🟢 En línea';
            }
        }

        // ========== CONTROLES DEL MAPA ==========
        function locateUser() {
            if (!navigator.geolocation) {
                showErrorMessage('Geolocalización no disponible');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    map.setView([lat, lng], 16);
                    
                    const marker = L.marker([lat, lng], {
                        icon: L.divIcon({
                            className: 'user-location-marker',
                            html: '📍',
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        })
                    }).addTo(map);
                    
                    marker.bindPopup('📍 Tu ubicación actual').openPopup();
                    
                    // Remover después de 5 segundos
                    setTimeout(() => map.removeLayer(marker), 5000);
                },
                function(error) {
                    showErrorMessage('Error obteniendo ubicación: ' + error.message);
                }
            );
        }

        function fitMapToRoute() {
            if (routeCoordinates.length === 0) {
                showWarningMessage('No hay ruta activa para ajustar');
                return;
            }
            
            const bounds = L.latLngBounds(routeCoordinates);
            if (currentPosition) {
                bounds.extend([currentPosition.lat, currentPosition.lng]);
            }
            
            map.fitBounds(bounds, { padding: [50, 50] });
            showSuccessMessage('Vista ajustada a la ruta');
        }

        function toggleMapView() {
            if (isSatelliteView) {
                map.removeLayer(satelliteLayer);
                tileLayer.addTo(map);
                isSatelliteView = false;
                showSuccessMessage('Cambiado a vista de mapa');
            } else {
                map.removeLayer(tileLayer);
                satelliteLayer.addTo(map);
                isSatelliteView = true;
                showSuccessMessage('Cambiado a vista satelital');
            }
        }

        function centerOnVehicle() {
            if (!currentPosition) {
                showWarningMessage('No hay ubicación del vehículo disponible');
                return;
            }
            
            map.setView([currentPosition.lat, currentPosition.lng], 16, {
                animate: true,
                duration: 1
            });
            
            showSuccessMessage('Centrado en vehículo');
        }

        async function recalculateRoute() {
            if (!isNavigating || !currentPosition) {
                showWarningMessage('No hay navegación activa');
                return;
            }
            
            try {
                showLoading('Recalculando ruta', 'Obteniendo nueva ruta desde tu ubicación...');
                
                // Forzar recálculo desde el backend
                const newRoute = await requestNewRouteFromBackend();
                
                if (newRoute) {
                    await processVroomRoute(newRoute);
                    routeCoordinates = [...decodedRouteCoordinates];
                    currentInstructions = generateInstructionsFromSteps();
                    drawRoute();
                    
                    hideLoading();
                    showSuccessMessage('✅ Ruta recalculada con nueva geometría');
                    
                    if (voiceEnabled) {
                        speak('Ruta recalculada exitosamente');
                    }
                } else {
                    throw new Error('No se pudo obtener nueva ruta del servidor');
                }
                
            } catch (error) {
                hideLoading();
                console.error('❌ Error recalculando ruta:', error);
                showErrorMessage('Error recalculando ruta: ' + error.message);
            }
        }

        // ========== SÍNTESIS DE VOZ ==========
        function speak(text) {
            if (!voiceEnabled || !speechSynthesis) return;
            
            speechSynthesis.cancel();
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'es-ES';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            utterance.volume = 0.8;
            
            speechSynthesis.speak(utterance);
            console.log('🔊 Anuncio:', text);
        }

        function toggleVoice() {
            voiceEnabled = !voiceEnabled;
            const btn = document.getElementById('voice-btn');
            
            if (voiceEnabled) {
                btn.textContent = '🔊 Voz';
                btn.style.opacity = '1';
                speak('Instrucciones de voz activadas');
            } else {
                btn.textContent = '🔇 Voz';
                btn.style.opacity = '0.6';
            }
            
            showSuccessMessage(voiceEnabled ? '🔊 Voz activada' : '🔇 Voz desactivada');
        }

        // ========== INDICADORES VISUALES ==========
        function showSpeedIndicator() {
            document.getElementById('speed-indicator').style.display = 'block';
        }

        function hideSpeedIndicator() {
            document.getElementById('speed-indicator').style.display = 'none';
        }

        function showArrivalNotification(clientName) {
            const notification = document.getElementById('arrival-notification');
            document.getElementById('arrival-client').textContent = clientName;
            
            notification.style.display = 'block';
            notification.style.animation = 'bounceIn 0.6s ease-out';
            
            // Ocultar después de 4 segundos
            setTimeout(() => {
                notification.style.display = 'none';
            }, 4000);
        }

        // ========== MENSAJES DEL SISTEMA ==========
        function showSuccessMessage(message) {
            showMessage(message, 'success');
        }

        function showErrorMessage(message) {
            showMessage(message, 'error');
        }

        function showWarningMessage(message) {
            showMessage(message, 'warning');
        }

        function showMessage(message, type) {
            const element = document.getElementById(`${type}-message`);
            element.textContent = message;
            element.style.display = 'block';
            
            setTimeout(() => {
                element.style.display = 'none';
            }, 4000);
            
            console.log(`${type.toUpperCase()}:`, message);
        }

        function showLoading(title, subtitle) {
            const overlay = document.getElementById('loading-overlay');
            overlay.querySelector('.loading-text').textContent = title;
            overlay.querySelector('.loading-subtitle').textContent = subtitle;
            overlay.style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        // ========== FUNCIONES AUXILIARES ==========
        function calculateDistance(pos1, pos2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = deg2rad(pos2.lat - pos1.lat);
            const dLng = deg2rad(pos2.lng - pos1.lng);
            
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(deg2rad(pos1.lat)) * Math.cos(deg2rad(pos2.lat)) *
                     Math.sin(dLng/2) * Math.sin(dLng/2);
            
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function deg2rad(deg) {
            return deg * (Math.PI/180);
        }

        function formatDistance(km) {
            if (km < 1) {
                return Math.round(km * 1000) + ' m';
            } else {
                return km.toFixed(1) + ' km';
            }
        }

        function getStatusText(status) {
            const statusMap = {
                'pending': 'Pendiente',
                'in_progress': 'En Progreso',
                'completed': 'Completada',
                'cancelled': 'Cancelada'
            };
            return statusMap[status] || 'Desconocido';
        }

        // ========== INTEGRACIÓN CON BACKEND ==========
        async function completeDeliveryBackend(deliveryId) {
            try {
                console.log('✅ Completando entrega en backend:', deliveryId);
                
                const response = await fetch('/api/mark-job-completed', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': backendData.csrf_token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        job_id: deliveryId
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ Entrega completada en backend exitosamente');
                    return true;
                } else {
                    throw new Error(data.error || 'Error desconocido del servidor');
                }
            } catch (error) {
                console.error('❌ Error completando entrega en backend:', error);
                return false;
            }
        }

        // Enviar ubicación actual al backend
        let lastLocationSent = 0;
        const LOCATION_SEND_INTERVAL = 10000; // 10 segundos

        async function sendLocationToBackend() {
            if (!currentPosition) return;
            
            const now = Date.now();
            if (now - lastLocationSent < LOCATION_SEND_INTERVAL) {
                return;
            }
            
            try {
                await fetch('/api/capture-vehicle-locations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': backendData.csrf_token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        locations: [{
                            vehicle_id: demoData.motoristId,
                            lat: currentPosition.lat,
                            lng: currentPosition.lng,
                            accuracy: currentPosition.accuracy
                        }]
                    })
                });
                
                lastLocationSent = now;
                console.log('📍 Ubicación enviada al backend');
                
            } catch (error) {
                console.warn('⚠️ Error enviando ubicación al backend:', error);
            }
        }

        // ========== FUNCIONES DE DEBUG ==========
        function logGeometryStatus() {
    console.log('🔍 ===== DEBUG COMPLETO DE GEOMETRÍA =====');
    
    // Estado de datos del backend
    console.log('📊 1. DATOS DEL BACKEND:', {
        backendDataExists: !!backendData,
        routesCount: backendData?.routes?.length || 0,
        currentMotorista: demoData.motoristId,
        userRouteInBackend: backendData?.routes?.find(r => r.vehicle === demoData.motoristId),
        allVehicleIds: backendData?.routes?.map(r => r.vehicle) || []
    });
    
    // Estado de geometría cruda
    console.log('🗺️ 2. GEOMETRÍA CRUDA:', {
        hasRouteGeometry: !!routeGeometry,
        routeGeometryType: typeof routeGeometry,
        routeGeometryLength: routeGeometry?.length || 0,
        routeGeometryPreview: routeGeometry?.substring(0, 100) || 'N/A',
        routeGeometryFull: routeGeometry
    });
    
    // Estado de geometría decodificada
    console.log('📍 3. GEOMETRÍA DECODIFICADA:', {
        hasDecodedCoordinates: decodedRouteCoordinates.length > 0,
        decodedCoordinatesCount: decodedRouteCoordinates.length,
        firstDecodedPoint: decodedRouteCoordinates[0],
        lastDecodedPoint: decodedRouteCoordinates[decodedRouteCoordinates.length - 1],
        allDecodedCoordinates: decodedRouteCoordinates
    });
    
    // Estado de coordenadas de ruta actual
    console.log('🛣️ 4. COORDENADAS DE RUTA ACTUAL:', {
        hasRouteCoordinates: routeCoordinates.length > 0,
        routeCoordinatesCount: routeCoordinates.length,
        firstRoutePoint: routeCoordinates[0],
        lastRoutePoint: routeCoordinates[routeCoordinates.length - 1],
        allRouteCoordinates: routeCoordinates
    });
    
    // Estado de pasos de ruta
    console.log('👣 5. PASOS DE RUTA:', {
        hasSteps: routeSteps.length > 0,
        stepsCount: routeSteps.length,
        steps: routeSteps,
        totalDistance: totalRouteDistance,
        totalDuration: totalRouteDuration
    });
    
    // Estado de destinos y asignaciones
    console.log('🎯 6. DESTINOS Y ASIGNACIONES:', {
        destinationsCount: currentDestinations.length,
        assignmentsCount: currentAssignments.length,
        currentDestinationIndex: currentDestinationIndex,
        destinations: currentDestinations,
        assignments: currentAssignments
    });
    
    // Estado del mapa
    console.log('🗺️ 7. ESTADO DEL MAPA:', {
        mapExists: !!map,
        hasRemainingRouteLine: !!remainingRouteLine,
        hasCompletedRouteLine: !!completedRouteLine,
        hasVehicleMarker: !!vehicleMarker,
        destinationMarkersCount: destinationMarkers.length
    });
    
    console.log('🔍 ===== FIN DEBUG DE GEOMETRÍA =====');
}

        function debugInfo() {
            console.log('🔍 Estado completo del sistema:', {
                isNavigating,
                currentPosition,
                currentDestinationIndex,
                currentDeliveryId,
                assignmentsCount: currentAssignments.length,
                destinationsCount: currentDestinations.length,
                routeCoordinatesCount: routeCoordinates.length,
                voiceEnabled,
                isAssignmentsPanelOpen,
                backendData: {
                    motoristId: demoData.motoristId,
                    routesAvailable: backendData.routes.length,
                    vehiclesAvailable: backendData.vehicles.length,
                    jobsAvailable: backendData.jobs.length
                }
            });
        }

        function simulateArrival() {
            if (currentDestinationIndex < currentDestinations.length) {
                const destination = currentDestinations[currentDestinationIndex];
                showArrivalNotification(destination.cliente);
                currentDeliveryId = destination.id;
                showCompleteButton();
                updateDeliveryStatus(destination.id, 'in_progress');
                console.log('🧪 Simulando llegada a:', destination.cliente);
            }
        }

        // ========== EVENTOS DEL TECLADO ==========
        document.addEventListener('keydown', function(event) {
            switch(event.key) {
                case ' ': // Barra espaciadora - Toggle navegación
                    event.preventDefault();
                    toggleNavigation();
                    break;
                case 'c': // C - Completar entrega
                    if (currentDeliveryId && isNavigating) {
                        completeCurrentDelivery();
                    }
                    break;
                case 'a': // A - Toggle asignaciones
                    toggleAssignments();
                    break;
                case 'v': // V - Toggle voz
                    toggleVoice();
                    break;
                case 'r': // R - Recalcular ruta
                    if (isNavigating) {
                        recalculateRoute();
                    }
                    break;
                case 'l': // L - Localizar usuario
                    locateUser();
                    break;
                case 'f': // F - Ajustar vista
                    fitMapToRoute();
                    break;
                case 'm': // M - Toggle mapa/satélite
                    toggleMapView();
                    break;
                case 'Escape': // ESC - Cerrar paneles
                    if (isAssignmentsPanelOpen) {
                        toggleAssignments();
                    }
                    break;
            }
        });

        // ========== NOTIFICACIONES (OPCIONAL) ==========
        function requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        console.log('✅ Permisos de notificación concedidos');
                    }
                });
            }
        }

        function showSystemNotification(title, message, icon = '🚛') {
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: message,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: 'delivery-notification'
                });
                
                notification.onclick = function() {
                    window.focus();
                    notification.close();
                };
                
                setTimeout(() => notification.close(), 5000);
            }
        }

        // ========== MODO OFFLINE ==========
        function handleConnectionChange() {
            if (navigator.onLine) {
                showSuccessMessage('🌐 Conexión restaurada');
                syncPendingData();
            } else {
                showWarningMessage('📡 Sin conexión - Modo offline activado');
            }
        }

        async function syncPendingData() {
            console.log('🔄 Sincronizando datos pendientes...');
            try {
                await loadAssignmentsFromBackend();
                showSuccessMessage('🔄 Datos sincronizados con el servidor');
            } catch (error) {
                console.warn('⚠️ Error sincronizando datos:', error);
            }
        }

        // Escuchar cambios de conexión
        window.addEventListener('online', handleConnectionChange);
        window.addEventListener('offline', handleConnectionChange);

        // Solicitar permisos de notificación
        setTimeout(requestNotificationPermission, 2000);

        console.log('✅ Sistema de Navegación GPS con geometría VROOM cargado completamente');
        console.log('⌨️ Atajos: Espacio=Nav, C=Completar, A=Asignaciones, V=Voz, R=Recalcular, L=Ubicar, F=Ajustar, M=Mapa');
        console.log('🔧 Debug: window.debugGeometry(), window.debugInfo(), window.simulateArrival()');
    </script>
</body>
</html>