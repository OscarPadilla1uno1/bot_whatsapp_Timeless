<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Completado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(3deg); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes checkmark {
            0% { stroke-dashoffset: 100; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes ripple {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(4); opacity: 0; }
        }
        .float { animation: float 6s ease-in-out infinite; }
        .fade-in { animation: fadeInUp 0.8s ease-out; }
        .checkmark-path { 
            stroke-dasharray: 100; 
            animation: checkmark 1s ease-in-out 0.5s both;
        }
        .ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            background: rgba(34, 197, 94, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            animation: ripple 2s infinite;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gradient-to-br from-emerald-400 via-green-500 to-teal-600 relative overflow-hidden">
    <!-- Elementos decorativos animados -->
    <div class="absolute top-20 left-20 w-32 h-32 bg-white/10 rounded-full float" style="animation-delay: 0s;"></div>
    <div class="absolute top-40 right-32 w-24 h-24 bg-white/15 rounded-full float" style="animation-delay: 1s;"></div>
    <div class="absolute bottom-32 left-40 w-20 h-20 bg-white/10 rounded-full float" style="animation-delay: 2s;"></div>
    <div class="absolute bottom-20 right-20 w-16 h-16 bg-white/20 rounded-full float" style="animation-delay: 0.5s;"></div>
    
    <!-- Partículas de fondo -->
    <div class="absolute inset-0">
        <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-white/30 rounded-full animate-ping" style="animation-delay: 0s;"></div>
        <div class="absolute top-3/4 right-1/4 w-1 h-1 bg-white/40 rounded-full animate-ping" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-3/4 w-1.5 h-1.5 bg-white/35 rounded-full animate-ping" style="animation-delay: 2s;"></div>
    </div>

    <!-- Card principal -->
    <div class="relative bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl p-12 max-w-lg text-center border border-white/20 fade-in">
        <!-- Logo del restaurante -->
        <div class="flex justify-center mb-8">
            <div class="w-32 h-32 bg-gradient-to-br from-emerald-100 to-green-200 rounded-full flex items-center justify-center shadow-lg">
                <img src="{{ asset('images/logo.jpeg') }}" alt="Logo del restaurante"
                    class="h-25 w-25 rounded-full">
            </div>
        </div>
        
        <!-- Icono de éxito con animación -->
        <div class="flex justify-center mb-6 relative ripple">
            <div class="bg-green-100 p-4 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path class="checkmark-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
        
        <!-- Mensaje principal -->
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Pago Completado</h1>
        <p class="text-lg text-gray-600 mb-8 leading-relaxed">
            Su transacción ha sido procesada exitosamente.<br>
            <span class="text-sm text-gray-500 mt-2 block">Puede cerrar esta ventana.</span>
        </p>
        
        
    </div>
</body>
</html>