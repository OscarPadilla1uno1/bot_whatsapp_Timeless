<!DOCTYPE html>
<html>

<head>
    <title>Sistema de Entregas Avanzado - {{ $driver->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow: hidden;
        background: #1a1a1a;
    }

    #map {
        height: 100vh;
        width: 100%;
    }

    /* Panel del motorista estilo Waze */
    .driver-panel {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        min-width: 320px;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .panel-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .panel-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        background-color: #28a745;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.7;
            transform: scale(1.1);
        }

        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    .toggle-icon {
        font-size: 18px;
        transition: transform 0.3s ease;
    }

    .panel-content {
        padding: 20px;
        max-height: 80vh;
        overflow-y: auto;
    }

    /* Panel de navegación estilo Waze */
    .navigation-panel {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        border-radius: 20px;
        padding: 16px 24px;
        min-width: 300px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        display: none;
    }

    .navigation-panel.active {
        display: block;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateX(-50%) translateY(100px);
            opacity: 0;
        }

        to {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    }

    .nav-instruction {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .nav-distance {
        font-size: 14px;
        color: #00d4ff;
        margin-bottom: 4px;
    }

    .nav-eta {
        font-size: 12px;
        color: #ccc;
    }

    /* Controles de navegación */
    .nav-controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .nav-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        transition: all 0.3s ease;
    }

    .nav-btn:hover {
        background: white;
        transform: scale(1.1);
    }

    .nav-btn.active {
        background: #007bff;
        color: white;
    }

    /* Información del motorista */
    .driver-info {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .driver-info p {
        margin: 6px 0;
        color: #555;
        font-size: 14px;
    }

    .driver-info strong {
        color: #333;
    }

    /* Estadísticas */
    .delivery-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-bottom: 16px;
    }

    .stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 8px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-number {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 10px;
        text-transform: uppercase;
        opacity: 0.9;
    }

    /* Controles principales */
    .route-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 16px;
    }

    .control-btn {
        padding: 10px 12px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .control-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
    }

    .btn-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #212529;
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    /* Lista de entregas mejorada */
    .delivery-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .delivery-item {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        border-left: 4px solid #007bff;
        position: relative;
    }

    .delivery-item:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .delivery-item.completed {
        border-left-color: #28a745;
        background: linear-gradient(135deg, #f8fff9 0%, #f0fff0 100%);
    }

    .panel-content.collapsed {
        display: none !important;
    }

    .toggle-icon.collapsed {
        transform: rotate(180deg);
    }

    @media (max-width: 768px) {
        .panel-header {
            cursor: pointer;
            user-select: none;
        }

        .toggle-icon {
            display: inline-block;
            transition: transform 0.3s ease;
        }
    }

    .delivery-item.returned {
        border-left-color: #dc3545;
        background: linear-gradient(135deg, #fff8f8 0%, #fff0f0 100%);
    }

    .delivery-item.current {
        border-left-color: #ffc107;
        background: linear-gradient(135deg, #fffbf0 0%, #fff8e1 100%);
        animation: glow 2s infinite;
    }

    @keyframes glow {
        0% {
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
        }

        50% {
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.6);
        }

        100% {
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.3);
        }
    }

    .delivery-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .delivery-client {
        font-weight: 600;
        color: #333;
        font-size: 15px;
    }

    .delivery-number {
        background: #007bff;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .delivery-info {
        margin: 8px 0;
        font-size: 13px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .phone-link {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 20px;
        background: rgba(0, 123, 255, 0.1);
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
    }

    .phone-link:hover {
        background: #007bff;
        color: white;
    }

    .delivery-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }

    .delivery-btn {
        flex: 1;
        padding: 8px 12px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .delivery-btn:hover {
        transform: translateY(-1px);
    }

    .btn-navigate {
        background: #17a2b8;
        color: white;
    }

    .btn-navigate:hover {
        background: #138496;
    }

    .btn-delivery {
        background: #28a745;
        color: white;
    }

    .btn-delivery:hover {
        background: #218838;
    }

    .btn-return {
        background: #dc3545;
        color: white;
    }

    .btn-return:hover {
        background: #c82333;
    }

    .delivery-status {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 600;
        text-align: center;
        margin-top: 8px;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    .status-returned {
        background: #f8d7da;
        color: #721c24;
    }

    .status-current {
        background: #ffeaa7;
        color: #d63031;
        animation: pulse 1s infinite;
    }

    /* Notificaciones estilo Waze */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        z-index: 3000;
        max-width: 320px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.3s ease;
    }

    .notification.success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .notification.error {
        background: linear-gradient(135deg, #dc3545, #e74c3c);
    }

    .notification.warning {
        background: linear-gradient(135deg, #ffc107, #f39c12);
        color: #212529;
    }

    .notification.info {
        background: linear-gradient(135deg, #17a2b8, #3498db);
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Loading screen */
    #loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 4000;
        color: white;
    }

    .loading-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 24px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .loading-text {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .loading-subtext {
        font-size: 16px;
        opacity: 0.8;
    }

    /* Marcadores personalizados */
    .current-location-marker {
        width: 20px;
        height: 20px;
        background: radial-gradient(circle, #4285F4 40%, rgba(66, 133, 244, 0.3) 70%);
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        animation: locationPulse 2s infinite;
    }

    @keyframes locationPulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .delivery-marker {
        font-size: 16px;
        text-align: center;
        line-height: 35px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 3px 15px rgba(0, 0, 0, 0.4);
        transition: all 0.3s ease;
    }

    .delivery-marker:hover {
        transform: scale(1.2);
    }

    /* ========================== */
    /* MEJORAS DE RESPONSIVIDAD */
    /* ========================== */

    /* Dispositivos móviles pequeños (hasta 480px) */
    @media (max-width: 480px) {
        .driver-panel {
            top: 5px;
            left: 5px;
            right: 5px;
            max-width: none;
            min-width: auto;
            width: calc(100% - 10px);
            border-radius: 12px;
        }

        .panel-header {
            padding: 12px 15px;
            border-radius: 12px 12px 0 0;
        }

        .panel-content {
            padding: 15px;
            max-height: 60vh;
        }

        .driver-info {
            padding: 12px;
        }

        .driver-info p {
            font-size: 13px;
        }

        .delivery-stats {
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .stat-box {
            padding: 10px 6px;
        }

        .stat-number {
            font-size: 16px;
        }

        .route-controls {
            grid-template-columns: 1fr;
            gap: 6px;
        }

        .control-btn {
            padding: 12px 10px;
            font-size: 14px;
        }

        .delivery-item {
            padding: 12px;
        }

        .delivery-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .delivery-actions {
            flex-direction: column;
            gap: 6px;
        }

        .delivery-btn {
            padding: 10px;
            font-size: 13px;
        }

        .nav-controls {
            top: 5px;
            right: 5px;
        }

        .nav-btn {
            width: 45px;
            height: 45px;
            font-size: 18px;
        }

        .navigation-panel {
            left: 10px;
            right: 10px;
            bottom: 10px;
            transform: none;
            min-width: auto;
            padding: 12px 18px;
        }

        .nav-instruction {
            font-size: 16px;
        }

        .nav-distance {
            font-size: 13px;
        }

        .nav-eta {
            font-size: 11px;
        }

        .notification {
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }

    /* Dispositivos móviles medianos (481px a 768px) */
    @media (min-width: 481px) and (max-width: 768px) {
        .driver-panel {
            left: 10px;
            right: 10px;
            max-width: none;
            min-width: auto;
            width: calc(100% - 20px);
        }

        .panel-content.collapsed {
            display: none;
        }

        .toggle-icon.collapsed {
            transform: rotate(180deg);
        }

        .route-controls {
            grid-template-columns: 1fr 1fr;
        }

        .delivery-stats {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .navigation-panel {
            left: 20px;
            right: 20px;
            transform: none;
            min-width: auto;
        }

        .nav-controls {
            right: 10px;
        }
    }

    /* Tablets (769px a 1024px) */
    @media (min-width: 769px) and (max-width: 1024px) {
        .driver-panel {
            max-width: 350px;
        }

        .panel-content {
            display: block !important;
        }

        .toggle-icon {
            display: none;
        }
    }

    /* Modo landscape en móviles */
    @media (max-height: 500px) and (max-width: 900px) {
        .driver-panel {
            max-height: 80vh;
        }

        .panel-content {
            max-height: 60vh;
        }

        .delivery-list {
            max-height: 200px;
        }
    }

    /* Mejoras de accesibilidad para dispositivos táctiles */
    @media (hover: none) and (pointer: coarse) {

        .control-btn,
        .delivery-btn,
        .nav-btn {
            min-height: 44px;
            /* Tamaño mínimo recomendado para elementos táctiles */
        }

        .delivery-item:hover {
            transform: none;
            /* Eliminar transformaciones hover en dispositivos táctiles */
        }
    }

    /* Soporte para dispositivos con notch o áreas seguras */
    @supports(padding: max(0px)) {

        .driver-panel,
        .nav-controls {
            top: max(10px, env(safe-area-inset-top));
        }

        .navigation-panel {
            bottom: max(20px, env(safe-area-inset-bottom));
        }
    }

    /* Ocultar el panel de indicaciones de Leaflet por defecto */
    .leaflet-routing-container {
        display: none !important;
    }

    /* Mostrar el panel de indicaciones dentro del menú lateral */
    .routing-instructions {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        padding: 12px;
        margin-top: 8px;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .routing-instructions h4 {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        padding-bottom: 4px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .instruction-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 6px 0;
        font-size: 12px;
        color: #555;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .instruction-item:last-child {
        border-bottom: none;
    }

    .instruction-icon {
        width: 16px;
        height: 16px;
        background: #007bff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 8px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .instruction-text {
        flex: 1;
        line-height: 1.3;
    }

    .instruction-distance {
        color: #007bff;
        font-weight: 600;
        font-size: 11px;
        white-space: nowrap;
    }

    /* Marcadores de navegación */
    .navigation-marker {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: bounceIn 0.6s ease;
    }

    .origin-marker {
        background: linear-gradient(135deg, #4285F4, #1a73e8);
    }

    .destination-marker {
        background: linear-gradient(135deg, #ff6b35, #e55a2b);
        animation: destinationPulse 2s infinite;
    }

    @keyframes bounceIn {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        50% {
            transform: scale(1.2);
            opacity: 0.8;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes destinationPulse {
        0% {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transform: scale(1);
        }

        50% {
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.6);
            transform: scale(1.05);
        }

        100% {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transform: scale(1);
        }
    }

    .current-location-container {
        transition: transform 0.3s ease;
    }

    .location-dot {
        width: 16px;
        height: 16px;
        background: #4285F4;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .location-arrow {
        color: #4285F4;
        font-size: 12px;
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        text-shadow: 0 0 3px white;
    }

    .nav-speed {
        font-size: 12px;
        color: #ff6b35;
        font-weight: 600;
    }
    </style>
</head>

<body>
    <div id="map"></div>

    <!-- Panel del motorista -->
    <div class="driver-panel">
        <div class="panel-header" onclick="togglePanel()">
            <h3>
                <span class="status-indicator" id="status-indicator"></span>
                {{ $driver->name }}
            </h3>
            <span class="toggle-icon" id="toggle-icon">▼</span>
        </div>

        <div class="panel-content" id="panel-content">
            <div class="driver-info">
                <p><strong>Email:</strong> {{ $driver->email }}</p>
                @if($route)
                <p><strong>Vehículo:</strong> #{{ $route['vehicle'] ?? 'N/A' }}</p>
                @endif
                <p><strong>Estado:</strong> <span id="connection-status">Conectado</span></p>
                <p><strong>Ubicación:</strong> <span id="location-status">Obteniendo...</span></p>
            </div>

            @if($route && isset($route['steps']))
            <div class="delivery-stats">
                <div class="stat-box">
                    <div class="stat-number" id="completed-count">0</div>
                    <div class="stat-label">Entregadas</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" id="pending-count">{{ count($route['steps']) }}</div>
                    <div class="stat-label">Pendientes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" id="current-delivery">-</div>
                    <div class="stat-label">Actual</div>
                </div>
            </div>

            <div class="route-controls">
                <button class="control-btn btn-primary" onclick="showOptimizedRoute()">
                    📍 Mostrar Ruta
                </button>
                <button class="control-btn btn-success" id="navigation-button" onclick="toggleNavigation()">
                    🧭 Navegación
                </button>
                <button class="control-btn btn-warning" onclick="centerOnLocation()">
                    🎯 Mi Posición
                </button>
                <button class="control-btn btn-danger" onclick="emergencyAlert()">
                    🚨 Emergencia
                </button>
            </div>

            <div class="delivery-list" id="delivery-list">
                @foreach($route['steps'] as $index => $step)
                @if(isset($step['type']) && $step['type'] === 'job' && isset($step['job_details']))
                <div class="delivery-item" data-delivery-id="{{ $step['job'] }}" id="delivery-{{ $step['job'] }}">
                    <div class="delivery-header">
                        <div class="delivery-client">{{ $step['job_details']['cliente'] ?? 'Cliente desconocido' }}
                        </div>
                        <div class="delivery-number">Parada {{ $index + 1 }}</div>
                    </div>

                    <div class="delivery-info">
                        📍 Dirección de entrega
                    </div>

                    @if($step['job_details']['telefono'] ?? false)
                    <a href="tel:{{ $step['job_details']['telefono'] }}" class="phone-link">
                        📞 {{ $step['job_details']['telefono'] }}
                    </a>
                    @endif

                    <div class="delivery-actions" id="actions-{{ $step['job'] }}">
                        <button class="delivery-btn btn-navigate" onclick="navigateToDelivery({{ $step['job'] }})">
                            🧭 Navegar
                        </button>
                        <button class="delivery-btn btn-delivery"
                            onclick="markDelivery('{{ $step['job'] }}', 'completed')">
                            ✅ Entregado
                        </button>
                    </div>

                    <div class="delivery-status status-pending">Pendiente</div>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <div style="text-align: center; padding: 40px 20px; color: #666;">
                <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                <p>No hay ruta asignada actualmente.</p>
                <button class="control-btn btn-primary" onclick="loadRoute()" style="margin-top: 16px;">
                    📍 Cargar Ruta
                </button>
            </div>
            @endif
        </div>
    </div>


    <!-- Panel de navegación estilo Waze -->
    <div class="navigation-panel" id="navigation-panel">
        <div class="nav-instruction" id="nav-instruction">
            Iniciando navegación...
        </div>
        <div class="nav-distance" id="nav-distance">
            Calculando ruta...
        </div>
        <div class="nav-eta" id="nav-eta">
            Tiempo estimado: --:--
        </div>
    </div>

    <!-- Loading screen -->
    <div id="loading">
        <div class="loading-spinner"></div>
        <div class="loading-text">Sistema de Navegación</div>
        <div class="loading-subtext">Inicializando GPS y mapas...</div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline@1.1.1/src/polyline.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>



    <script>
    // Variables globales del sistema
    const driverRoute = @json($route ?? null);
    const driverData = @json($driver);
    let deliveryStatuses = {};

    // Variables del mapa y navegación
    let map;
    let currentLocationMarker = null;
    let routingControl = null;
    let currentMarkers = [];
    let isNavigating = false;
    let currentDeliveryIndex = 0;
    let watchId = null;
    let locationTracking = false;
    let trafficView = false;
    let autoZoom = true;

    // Variables de navegación
    let currentRoute = null;
    let currentInstructions = [];
    let currentInstructionIndex = 0;
    let voiceEnabled = true;

    // Variables para navegación en tiempo real
    let navigationWatchId = null;
    let currentSpeed = 0;
    let lastKnownPosition = null;
    let routeProgress = 0;
    let currentStepIndex = 0;
    let nextWaypointDistance = 0;
    let estimatedArrival = null;

    let routePolyline = null;
    let completedRoutePolyline = null;
    let remainingRoutePolyline = null;
    let routeCoordinates = [];
    let completedCoordinates = [];
    let closestPointOnRoute = null;
    let routeProgressPercent = 0;

    // Inicialización del sistema
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Iniciando sistema de navegación avanzado...');

        initializeMap();
        initializeGPS();
        loadRouteData();
        setupEventListeners();

        // Ocultar loading después de inicializar
        setTimeout(() => {
            hideLoading();
            //showNotification('Sistema de navegación listo', 'success');
        }, 3000);
    });

    // Inicializar mapa
    function initializeMap() {
        map = L.map('map').setView([14.0821, -87.2065], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        console.log('🗺️ Mapa inicializado');
    }

    // Inicializar GPS
    function initializeGPS() {
        if (!navigator.geolocation) {
            //showNotification('GPS no disponible en este dispositivo', 'error');
            return;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 30000
        };

        navigator.geolocation.getCurrentPosition(
            handleLocationSuccess,
            handleLocationError,
            options
        );

        console.log('📡 GPS inicializado');
    }

    // Manejar ubicación exitosa
    function handleLocationSuccess(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        const speed = position.coords.speed || 0;

        updateCurrentLocation(lat, lng, accuracy, speed);

        if (isNavigating) {
            updateNavigationProgress(lat, lng);
        }
    }

    // Manejar error de ubicación
    function handleLocationError(error) {
        console.error('❌ Error GPS:', error);

        let message = 'Error GPS';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Acceso GPS denegado';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'GPS no disponible';
                break;
            case error.TIMEOUT:
                message = 'Tiempo GPS agotado';
                break;
        }

        //showNotification(message, 'error');
        document.getElementById('location-status').textContent = 'Error GPS';
        document.getElementById('status-indicator').style.backgroundColor = '#dc3545';
    }

    // Actualizar ubicación actual
    function updateCurrentLocation(lat, lng, accuracy, speed) {
        if (currentLocationMarker) {
            currentLocationMarker.setLatLng([lat, lng]);
            if (isNavigating) {
                updateOriginMarker(lat, lng);
            }
        } else {
            currentLocationMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'current-location-marker',
                    html: '<div class="current-location-marker"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                })
            }).addTo(map);

            currentLocationMarker.bindPopup("Tu ubicación actual");
        }

        // Actualizar display
        const speedKmh = speed ? (speed * 3.6).toFixed(0) : 0;
        document.getElementById('location-status').textContent =
            `${lat.toFixed(4)}, ${lng.toFixed(4)} (${speedKmh} km/h)`;

        // Auto-centrar si está activado
        if (autoZoom && isNavigating) {
            map.setView([lat, lng], 18, {
                animate: true
            });
        }

        console.log('📍 Ubicación actualizada:', lat.toFixed(4), lng.toFixed(4));
    }

    // Cargar datos de la ruta con validaciones
    function loadRouteData() {
        console.log('📦 Cargando datos de ruta...');
        console.log('Driver Route:', driverRoute);

        if (!driverRoute) {
            console.log('⚠️ No hay ruta asignada');
            return;
        }

        // Validar estructura de la ruta
        if (!driverRoute.steps || !Array.isArray(driverRoute.steps)) {
            console.error('❌ Estructura de ruta inválida - no hay steps');
            //showNotification('Estructura de ruta inválida', 'error');
            return;
        }

        console.log('📋 Steps encontrados:', driverRoute.steps.length);

        // Inicializar estados de entrega y validar coordenadas
        let validDeliveries = 0;
        let invalidDeliveries = 0;

        driverRoute.steps.forEach((step, index) => {
            console.log(`Step ${index}:`, step);

            if (step.type === 'job') {
                deliveryStatuses[step.job] = 'pending';

                // Validar coordenadas de cada entrega
                if (!step.location || !Array.isArray(step.location) || step.location.length !== 2) {
                    console.warn(`❌ Entrega ${step.job} sin coordenadas válidas:`, step.location);
                    invalidDeliveries++;
                } else {
                    const [lng, lat] = step.location;
                    if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                        console.warn(`❌ Entrega ${step.job} con coordenadas inválidas:`, lat, lng);
                        invalidDeliveries++;
                    } else {
                        console.log(`✅ Entrega ${step.job} con coordenadas válidas:`, lat, lng);
                        validDeliveries++;
                    }
                }
            }
        });

        console.log(`✅ Entregas válidas: ${validDeliveries}, ❌ Inválidas: ${invalidDeliveries}`);

        if (validDeliveries === 0) {
            //showNotification('No hay entregas con coordenadas válidas', 'warning');
        } else if (invalidDeliveries > 0) {
            //showNotification(`${invalidDeliveries} entregas sin coordenadas válidas`, 'warning');
        }

        updateStats();
    }

    // Función mejorada para manejar errores de ubicación GPS
    function handleLocationError(error) {
        console.error('❌ Error GPS completo:', error);

        let message = 'Error GPS';
        let severity = 'error';

        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = 'Acceso GPS denegado. Por favor, permite el acceso a la ubicación.';
                severity = 'error';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'GPS no disponible. Verifica tu conexión.';
                severity = 'warning';
                break;
            case error.TIMEOUT:
                message = 'Tiempo GPS agotado. Reintentando...';
                severity = 'warning';
                // Reintentar automáticamente
                setTimeout(initializeGPS, 5000);
                break;
            default:
                message = `Error GPS desconocido: ${error.message}`;
                severity = 'error';
                break;
        }

        //showNotification(message, severity);
        document.getElementById('location-status').textContent = 'Error GPS';
        document.getElementById('status-indicator').style.backgroundColor = '#dc3545';
    }

    // Función de debug para mostrar información del sistema
    function debugSystem() {
        console.log('=== DEBUG DEL SISTEMA ===');
        console.log('Driver Data:', driverData);
        console.log('Driver Route:', driverRoute);
        console.log('Current Location Marker:', currentLocationMarker);
        console.log('Delivery Statuses:', deliveryStatuses);
        console.log('Is Navigating:', isNavigating);
        console.log('Current Route:', currentRoute);
        console.log('Location Tracking:', locationTracking);

        // Validar datos de entrega
        if (driverRoute && driverRoute.steps) {
            console.log('=== VALIDACIÓN DE ENTREGAS ===');
            driverRoute.steps.forEach((step, index) => {
                if (step.type === 'job') {
                    console.log(`Entrega ${step.job}:`);
                    console.log('  - Location:', step.location);
                    console.log('  - Job Details:', step.job_details);
                    console.log('  - Status:', deliveryStatuses[step.job]);

                    if (step.location) {
                        const [lng, lat] = step.location;
                        console.log(
                            `  - Lat: ${lat}, Lng: ${lng} (Válidas: ${!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0})`
                        );
                    }
                }
            });
        }

        // Información de navegación
        console.log('=== ESTADO DE NAVEGACIÓN ===');
        console.log('GPS Disponible:', navigator.geolocation ? 'Sí' : 'No');
        console.log('Watch ID:', watchId);
        console.log('Routing Control:', routingControl);
        console.log('Current Markers:', currentMarkers.length);

        return 'Debug info logged to console';
    }

    // Función para testear coordenadas específicas
    function testNavigation() {
        console.log('🧪 Testeando navegación...');

        // Coordenadas de prueba en Tegucigalpa
        const testCoords = {
            lat: 14.0823,
            lng: -87.2063
        };

        if (!currentLocationMarker) {
            // Crear ubicación de prueba
            console.log('Creando ubicación de prueba...');
            updateCurrentLocation(14.0821, -87.2065, 10, 0);
        }

        // Crear entrega de prueba
        const testDelivery = {
            job: 'TEST_001',
            location: [testCoords.lng, testCoords.lat],
            job_details: {
                cliente: 'Cliente de Prueba',
                telefono: '9999-9999'
            }
        };

        console.log('Navegando a coordenadas de prueba:', testCoords);

        // Intentar navegación
        const currentPos = currentLocationMarker.getLatLng();
        useSimpleNavigation(currentPos, testCoords.lat, testCoords.lng, testDelivery);

        //showNotification('Navegación de prueba iniciada', 'info');
    }

    // Función mejorada de inicialización con más validaciones
    function initializeSystemWithValidation() {
        console.log('🔧 Inicializando sistema con validaciones completas...');

        // Verificar soporte de geolocalización
        if (!navigator.geolocation) {
            //showNotification('GPS no soportado en este navegador', 'error');
            return false;
        }

        // Verificar que Leaflet esté cargado
        if (typeof L === 'undefined') {
            //showNotification('Error: Leaflet no está cargado', 'error');
            return false;
        }

        // Verificar que el mapa esté inicializado
        if (!map) {
            //showNotification('Error: Mapa no inicializado', 'error');
            return false;
        }

        // Verificar datos del driver
        if (!driverData || !driverData.id) {
            //showNotification('Error: Datos del motorista inválidos', 'error');
            return false;
        }

        console.log('✅ Todas las validaciones pasaron');
        return true;
    }

    // Modificar la función de inicialización principal
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Iniciando sistema de navegación avanzado...');

        try {
            initializeMap();

            if (!initializeSystemWithValidation()) {
                console.error('❌ Fallo en la inicialización del sistema');
                return;
            }

            initializeGPS();
            loadRouteData();
            setupEventListeners();

            // Funciones de debug disponibles globalmente
            window.debugSystem = debugSystem;
            window.testNavigation = testNavigation;

            console.log('💡 Funciones de debug disponibles: debugSystem(), testNavigation()');

        } catch (error) {
            console.error('❌ Error crítico en inicialización:', error);
            //showNotification('Error crítico en inicialización', 'error');
        }

        // Ocultar loading después de inicializar
        setTimeout(() => {
            hideLoading();
            //showNotification('Sistema de navegación listo', 'success');
        }, 3000);
    });

    // Mostrar ruta optimizada
    function showOptimizedRoute() {
        console.log('🗺️ Mostrando ruta optimizada...');

        if (!driverRoute || !driverRoute.steps) {
            //showNotification('No hay ruta para mostrar', 'warning');
            return;
        }

        clearPreviousRoute();

        if (driverRoute.geometry) {
            try {
                const decoded = polyline.decode(driverRoute.geometry);
                const latlngs = decoded.map(p => L.latLng(p[0], p[1]));

                // Dibujar ruta principal
                const routePolyline = L.polyline(latlngs, {
                    color: '#007bff',
                    weight: 5,
                    opacity: 0.8,
                    dashArray: '10, 5'
                });
                routePolyline.addTo(map);
                currentMarkers.push(routePolyline);

            } catch (error) {
                console.error('Error decodificando ruta:', error);
            }
        }

        // Añadir marcadores de entregas
        addDeliveryMarkers();

        //showNotification('Ruta mostrada en el mapa', 'success');
    }

    // Añadir marcadores de entregas
    function addDeliveryMarkers() {
        if (!driverRoute || !driverRoute.steps) return;

        driverRoute.steps.forEach((step, index) => {
            if (step.type !== 'job' || !step.location) return;

            const [lng, lat] = step.location;
            const status = deliveryStatuses[step.job] || 'pending';

            let color = '#ffc107';
            let icon = '📦';

            if (status === 'completed') {
                color = '#28a745';
                icon = '✅';
            } else if (status === 'returned') {
                color = '#dc3545';
                icon = '🔄';
            } else if (index === currentDeliveryIndex) {
                color = '#ff6b35';
                icon = '🎯';
            }

            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'delivery-marker',
                    html: `<div class="delivery-marker" style="background-color: ${color}; width: 35px; height: 35px;">${icon}</div>`,
                    iconSize: [35, 35],
                    iconAnchor: [17, 17]
                })
            });

            const popupContent = `
                    <div style="text-align: center; min-width: 200px;">
                        <h4>${step.job_details?.cliente || 'Cliente'}</h4>
                        <p>Entrega #${index + 1}</p>
                        ${step.job_details?.telefono ? `<p>📞 ${step.job_details.telefono}</p>` : ''}
                        <div style="margin-top: 10px;">
                            <button onclick="navigateToDelivery(${step.job})" 
                                    style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 4px; margin: 2px; cursor: pointer;">
                                🧭 Navegar
                            </button>
                            ${status === 'pending' ? `
                                <button onclick="markDelivery('${step.job}', 'completed')" 
                                        style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; margin: 2px; cursor: pointer;">
                                    ✅ Entregado
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;

            marker.bindPopup(popupContent);
            marker.addTo(map);
            currentMarkers.push(marker);
        });

        // Ajustar vista para mostrar todos los marcadores
        if (currentMarkers.length > 0) {
            const group = new L.featureGroup(currentMarkers.filter(m => m.getLatLng));
            if (group.getLayers().length > 0) {
                map.fitBounds(group.getBounds(), {
                    padding: [20, 20]
                });
            }
        }
    }

    // Limpiar ruta anterior
    function clearPreviousRoute() {
        currentMarkers.forEach(marker => {
            if (map.hasLayer(marker)) {
                map.removeLayer(marker);
            }
        });
        currentMarkers = [];

        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
    }

    // Toggle navegación
    function toggleNavigation() {
        isNavigating = !isNavigating;
        const button = document.getElementById('navigation-button');
        const panel = document.getElementById('navigation-panel');

        if (isNavigating) {
            startNavigation();
            button.textContent = '⏹️ Detener Nav';
            button.classList.remove('btn-success');
            button.classList.add('btn-danger');
            panel.classList.add('active');
            //showNotification('Navegación activada', 'success');
        } else {
            stopNavigation();
            button.textContent = '🧭 Navegación';
            button.classList.remove('btn-danger');
            button.classList.add('btn-success');
            panel.classList.remove('active');
            //showNotification('Navegación desactivada', 'info');
        }
    }

    // Iniciar navegación
    function startNavigation() {
        if (!currentLocationMarker) {
            //showNotification('Esperando ubicación GPS...', 'warning');
            setTimeout(startNavigation, 2000);
            return;
        }

        // Iniciar seguimiento continuo de ubicación
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 5000
        };

        watchId = navigator.geolocation.watchPosition(
            handleLocationSuccess,
            handleLocationError,
            options
        );

        // Buscar próxima entrega
        findNextDelivery();

        console.log('🧭 Navegación iniciada');
    }

    // Detener navegación
    function stopNavigation() {
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }

        if (navigationWatchId) {
            navigator.geolocation.clearWatch(navigationWatchId);
            navigationWatchId = null;
        }

        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }

        // NUEVO: Limpiar polylines dinámicas
        if (completedRoutePolyline) {
            map.removeLayer(completedRoutePolyline);
            completedRoutePolyline = null;
        }

        if (remainingRoutePolyline) {
            map.removeLayer(remainingRoutePolyline);
            remainingRoutePolyline = null;
        }

        // Limpiar variables
        routeCoordinates = [];
        completedCoordinates = [];
        routeProgressPercent = 0;
        currentRoute = null;
        currentInstructions = [];
        currentInstructionIndex = 0;
        nextWaypointDistance = 0;
        estimatedArrival = null;

        console.log('🛑 Navegación detenida');
    }

    // Buscar próxima entrega
    function findNextDelivery() {
        if (!driverRoute || !driverRoute.steps) return;

        const pendingDeliveries = driverRoute.steps.filter(step =>
            step.type === 'job' && deliveryStatuses[step.job] === 'pending'
        );

        if (pendingDeliveries.length === 0) {
            //showNotification('¡Todas las entregas completadas!', 'success');
            stopNavigation();
            return;
        }

        const nextDelivery = pendingDeliveries[0];
        navigateToDelivery(nextDelivery.job);
    }

    function navigateToDelivery(deliveryId) {
        console.log('Iniciando navegación a entrega:', deliveryId);

        if (!currentLocationMarker) {
            showNotification('Esperando ubicación GPS...', 'warning');
            setTimeout(() => navigateToDelivery(deliveryId), 3000);
            return;
        }

        const delivery = driverRoute.steps.find(step => step.job == deliveryId);
        if (!delivery || !delivery.location) {
            showNotification('No se encontró la entrega', 'error');
            return;
        }

        const [lng, lat] = delivery.location;
        const currentPos = currentLocationMarker.getLatLng();

        updateCurrentDelivery(deliveryId);

        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }

        addNavigationMarkers(currentPos, {
            lat: lat,
            lng: lng
        }, delivery);

        try {
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(currentPos.lat, currentPos.lng),
                    L.latLng(lat, lng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() {
                    return null;
                },
                lineOptions: {
                    styles: [{
                        color: '#ff6b35',
                        weight: 6,
                        opacity: 0.8
                    }]
                },
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://lacampañafoodservice.com/osrm/route/v1',
                    language: 'es',
                    timeout: 30000
                })
            }).on('routesfound', function(e) {
                console.log('Ruta encontrada:', e.routes[0]);
                const route = e.routes[0];
                currentRoute = route;
                currentInstructions = route.instructions || [];
                currentInstructionIndex = 0;

                // NUEVO: Iniciar navegación en tiempo real
                startRealTimeNavigation(delivery);

                updateNavigationPanel();

                if (autoZoom && route.bounds) {
                    map.fitBounds(route.bounds, {
                        padding: [20, 20]
                    });
                }

                showNotification(`Navegando a: ${delivery.job_details?.cliente || 'Cliente'}`, 'info');

            }).on('routesfound', function(e) {
                console.log('Ruta encontrada:', e.routes[0]);
                const route = e.routes[0];
                currentRoute = route;
                currentInstructions = route.instructions || [];
                currentInstructionIndex = 0;

                // NUEVO: Guardar coordenadas de la ruta
                routeCoordinates = route.coordinates.map(coord => [coord.lat, coord.lng]);
                completedCoordinates = [];

                // NUEVO: Crear polylines separadas para ruta completada y pendiente
                createDynamicRoutePolylines();

                startRealTimeNavigation(delivery);
                updateNavigationPanel();

                if (autoZoom && route.bounds) {
                    map.fitBounds(route.bounds, {
                        padding: [20, 20]
                    });
                }

                showNotification(`Navegando a: ${delivery.job_details?.cliente || 'Cliente'}`, 'info');
            }).addTo(map);

        } catch (error) {
            console.error('Error creando control de routing:', error);
            useSimpleNavigation(currentPos, lat, lng, delivery);
        }
    }

    function createDynamicRoutePolylines() {
        // Limpiar polylines anteriores
        if (completedRoutePolyline) {
            map.removeLayer(completedRoutePolyline);
        }
        if (remainingRoutePolyline) {
            map.removeLayer(remainingRoutePolyline);
        }

        // Crear polyline para ruta completada (verde)
        completedRoutePolyline = L.polyline([], {
            color: '#28a745',
            weight: 8,
            opacity: 0.8,
            dashArray: null
        }).addTo(map);

        // Crear polyline para ruta restante (azul/naranja)
        remainingRoutePolyline = L.polyline(routeCoordinates, {
            color: '#ff6b35',
            weight: 6,
            opacity: 0.7,
            dashArray: '10, 5'
        }).addTo(map);
    }

    function addNavigationMarkers(origin, destination, delivery) {
        // Marcador de origen (ubicación actual)
        const originMarker = L.marker([origin.lat, origin.lng], {
            icon: L.divIcon({
                className: 'navigation-origin-marker',
                html: `<div class="navigation-marker origin-marker">🚗</div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            })
        }).addTo(map);

        originMarker.bindPopup(`
        <div style="text-align: center;">
            <h4>Tu ubicación</h4>
            <p>Punto de inicio</p>
        </div>
    `);

        // Marcador de destino
        const destinationMarker = L.marker([destination.lat, destination.lng], {
            icon: L.divIcon({
                className: 'navigation-destination-marker',
                html: `<div class="navigation-marker destination-marker">🎯</div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            })
        }).addTo(map);

        destinationMarker.bindPopup(`
        <div style="text-align: center;">
            <h4>${delivery.job_details?.cliente || 'Cliente'}</h4>
            <p>Destino de entrega</p>
            ${delivery.job_details?.telefono ? `<p>📞 ${delivery.job_details.telefono}</p>` : ''}
        </div>
    `);

        // Agregar a currentMarkers para limpiarlos después
        currentMarkers.push(originMarker);
        currentMarkers.push(destinationMarker);
    }

    // NUEVA FUNCIÓN: Iniciar navegación en tiempo real
    function startRealTimeNavigation(delivery) {
        console.log('🚀 Iniciando navegación en tiempo real');

        const panel = document.getElementById('navigation-panel');
        panel.classList.add('active');

        // Configurar seguimiento GPS de alta precisión
        const options = {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 1000
        };

        // Iniciar seguimiento continuo
        if (navigationWatchId) {
            navigator.geolocation.clearWatch(navigationWatchId);
        }

        navigationWatchId = navigator.geolocation.watchPosition(
            (position) => handleRealTimePosition(position, delivery),
            handleLocationError,
            options
        );
    }

    // NUEVA FUNCIÓN: Manejar posición en tiempo real
    function handleRealTimePosition(position, delivery) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const speed = position.coords.speed || 0;
        const heading = position.coords.heading;

        currentSpeed = speed * 3.6; // Convertir a km/h
        lastKnownPosition = {
            lat,
            lng
        };

        // Actualizar marcador de posición actual
        updateCurrentLocationMarker(lat, lng, heading);

        // Actualizar progreso de navegación
        updateNavigationProgress(lat, lng, delivery);

        // Actualizar panel de navegación
        updateRealTimeNavigationPanel();

        // Auto-centrar si está navegando
        if (isNavigating && autoZoom) {
            map.setView([lat, lng], 18, {
                animate: true,
                duration: 0.5
            });
        }
    }

    // NUEVA FUNCIÓN: Actualizar marcador con dirección
    function updateCurrentLocationMarker(lat, lng, heading) {
        if (currentLocationMarker) {
            currentLocationMarker.setLatLng([lat, lng]);

            // Actualizar rotación del marcador según la dirección
            if (heading !== null && heading !== undefined) {
                const markerElement = currentLocationMarker.getElement();
                if (markerElement) {
                    markerElement.style.transform += ` rotate(${heading}deg)`;
                }
            }
        } else {
            // Crear nuevo marcador con indicador de dirección
            const markerHtml = `
            <div class="current-location-marker" ${heading ? `style="transform: rotate(${heading}deg)"` : ''}>
                <div class="location-dot"></div>
                <div class="location-arrow">▲</div>
            </div>
        `;

            currentLocationMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'current-location-container',
                    html: markerHtml,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(map);
        }
    }

    // NUEVA FUNCIÓN: Actualizar progreso de navegación
    function updateNavigationProgress(currentLat, currentLng, delivery) {
        if (!currentRoute || !routeCoordinates || routeCoordinates.length === 0) return;

        const [destLng, destLat] = delivery.location;
        const distanceToDestination = calculateDistance(currentLat, currentLng, destLat, destLng);

        // NUEVO: Encontrar punto más cercano en la ruta
        const closestPoint = findClosestPointOnRoute(currentLat, currentLng);

        if (closestPoint) {
            // NUEVO: Actualizar polylines según progreso
            updateRouteProgress(closestPoint.index, currentLat, currentLng);

            // Actualizar porcentaje de progreso
            routeProgressPercent = Math.round((closestPoint.index / routeCoordinates.length) * 100);
        }

        // Encontrar instrucción más cercana
        let closestInstructionIndex = 0;
        let minDistance = Infinity;

        currentInstructions.forEach((instruction, index) => {
            if (instruction.index && currentRoute.coordinates) {
                const coordIndex = instruction.index[0];
                const coord = currentRoute.coordinates[coordIndex];
                if (coord) {
                    const distance = calculateDistance(currentLat, currentLng, coord.lat, coord.lng);
                    if (distance < minDistance) {
                        minDistance = distance;
                        closestInstructionIndex = index;
                    }
                }
            }
        });

        if (closestInstructionIndex !== currentInstructionIndex) {
            currentInstructionIndex = closestInstructionIndex;
            updateRealTimeNavigationPanel();

            if (voiceEnabled) {
                speakInstruction(currentInstructions[currentInstructionIndex]);
            }
        }

        nextWaypointDistance = distanceToDestination;

        if (currentSpeed > 0) {
            const timeInHours = distanceToDestination / (currentSpeed * 1000);
            estimatedArrival = new Date(Date.now() + timeInHours * 3600000);
        }
    }

    function updateRouteProgress(closestIndex, currentLat, currentLng) {
        // Coordenadas completadas (desde inicio hasta posición actual)
        const completed = routeCoordinates.slice(0, closestIndex + 1);

        // Añadir posición actual exacta al final de la ruta completada
        completed.push([currentLat, currentLng]);

        // Coordenadas restantes (desde posición actual hasta destino)
        const remaining = [
            [currentLat, currentLng], ...routeCoordinates.slice(closestIndex + 1)
        ];

        // Actualizar polylines
        if (completedRoutePolyline) {
            completedRoutePolyline.setLatLngs(completed);
        }

        if (remainingRoutePolyline) {
            remainingRoutePolyline.setLatLngs(remaining);
        }

        // Añadir efecto de "glow" a la ruta completada
        completedRoutePolyline.setStyle({
            color: '#28a745',
            weight: 8,
            opacity: 0.9,
            dashArray: null
        });

        remainingRoutePolyline.setStyle({
            color: '#ff6b35',
            weight: 6,
            opacity: 0.7,
            dashArray: '8, 4'
        });
    }

    function findClosestPointOnRoute(currentLat, currentLng) {
        let minDistance = Infinity;
        let closestIndex = 0;
        let closestPoint = null;

        for (let i = 0; i < routeCoordinates.length; i++) {
            const [lat, lng] = routeCoordinates[i];
            const distance = calculateDistance(currentLat, currentLng, lat, lng);

            if (distance < minDistance) {
                minDistance = distance;
                closestIndex = i;
                closestPoint = {
                    lat,
                    lng,
                    index: i,
                    distance
                };
            }
        }

        return closestPoint;
    }

    // NUEVA FUNCIÓN: Actualizar panel en tiempo real
    function updateRealTimeNavigationPanel() {
        const instruction = currentInstructions[currentInstructionIndex];
        if (!instruction) return;

        const instructionElement = document.getElementById('nav-instruction');
        const distanceElement = document.getElementById('nav-distance');
        const etaElement = document.getElementById('nav-eta');

        if (instructionElement) {
            instructionElement.textContent = instruction.text || 'Continúa recto';
        }

        if (distanceElement) {
            // NUEVO: Mostrar progreso además de distancia
            const progressText = routeProgressPercent > 0 ? ` (${routeProgressPercent}%)` : '';
            distanceElement.textContent = formatDistance(nextWaypointDistance * 1000) + progressText;
        }

        if (etaElement) {
            const eta = estimatedArrival ? estimatedArrival.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            }) : '--:--';
            etaElement.innerHTML = `
            Llegada: ${eta}<br>
            <span class="nav-speed">Velocidad: ${Math.round(currentSpeed)} km/h</span>
        `;
        }
    }

    // NUEVA FUNCIÓN: Calcular distancia entre dos puntos (en km)
    function calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Radio de la Tierra en km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    // NUEVA FUNCIÓN: Síntesis de voz (opcional)
    function speakInstruction(instruction) {
        if ('speechSynthesis' in window && instruction && instruction.text) {
            const utterance = new SpeechSynthesisUtterance(instruction.text);
            utterance.lang = 'es-ES';
            utterance.rate = 0.9;
            utterance.volume = 0.8;
            speechSynthesis.speak(utterance);
        }
    }

    // Navegación simple de fallback
    function useSimpleNavigation(currentPos, targetLat, targetLng, delivery) {
        addNavigationMarkers(currentPos, {
            lat: targetLat,
            lng: targetLng
        }, delivery);
        const directLine = L.polyline([
            [currentPos.lat, currentPos.lng],
            [targetLat, targetLng]
        ], {
            color: '#ff6b35',
            weight: 4,
            opacity: 0.7,
            dashArray: '10, 10'
        }).addTo(map);

        currentMarkers.push(directLine);

        // Centrar mapa
        const bounds = L.latLngBounds([currentPos, [targetLat, targetLng]]);
        map.fitBounds(bounds, {
            padding: [50, 50]
        });

        // Calcular distancia simple
        const distance = map.distance(currentPos, [targetLat, targetLng]);

        // Mostrar panel de navegación simple
        document.getElementById('nav-instruction').textContent = 'Dirígete hacia el destino marcado';
        document.getElementById('nav-distance').textContent = formatDistance(distance);
        document.getElementById('nav-eta').textContent = 'Navegación simplificada';
        document.getElementById('navigation-panel').classList.add('active');



        //showNotification(`Navegación simple a: ${delivery.job_details?.cliente || 'Cliente'}`, 'success');
    }

    // Actualizar entrega actual
    function updateCurrentDelivery(deliveryId) {
        // Remover clase 'current' de todas las entregas
        document.querySelectorAll('.delivery-item').forEach(item => {
            item.classList.remove('current');
            const status = item.querySelector('.delivery-status');
            if (status && status.classList.contains('status-current')) {
                status.className = 'delivery-status status-pending';
                status.textContent = 'Pendiente';
            }
        });

        // Añadir clase 'current' a la entrega actual
        const currentItem = document.getElementById(`delivery-${deliveryId}`);
        if (currentItem) {
            currentItem.classList.add('current');
            const status = currentItem.querySelector('.delivery-status');
            if (status) {
                status.className = 'delivery-status status-current';
                status.textContent = 'En ruta';
            }

            // Hacer scroll para mostrar la entrega actual
            currentItem.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Actualizar estadísticas
        const index = driverRoute.steps.findIndex(step => step.job == deliveryId);
        document.getElementById('current-delivery').textContent = index >= 0 ? index + 1 : '-';
    }

    // Actualizar panel de navegación
    function updateNavigationPanel() {
        if (!currentRoute || !currentInstructions) return;

        const instruction = currentInstructions[currentInstructionIndex];
        if (!instruction) return;

        document.getElementById('nav-instruction').textContent = instruction.text || 'Continúa recto';
        document.getElementById('nav-distance').textContent = formatDistance(instruction.distance);
        document.getElementById('nav-eta').textContent = `Llegada: ${formatTime(currentRoute.summary.totalTime)}`;
    }

    // Marcar entrega
    function markDelivery(deliveryId, status) {
        const deliveryItem = document.getElementById(`delivery-${deliveryId}`);
        if (!deliveryItem) return;

        deliveryStatuses[deliveryId] = status;

        // Actualizar interfaz
        deliveryItem.classList.remove('completed', 'returned', 'current');
        if (status === 'completed') {
            deliveryItem.classList.add('completed');
            updateDeliveryItemUI(deliveryItem, 'Entregado', 'status-completed');
        } else if (status === 'returned') {
            deliveryItem.classList.add('returned');
            updateDeliveryItemUI(deliveryItem, 'Devuelto', 'status-returned');
        }

        // Deshabilitar botones
        const buttons = deliveryItem.querySelectorAll('.delivery-btn');
        buttons.forEach(btn => {
            if (btn.textContent.includes('Entregado') || btn.textContent.includes('Devolución')) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            }
        });

        updateStats();
        updateMapMarker(deliveryId, status);

        const statusText = status === 'completed' ? 'entregado' : 'devuelto';
        //showNotification(`Entrega marcada como ${statusText}`, 'success');

        sendDeliveryUpdate(deliveryId, status);

        // Si estamos navegando, buscar siguiente entrega
        if (isNavigating) {
            setTimeout(() => {
                findNextDelivery();
            }, 2000);
        }
    }

    // Actualizar UI del item de entrega
    function updateDeliveryItemUI(deliveryItem, statusText, statusClass) {
        const statusElement = deliveryItem.querySelector('.delivery-status');
        if (statusElement) {
            statusElement.textContent = statusText;
            statusElement.className = `delivery-status ${statusClass}`;
        }
    }

    // Actualizar marcador en el mapa
    function updateMapMarker(deliveryId, status) {
        // Esta función actualizaría el marcador en el mapa
        // Recargar marcadores para mostrar el nuevo estado
        if (currentMarkers.length > 0) {
            clearPreviousRoute();
            addDeliveryMarkers();
        }
    }

    // Enviar actualización al servidor
    function sendDeliveryUpdate(deliveryId, status) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) return;

        const serverStatus = status === 'completed' ? 'entregado' : 'devuelto';

        fetch('/update-delivery-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    delivery_id: deliveryId,
                    status: serverStatus,
                    driver_id: driverData.id,
                    latitude: currentLocationMarker ? currentLocationMarker.getLatLng().lat : null,
                    longitude: currentLocationMarker ? currentLocationMarker.getLatLng().lng : null,
                    timestamp: new Date().toISOString()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Estado actualizado en servidor');
                } else {
                    //showNotification('Error sincronizando con servidor', 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                //showNotification('Error de conexión con servidor', 'warning');
            });
    }

    // Actualizar estadísticas
    function updateDeliveryStats() {
        const completed = Object.values(deliveryStatuses).filter(status => status === 'completed').length;
        const returned = Object.values(deliveryStatuses).filter(status => status === 'returned').length;
        const total = Object.keys(deliveryStatuses).length;
        const pending = total - completed - returned;

        document.getElementById('completed-count').textContent = completed;
        document.getElementById('pending-count').textContent = pending;
    }

    // Alias para updateDeliveryStats
    function updateStats() {
        updateDeliveryStats();
    }

    // Centrar en ubicación
    function centerOnLocation() {
        if (currentLocationMarker) {
            map.setView(currentLocationMarker.getLatLng(), 18, {
                animate: true
            });
            //showNotification('Vista centrada en tu ubicación', 'success');
        } else {
            //showNotification('Ubicación no disponible', 'warning');
            initializeGPS();
        }
    }

    // Toggle seguimiento de ubicación
    function toggleLocationTracking() {
        locationTracking = !locationTracking;
        const btn = document.getElementById('location-btn');

        if (locationTracking) {
            btn.classList.add('active');
            btn.title = 'Seguimiento GPS ON';
            // Iniciar seguimiento más frecuente
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }
            watchId = navigator.geolocation.watchPosition(
                handleLocationSuccess,
                handleLocationError, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 1000
                }
            );
            //showNotification('Seguimiento GPS activado', 'success');
        } else {
            btn.classList.remove('active');
            btn.title = 'Seguimiento GPS OFF';
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            //showNotification('Seguimiento GPS desactivado', 'info');
        }
    }

    // Toggle vista de tráfico (simulado)
    function toggleTrafficView() {
        trafficView = !trafficView;
        const btn = document.getElementById('traffic-btn');

        if (trafficView) {
            btn.classList.add('active');
            btn.title = 'Vista de tráfico ON';
            //showNotification('Vista de tráfico activada', 'info');
        } else {
            btn.classList.remove('active');
            btn.title = 'Vista de tráfico OFF';
            //showNotification('Vista de tráfico desactivada', 'info');
        }
    }

    // Toggle auto zoom
    function toggleZoomMode() {
        autoZoom = !autoZoom;
        const btn = document.getElementById('zoom-btn');

        if (autoZoom) {
            btn.classList.add('active');
            btn.title = 'Auto Zoom ON';
            //showNotification('Auto zoom activado', 'success');
        } else {
            btn.classList.remove('active');
            btn.title = 'Auto Zoom OFF';
            //showNotification('Auto zoom desactivado', 'info');
        }
    }

    // Alerta de emergencia
    function emergencyAlert() {
        if (confirm('¿Confirmas que quieres enviar una ALERTA DE EMERGENCIA?')) {
            //showNotification('🚨 ENVIANDO ALERTA DE EMERGENCIA...', 'error');

            const location = currentLocationMarker ? currentLocationMarker.getLatLng() : null;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (token && location) {
                fetch('/emergency', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        driver_id: driverData.id,
                        location: [location.lat, location.lng],
                        timestamp: new Date().toISOString()
                    })
                }).catch(error => console.error('Error enviando alerta:', error));
            }

            setTimeout(() => {
                //showNotification('🚨 ALERTA DE EMERGENCIA ENVIADA', 'error');
            }, 1000);
        }
    }

    // Cargar ruta (función auxiliar)
    function loadRoute() {
        //showNotification('Cargando ruta...', 'info');
        location.reload();
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Panel colapsible en móviles - inicializar correctamente
        if (window.innerWidth <= 768) {
            const content = document.getElementById('panel-content');
            const icon = document.getElementById('toggle-icon');

            if (content && icon) {
                content.classList.add('collapsed');
                icon.textContent = '▲';
                icon.classList.add('collapsed');
            }
        }

        // Añadir event listener para redimensionamiento
        window.addEventListener('resize', function() {
            const content = document.getElementById('panel-content');
            const icon = document.getElementById('toggle-icon');

            if (window.innerWidth > 768) {
                // En desktop, siempre mostrar el panel
                if (content) content.classList.remove('collapsed');
                if (icon) {
                    icon.textContent = '▼';
                    icon.classList.remove('collapsed');
                }
            }
        });
    }

    // Toggle panel
    function togglePanel() {
        const content = document.getElementById('panel-content');
        const icon = document.getElementById('toggle-icon');

        if (!content || !icon) {
            console.error('Elementos del panel no encontrados');
            return;
        }

        // Alternar clase collapsed
        if (content.classList.contains('collapsed')) {
            // Mostrar panel
            content.classList.remove('collapsed');
            icon.textContent = '▼';
            icon.classList.remove('collapsed');
            console.log('Panel expandido');
        } else {
            // Ocultar panel
            content.classList.add('collapsed');
            icon.textContent = '▲';
            icon.classList.add('collapsed');
            console.log('Panel colapsado');
        }
    }

    // Funciones de utilidad
    function formatDistance(meters) {
        if (meters < 1000) {
            return Math.round(meters) + ' m';
        } else {
            return (meters / 1000).toFixed(1) + ' km';
        }
    }

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else {
            return `${minutes} min`;
        }
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: none; border: none; color: inherit; font-size: 16px; cursor: pointer; padding: 0 0 0 10px;">
                        ✕
                    </button>
                </div>
            `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.remove();
            }
        }, 4000);
    }

    function hideLoading() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.style.opacity = '0';
            setTimeout(() => {
                loading.style.display = 'none';
            }, 500);
        }
    }

    // Actualizar progreso de navegación (para futuras mejoras)
    function updateNavigationProgress(lat, lng) {
        // Esta función se puede expandir para calcular progreso en tiempo real
        if (currentRoute && currentInstructions) {
            // Lógica para actualizar instrucciones basada en la ubicación actual
            updateNavigationPanel();
        }
    }

    function updateOriginMarker(lat, lng) {
        const originMarkers = currentMarkers.filter(marker =>
            marker.options && marker.options.icon &&
            marker.options.icon.options.className === 'navigation-origin-marker'
        );

        if (originMarkers.length > 0) {
            originMarkers[0].setLatLng([lat, lng]);
        }
    }

    // Cleanup al salir
    window.addEventListener('beforeunload', function() {
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
        }
    });

    console.log('🎯 Sistema de navegación estilo Waze listo');
    </script>
</body>

</html>