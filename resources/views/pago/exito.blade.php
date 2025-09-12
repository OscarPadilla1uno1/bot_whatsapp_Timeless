<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if($pago->estado_pago === 'confirmado')
            Pago Completado
        @elseif($pago->estado_pago === 'pendiente')
            Pago Pendiente
        @else
            Estado de Pago
        @endif
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Animaciones base */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(3deg);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        @keyframes checkmark {
            0% {
                stroke-dashoffset: 100;
            }

            100% {
                stroke-dashoffset: 0;
            }
        }

        /* Animaciones para burbujas (pago aprobado) */
        @keyframes bubble1 {
            0% {
                transform: translateY(100vh) scale(0);
            }

            10% {
                transform: translateY(90vh) scale(0.1);
            }

            50% {
                transform: translateY(50vh) scale(0.4);
            }

            100% {
                transform: translateY(-10vh) scale(0);
                opacity: 0;
            }
        }

        @keyframes bubble2 {
            0% {
                transform: translateY(100vh) scale(0);
            }

            15% {
                transform: translateY(85vh) scale(0.2);
            }

            60% {
                transform: translateY(40vh) scale(0.6);
            }

            100% {
                transform: translateY(-10vh) scale(0);
                opacity: 0;
            }
        }

        @keyframes bubble3 {
            0% {
                transform: translateY(100vh) scale(0);
            }

            20% {
                transform: translateY(80vh) scale(0.3);
            }

            70% {
                transform: translateY(30vh) scale(0.5);
            }

            100% {
                transform: translateY(-10vh) scale(0);
                opacity: 0;
            }
        }

        /* Animaciones para ondas (pago pendiente) */
        @keyframes wave {
            0% {
                transform: scale(0);
                opacity: 1;
            }

            100% {
                transform: scale(4);
                opacity: 0;
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Clases de utilidad */
        .fade-in {
            animation: fadeInUp 0.8s ease-out;
        }

        .pulse-slow {
            animation: pulse 2s infinite;
        }

        .checkmark-path {
            stroke-dasharray: 100;
            animation: checkmark 1s ease-in-out 0.5s both;
        }

        /* Burbujas para estado APPROVED */
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .bubble:nth-child(1) {
            width: 40px;
            height: 40px;
            left: 10%;
            animation: bubble1 8s infinite linear;
            animation-delay: 0s;
        }

        .bubble:nth-child(2) {
            width: 60px;
            height: 60px;
            left: 25%;
            animation: bubble2 10s infinite linear;
            animation-delay: 2s;
        }

        .bubble:nth-child(3) {
            width: 30px;
            height: 30px;
            left: 40%;
            animation: bubble3 12s infinite linear;
            animation-delay: 4s;
        }

        .bubble:nth-child(4) {
            width: 50px;
            height: 50px;
            left: 60%;
            animation: bubble1 9s infinite linear;
            animation-delay: 1s;
        }

        .bubble:nth-child(5) {
            width: 35px;
            height: 35px;
            left: 80%;
            animation: bubble2 11s infinite linear;
            animation-delay: 3s;
        }

        .bubble:nth-child(6) {
            width: 45px;
            height: 45px;
            left: 90%;
            animation: bubble3 7s infinite linear;
            animation-delay: 5s;
        }

        /* Ondas para estado PENDING */
        .wave {
            position: absolute;
            border: 2px solid rgba(255, 165, 0, 0.3);
            border-radius: 50%;
            animation: wave 3s infinite;
        }

        .wave:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 15%;
            animation-delay: 0s;
        }

        .wave:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 20%;
            animation-delay: 1s;
        }

        .wave:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 30%;
            left: 70%;
            animation-delay: 2s;
        }

        /* Gradientes personalizados */
        .bg-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 25%, #047857 50%, #065f46 75%, #064e3b 100%);
        }

        .bg-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 25%, #b45309 50%, #92400e 75%, #78350f 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen relative overflow-hidden">

    @if($pago->estado_pago === 'confirmado')
        <div id="approved-state" class="bg-success w-full h-full absolute inset-0">
            <!-- Burbujas animadas para estado aprobado -->
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
        </div>
    @elseif($pago->estado_pago === 'pendiente')
        <div id="pending-state" class="bg-pending w-full h-full absolute inset-0">
            <!-- Ondas animadas para estado pendiente -->
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
        </div>
    @else
        <div class="bg-gray-400 w-full h-full absolute inset-0"></div>
    @endif

    <!-- Card principal -->
    <div class="relative glass-card rounded-3xl shadow-2xl p-12 max-w-lg text-center fade-in z-10">

        <!-- Logo del restaurante -->
        <div class="flex justify-center mb-8">
            <div
                class="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-lg pulse-slow">
                <!-- En tu Laravel: <img src="{{ asset('images/logo.jpeg') }}" alt="Logo del restaurante" class="h-25 w-25 rounded-full"> -->
                <div
                    class="w-24 h-24 bg-white rounded-full flex items-center justify-center text-white text-2xl font-bold">
                    <img src="{{ asset('images/logo.jpeg') }}" alt="Logo del restaurante"
                        class="h-25 w-25 rounded-full">
                </div>
            </div>
        </div>

        {{-- Contenido dinámico --}}
        @if($pago->estado_pago === 'confirmado')
            <div>
                <div class="flex justify-center mb-6">
                    <div class="bg-green-100 p-4 rounded-full shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path class="checkmark-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-4">¡Pago Completado!</h1>
                <p class="text-lg text-gray-600 mb-8">Su transacción ha sido procesada exitosamente.</p>
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                    <p class="text-green-800 font-semibold">✓ Transacción Exitosa</p>
                    <p class="text-green-600 text-sm">Su pedido será procesado inmediatamente</p>
                </div>
            </div>

        @elseif($pago->estado_pago === 'pendiente')
            <div>
                <div class="flex justify-center mb-6">
                    <div class="bg-orange-100 p-4 rounded-full shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-orange-600 animate-spin"
                            style="animation-duration: 2s;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Pago Pendiente</h1>
                <p class="text-lg text-gray-600 mb-8">Su transacción está siendo procesada. Por favor espere la
                    confirmación.</p>
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
                    <p class="text-orange-800 font-semibold">⏳ Procesando Pago...</p>
                    <p class="text-orange-600 text-sm">Recibirá una confirmación en breve</p>
                </div>
            </div>

        @else
            <div>
                <h1 class="text-4xl font-bold text-gray-800 mb-4">Estado de pago: {{ $pago->estado_pago }}</h1>
                <p class="text-lg text-gray-600 mb-8">Consulte con soporte para más detalles.</p>
            </div>
        @endif

        <span class="text-sm text-gray-500 mt-2 block">Referencia: {{ $pago->referencia_transaccion }}</span>

    </div>

    <script>
        // Animación de entrada suave
        window.addEventListener('load', function () {
            document.querySelector('.fade-in').style.opacity = '1';
        });
    </script>
</body>

</html>