<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <style>
        /* CSS para el botón con gradiente - CENTRADO Y MÁS GRANDE */
        .vehicle-btn-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50vh;
            padding: 40px 20px;
        }

        .vehicle-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 20px 40px;
            font-size: 24px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            min-width: 250px;
            justify-content: center;
        }

        .vehicle-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .vehicle-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            text-decoration: none;
            color: white;
        }

        .vehicle-btn:hover::before {
            left: 100%;
        }

        .vehicle-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .vehicle-btn-icon {
            width: 28px;
            height: 28px;
            fill: currentColor;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Contenedor centrado para el botón -->
                    <div class="vehicle-btn-container">
                        <a href="{{ route('motorista.mi_ruta') }}" class="vehicle-btn">
                            <svg class="vehicle-btn-icon" viewBox="0 0 24 24">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                            </svg>
                            Ver Ruta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>