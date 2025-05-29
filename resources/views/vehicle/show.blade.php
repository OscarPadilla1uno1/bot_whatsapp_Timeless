<!DOCTYPE html>
<html lang="es">
<head>
    <title>Navegación GPS en Tiempo Real - Sistema de Entregas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="LzLCVJiNh7GWRwexmSawOsahUNHpn9OXATscc6Z8">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Leaflet MarkerCluster -->
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

    <!-- Panel Principal de Control -->
    <div class="main-control-panel">
        <div class="panel-header">
            <div class="motorist-info">
                <div class="motorist-avatar">M2</div>
                <div class="motorist-details">
                    <h3>Motorista 2</h3>
                    <p>ID: 8 - <span class="status-indicator status-online">🟢 En línea</span></p>
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
                    <div style="font-size: 20px; font-weight: bold; color: #3b82f6;" id="pending-count">2</div>
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
                    📦 <span id="next-client">Carlos Martínez</span>
                </div>
                <div class="delivery-info" id="next-info">
                    📍 Colonia Palmira • 📞 +504 9999-9999
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
            <div class="assignment-item">
                <div class="assignment-client">📦 Carlos Martínez</div>
                <div class="assignment-details">
                    📍 Colonia Palmira, Tegucigalpa<br>
                    📞 +504 9999-9999
                </div>
                <div class="assignment-status">Pendiente</div>
            </div>

            <div class="assignment-item">
                <div class="assignment-client">📦 Juan Pérez</div>
                <div class="assignment-details">
                    📍 Barrio El Centro, Tegucigalpa<br>
                    📞 +504 8888-8888
                </div>
                <div class="assignment-status">Pendiente</div>
            </div>
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
        <div class="arrival-client" id="arrival-client">Carlos Martínez</div>
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

        // Datos que se cargarán desde el backend
        const demoData = {
            motoristId: 8,
            motoristName: "Motorista 2",
            assignments: [],
            vehicleStartPosition: { lat: 14.0667, lng: -87.1875 },
            currentRoute: null // Ruta completa desde VROOM
        };

        // Variables para manejo de geometría de rutas reales
        let routeGeometry = null;
        let decodedRouteCoordinates = [];
        let routeSteps = [];
        let totalRouteDistance = 0;
        let totalRouteDuration = 0;

        // ========== INICIALIZACIÓN ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Inicializando aplicación...');
            initializeApp();
        });

        async function initializeApp() {
            try {
                // Inicializar mapa
                initMap();
                
                // Verificar geolocalización
                checkGeolocationSupport();
                
                // Cargar datos iniciales
                await loadInitialData();
                
                // Configurar eventos
                setupEventListeners();
                
                // Ocultar loading
                hideLoading();
                
                console.log('✅ Aplicación inicializada correctamente');
                showSuccessMessage('🎯 Sistema GPS listo. ¡Inicia tu navegación cuando estés preparado!');
                
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

        async function loadInitialData() {
            console.log('📊 Cargando datos iniciales desde el backend...');
            
            try {
                // Cargar las rutas reales desde el backend (tu sistema VROOM existente)
                const success = await loadRouteFromBackend();
                
                if (!success) {
                    console.log('⚠️ No se pudieron cargar rutas del backend, usando datos de ejemplo');
                    // Datos de ejemplo como fallback
                    await loadFallbackData();
                }
                
                // Actualizar UI
                updateAssignmentCounts();
                updateNextDeliveryInfo();
                
                console.log('✅ Datos cargados:', currentAssignments.length, 'asignaciones');
                
            } catch (error) {
                console.error('❌ Error cargando datos:', error);
                await loadFallbackData();
            }
        }

        // Cargar ruta completa desde tu backend existente con geometría incluida
        async function loadRouteFromBackend() {
            try {
                console.log('🔄 Cargando ruta con geometría desde VROOM...');
                
                // Usar los datos que ya están disponibles en la página
                // Tu sistema ya tiene las rutas calculadas con geometría
                const routes = window.allRoutes || [];
                const userRoute = routes.find(route => route.vehicle === demoData.motoristId);
                
                if (userRoute && userRoute.geometry) {
                    console.log('✅ Ruta con geometría encontrada en datos existentes');
                    await processVroomRoute(userRoute);
                    return true;
                }
                
                // Si no están en memoria, intentar obtener via AJAX
                const response = await fetch(window.location.href, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.routes) {
                        const userRoute = data.routes.find(route => route.vehicle === demoData.motoristId);
                        
                        if (userRoute && userRoute.geometry) {
                            console.log('✅ Ruta con geometría obtenida del servidor');
                            await processVroomRoute(userRoute);
                            return true;
                        }
                    }
                }
                
                console.warn('⚠️ No se encontró ruta con geometría para el vehículo', demoData.motoristId);
                return false;
                
            } catch (error) {
                console.error('❌ Error cargando ruta desde backend:', error);
                return false;
            }
        }

        // Procesar la ruta directamente de VROOM (ya incluye geometría)
        async function processVroomRoute(route) {
            console.log('🛣️ Procesando ruta VROOM con geometría incluida...');
            
            demoData.currentRoute = route;
            routeGeometry = route.geometry; // Geometría ya viene en la respuesta
            routeSteps = route.steps || [];
            totalRouteDistance = route.distance || 0;
            totalRouteDuration = route.duration || 0;
            
            console.log('📊 Datos de ruta VROOM:', {
                hasGeometry: !!routeGeometry,
                geometryLength: routeGeometry?.length || 0,
                stepsCount: routeSteps.length,
                distance: `${(totalRouteDistance/1000).toFixed(1)} km`,
                duration: `${Math.round(totalRouteDuration/60)} min`
            });
            
            // Decodificar la geometría que ya viene en la respuesta de VROOM
            if (routeGeometry) {
                decodedRouteCoordinates = await decodeRouteGeometry(routeGeometry);
                console.log('📍 Coordenadas decodificadas de VROOM:', decodedRouteCoordinates.length, 'puntos');
            } else {
                console.warn('⚠️ No se encontró geometría en la ruta de VROOM');
                decodedRouteCoordinates = [];
            }
            
            // Procesar pasos de la ruta para obtener destinos
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
            
            console.log('✅ Ruta VROOM procesada:', {
                destinations: currentDestinations.length,
                assignments: currentAssignments.length,
                geometryPoints: decodedRouteCoordinates.length,
                hasRealRoute: decodedRouteCoordinates.length > 2
            });
        }

        // Decodificar la geometría de polyline de VROOM
        async function decodeRouteGeometry(geometry) {
            try {
                // Si tienes la librería polyline disponible
                if (typeof polyline !== 'undefined' && polyline.decode) {
                    const decoded = polyline.decode(geometry);
                    return decoded.map(point => L.latLng(point[0], point[1]));
                }
                
                // Fallback: decodificación manual básica de polyline
                return decodePolylineManual(geometry);
                
            } catch (error) {
                console.error('❌ Error decodificando geometría:', error);
                
                // Último recurso: usar los puntos de los pasos
                const coordinates = [];
                routeSteps.forEach(step => {
                    if (step.location) {
                        coordinates.push(L.latLng(step.location[1], step.location[0]));
                    }
                });
                
                return coordinates;
            }
        }

        // Decodificación manual básica de polyline (simplificada)
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

        // Datos de fallback si no se puede cargar del backend
        async function loadFallbackData() {
            console.log('📝 Cargando datos de ejemplo...');
            
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
            
            // Crear geometría simple para demo
            decodedRouteCoordinates = [
                L.latLng(14.0667, -87.1875), // Inicio
                L.latLng(14.08, -87.185),     // Punto intermedio
                L.latLng(14.094832223270211, -87.18619506686059), // Carlos
                L.latLng(14.09, -87.18),      // Punto intermedio
                L.latLng(14.0849932, -87.1797737) // Juan
            ];
        }

        function setupEventListeners() {
            // Eventos del mapa
            map.on('click', function(e) {
                console.log("🎯 Coordenadas:", e.latlng.lat, e.latlng.lng);
            });
        }

        // ========== NAVEGACIÓN GPS ==========
        async function toggleNavigation() {
            if (isNavigating) {
                stopNavigation();
            } else {
                await startNavigation();
            }
        }

        async function startNavigation() {
            console.log('🧭 Iniciando navegación GPS...');
            
            if (!navigator.geolocation) {
                showErrorMessage('Tu dispositivo no soporta geolocalización');
                return;
            }
            
            try {
                showLoading('Iniciando Navegación GPS', 'Obteniendo tu ubicación...');
                
                // Obtener ubicación actual
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
                
                // Calcular ruta hacia primer destino
                await calculateRoute();
                
                // Iniciar seguimiento en tiempo real
                startRealTimeTracking();
                
                // Actualizar interfaz
                isNavigating = true;
                showNavigationPanel();
                updateNavigationButton();
                updateStatusIndicator('navigating');
                
                // Anuncio de voz
                if (voiceEnabled) {
                    speak('Navegación GPS iniciada. Dirigiéndote hacia ' + currentDestinations[0].cliente);
                }
                
                hideLoading();
                showSuccessMessage('🧭 Navegación GPS iniciada exitosamente');
                
                console.log('✅ Navegación iniciada');
                
            } catch (error) {
                console.error('❌ Error al iniciar navegación:', error);
                hideLoading();
                showErrorMessage('Error al iniciar navegación: ' + error.message);
            }
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

        async function calculateRoute() {
            if (currentDestinations.length === 0) {
                throw new Error('No hay destinos disponibles');
            }
            
            const destination = currentDestinations[currentDestinationIndex];
            console.log('🗺️ Usando ruta real hacia:', destination.cliente);
            
            try {
                // Si ya tenemos la ruta completa de VROOM, usarla
                if (decodedRouteCoordinates.length > 0) {
                    console.log('✅ Usando ruta real de VROOM con', decodedRouteCoordinates.length, 'puntos');
                    routeCoordinates = decodedRouteCoordinates;
                    
                    // Calcular instrucciones basadas en los pasos reales
                    currentInstructions = generateInstructionsFromSteps();
                    
                    // Dibujar ruta real en el mapa
                    drawRoute();
                    
                    return;
                }
                
                // Si no tenemos ruta, intentar obtenerla del backend
                console.log('🔄 Solicitando nueva ruta al backend...');
                const backendRoute = await getRouteFromBackend();
                
                if (backendRoute && backendRoute.geometry) {
                    await processVroomRoute(backendRoute);
                    routeCoordinates = decodedRouteCoordinates;
                    currentInstructions = generateInstructionsFromSteps();
                    drawRoute();
                    return;
                }
                
                // Último recurso: usar cálculo simple
                console.warn('⚠️ Usando ruta simple como último recurso');
                await calculateSimpleRoute(destination);
                
            } catch (error) {
                console.error('❌ Error calculando ruta:', error);
                await calculateSimpleRoute(destination);
            }
        }

        // Generar instrucciones de navegación desde los pasos de VROOM
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

        // Ruta simple como fallback
        async function calculateSimpleRoute(destination) {
            console.log('📏 Calculando ruta simple hacia:', destination.cliente);
            
            routeCoordinates = [
                L.latLng(currentPosition.lat, currentPosition.lng),
                L.latLng(destination.lat, destination.lng)
            ];
            
            currentInstructions = [{
                instruction: `Dirígete hacia ${destination.cliente}`,
                distance: calculateDistance(currentPosition, destination) * 1000
            }];
            
            drawRoute();
        }

        async function calculateRealRoute(startLat, startLng, endLat, endLng) {
            // Simular llamada a servicio de routing (OSRM)
            return new Promise((resolve) => {
                setTimeout(() => {
                    const distance = calculateDistance(
                        {lat: startLat, lng: startLng},
                        {lat: endLat, lng: endLng}
                    );
                    
                    resolve({
                        coordinates: [
                            L.latLng(startLat, startLng),
                            L.latLng(endLat, endLng)
                        ],
                        distance: distance,
                        duration: distance * 60, // Estimación simple
                        instructions: [
                            { instruction: 'Inicia tu recorrido', distance: 0 },
                            { instruction: 'Continúa recto', distance: distance * 500 },
                            { instruction: 'Has llegado a tu destino', distance: distance * 1000 }
                        ]
                    });
                }, 1000);
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
                    // Si falla el backend, preguntar si continuar localmente
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
                    await calculateRoute();
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

        // ========== FUNCIONES AUXILIARES PARA RUTAS REALES ==========

        // Función simplificada para usar directamente los datos de VROOM existentes
        async function useExistingRoutes() {
            try {
                console.log('🔄 Usando rutas VROOM ya calculadas...');
                
                // Opción 1: Datos pasados desde el controlador (más eficiente)
                if (window.routesData && Array.isArray(window.routesData)) {
                    const userRoute = window.routesData.find(route => route.vehicle === demoData.motoristId);
                    
                    if (userRoute && userRoute.geometry) {
                        console.log('✅ Usando ruta de window.routesData');
                        await processVroomRoute(userRoute);
                        return true;
                    }
                }
                
                // Opción 2: Datos en sessionStorage
                const sessionRoutes = sessionStorage.getItem('all_routes');
                if (sessionRoutes) {
                    const routes = JSON.parse(sessionRoutes);
                    const userRoute = routes.find(route => route.vehicle === demoData.motoristId);
                    
                    if (userRoute && userRoute.geometry) {
                        console.log('✅ Usando ruta de sessionStorage');
                        await processVroomRoute(userRoute);
                        return true;
                    }
                }
                
                // Opción 3: Intentar cargar desde el backend
                return await loadRouteFromBackend();
                
            } catch (error) {
                console.error('❌ Error usando rutas existentes:', error);
                return false;
            }
        }

        // Simplificar el cálculo de ruta para usar directamente la geometría de VROOM
        async function calculateRoute() {
            if (currentDestinations.length === 0) {
                throw new Error('No hay destinos disponibles');
            }
            
            const destination = currentDestinations[currentDestinationIndex];
            console.log('🗺️ Usando ruta VROOM hacia:', destination.cliente);
            
            try {
                // Si ya tenemos la geometría decodificada de VROOM, usarla directamente
                if (decodedRouteCoordinates.length > 0) {
                    console.log('✅ Usando geometría real de VROOM con', decodedRouteCoordinates.length, 'puntos');
                    routeCoordinates = decodedRouteCoordinates;
                    
                    // Generar instrucciones basadas en los pasos reales de VROOM
                    currentInstructions = generateInstructionsFromSteps();
                    
                    // Dibujar la ruta real en el mapa
                    drawRoute();
                    
                    console.log(`🛣️ Ruta real cargada: ${(totalRouteDistance/1000).toFixed(1)}km, ${Math.round(totalRouteDuration/60)}min`);
                    return;
                }
                
                // Si no tenemos geometría, intentar cargar desde VROOM
                console.log('🔄 Intentando obtener geometría de VROOM...');
                const routeLoaded = await useExistingRoutes();
                
                if (routeLoaded && decodedRouteCoordinates.length > 0) {
                    routeCoordinates = decodedRouteCoordinates;
                    currentInstructions = generateInstructionsFromSteps();
                    drawRoute();
                    return;
                }
                
                // Último recurso: ruta simple
                console.warn('⚠️ No se pudo obtener geometría de VROOM, usando ruta simple');
                await calculateSimpleRoute(destination);
                
            } catch (error) {
                console.error('❌ Error calculando ruta:', error);
                await calculateSimpleRoute(destination);
            }
        }

        // Función para recargar rutas si es necesario
        async function refreshRoutesIfNeeded() {
            if (decodedRouteCoordinates.length === 0) {
                console.log('🔄 No hay rutas cargadas, intentando cargar...');
                
                const success = await useExistingRoutes();
                if (!success) {
                    console.log('⚠️ No se pudieron cargar rutas, usando datos de ejemplo');
                    await loadFallbackData();
                }
            }
        }

        // Mejorar la función de recálculo de rutas
        async function recalculateRoute() {
            if (!isNavigating || !currentPosition) {
                showWarningMessage('No hay navegación activa');
                return;
            }
            
            try {
                showLoading('Recalculando ruta', 'Obteniendo nueva ruta desde tu ubicación...');
                
                // Forzar recálculo desde el backend
                const newRoute = await getRouteFromBackend();
                
                if (newRoute) {
                    await processVroomRoute(newRoute);
                    routeCoordinates = decodedRouteCoordinates;
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
                
                // Intentar usar ruta simple como fallback
                if (currentDestinationIndex < currentDestinations.length) {
                    await calculateSimpleRoute(currentDestinations[currentDestinationIndex]);
                    showWarningMessage('⚠️ Usando ruta simple como respaldo');
                }
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
            clearMapElements();
            
            if (routeCoordinates.length === 0) return;
            
            // Línea de ruta completa
            remainingRouteLine = L.polyline(routeCoordinates, {
                color: '#94a3b8',
                weight: 6,
                opacity: 0.7,
                className: 'remaining-route'
            }).addTo(map);
            
            // Línea de progreso completado
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
            
            // Ajustar vista
            const bounds = L.latLngBounds(routeCoordinates);
            if (currentPosition) {
                bounds.extend([currentPosition.lat, currentPosition.lng]);
            }
            map.fitBounds(bounds, { padding: [50, 50] });
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
            
            // Simular actualización
            showSuccessMessage('🔄 Asignaciones actualizadas');
            refreshAssignmentsList();
            updateAssignmentCounts();
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
                showLoading('Recalculando ruta', 'Calculando nueva ruta...');
                await calculateRoute();
                hideLoading();
                showSuccessMessage('✅ Ruta recalculada exitosamente');
            } catch (error) {
                hideLoading();
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
        
        // Función para enviar datos al backend de Laravel
        async function sendToBackend(endpoint, data) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                console.error('Error enviando al backend:', error);
                throw error;
            }
        }

        // Completar entrega real (conecta con backend)
        async function completeDeliveryBackend(deliveryId) {
            try {
                console.log('✅ Completando entrega en backend:', deliveryId);
                
                const response = await sendToBackend('/api/mark-job-completed', {
                    job_id: deliveryId
                });
                
                if (response.success) {
                    console.log('✅ Entrega completada en backend exitosamente');
                    return true;
                } else {
                    throw new Error(response.error || 'Error desconocido del servidor');
                }
            } catch (error) {
                console.error('❌ Error completando entrega en backend:', error);
                showErrorMessage('Error al completar entrega en el servidor: ' + error.message);
                return false;
            }
        }

        // Obtener asignaciones desde el controlador
        async function loadAssignmentsFromBackend() {
            try {
                console.log('🔄 Cargando asignaciones desde el controlador...');
                
                // Usar el endpoint específico de tu controlador
                const response = await fetch(`/api/get-assigned-jobs/${demoData.motoristId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.assigned_jobs) {
                    // Procesar asignaciones del formato de tu controlador
                    currentAssignments = data.data.assigned_jobs.map(job => ({
                        id: job.id,
                        status: job.status || 'pending',
                        cliente: job.cliente || job.assignment_data?.cliente || 'Cliente desconocido',
                        telefono: job.assignment_data?.telefono || '',
                        direccion: job.assignment_data?.direccion || '',
                        coordenadas: {
                            lat: job.location[1], // Tu controlador usa formato VROOM [lng, lat]
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
                    // Si falla el backend, preguntar si continuar localmente
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
                    await calculateRoute();
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
                    
        // Enviar ubicación actual al backend de manera eficiente
        let lastLocationSent = 0;
        const LOCATION_SEND_INTERVAL = 10000; // 10 segundos

        async function sendLocationToBackend() {
            if (!currentPosition) return;
            
            const now = Date.now();
            if (now - lastLocationSent < LOCATION_SEND_INTERVAL) {
                return; // No enviar muy frecuentemente
            }
            
            try {
                await sendToBackend('/api/capture-vehicle-locations', {
                    locations: [{
                        vehicle_id: demoData.motoristId,
                        lat: currentPosition.lat,
                        lng: currentPosition.lng,
                        accuracy: currentPosition.accuracy
                    }]
                });
                
                lastLocationSent = now;
                console.log('📍 Ubicación enviada al backend');
                
            } catch (error) {
                console.warn('⚠️ Error enviando ubicación al backend:', error);
            }
        }

        // Obtener ruta real desde el backend usando VROOM
        async function getRouteFromBackend() {
            if (!currentPosition) {
                console.warn('⚠️ No hay posición actual para calcular ruta');
                return null;
            }
            
            try {
                console.log('🔄 Solicitando ruta real al backend...');
                
                const response = await sendToBackend('/api/seguir-ruta', {
                    current_location: [currentPosition.lat, currentPosition.lng],
                    vehicle_id: demoData.motoristId,
                    force_recalculate: true
                });
                
                if (response.success && response.routes && response.routes.length > 0) {
                    console.log('✅ Ruta obtenida del backend:', response.routes[0]);
                    return response.routes[0];
                }
                
                console.warn('⚠️ Backend no devolvió rutas válidas');
                return null;
                
            } catch (error) {
                console.error('❌ Error obteniendo ruta desde backend:', error);
                return null;
            }
        }

        // Obtener rutas pre-calculadas desde el endpoint principal
        async function loadPreCalculatedRoutes() {
            try {
                console.log('🔄 Cargando rutas pre-calculadas...');
                
                const response = await fetch('/vehicle/data', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.routes && Array.isArray(data.routes)) {
                    const userRoute = data.routes.find(route => route.vehicle === demoData.motoristId);
                    
                    if (userRoute) {
                        console.log('✅ Ruta pre-calculada encontrada');
                        await processVroomRoute(userRoute);
                        return true;
                    }
                }
                
                return false;
                
            } catch (error) {
                console.error('❌ Error cargando rutas pre-calculadas:', error);
                return false;
            }
        }

        // ========== EVENTOS DEL TECLADO ==========
        document.addEventListener('keydown', function(event) {
            // Atajos de teclado para navegación rápida
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

        // ========== NOTIFICACIONES PUSH (OPCIONAL) ==========
        
        // Solicitar permisos para notificaciones
        function requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        console.log('✅ Permisos de notificación concedidos');
                    }
                });
            }
        }

        // Mostrar notificación del sistema
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
                
                // Auto-cerrar después de 5 segundos
                setTimeout(() => notification.close(), 5000);
            }
        }

        // ========== MODO OFFLINE (OPCIONAL) ==========
        
        // Detectar estado de conexión
        function handleConnectionChange() {
            if (navigator.onLine) {
                showSuccessMessage('🌐 Conexión restaurada');
                // Sincronizar datos pendientes
                syncPendingData();
            } else {
                showWarningMessage('📡 Sin conexión - Modo offline activado');
            }
        }

        // Sincronizar datos cuando se restaure la conexión
        async function syncPendingData() {
            // Aquí podrías implementar lógica para sincronizar
            // entregas completadas offline, ubicaciones, etc.
            console.log('🔄 Sincronizando datos pendientes...');
        }

        // Escuchar cambios de conexión
        window.addEventListener('online', handleConnectionChange);
        window.addEventListener('offline', handleConnectionChange);

        // ========== FUNCIONES DE DESARROLLO/DEBUG ==========
        
        // Función para simular llegada a destino (solo para testing)
        window.simulateArrival = function() {
            if (currentDestinationIndex < currentDestinations.length) {
                const destination = currentDestinations[currentDestinationIndex];
                showArrivalNotification(destination.cliente);
                currentDeliveryId = destination.id;
                showCompleteButton();
                updateDeliveryStatus(destination.id, 'in_progress');
                console.log('🧪 Simulando llegada a:', destination.cliente);
            }
        };

        // Función para mostrar información de debug
        window.debugInfo = function() {
            console.log('🔍 Estado actual del sistema:', {
                isNavigating,
                currentPosition,
                currentDestinationIndex,
                currentDeliveryId,
                assignmentsCount: currentAssignments.length,
                destinationsCount: currentDestinations.length,
                routeCoordinatesCount: routeCoordinates.length,
                voiceEnabled,
                isAssignmentsPanelOpen
            });
        };

        // Solicitar permisos de notificación al cargar
        setTimeout(requestNotificationPermission, 2000);

        console.log('✅ Sistema de Navegación GPS cargado completamente');
        console.log('⌨️ Atajos disponibles: Espacio=Nav, C=Completar, A=Asignaciones, V=Voz, R=Recalcular, L=Ubicar, F=Ajustar, M=Mapa');
    </script>
</body>
</html>