<?php

namespace App\Http\Controllers;

use App\Models\UserRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VroomController extends Controller
{
    private $vroomServerUrl = 'http://154.38.191.25:3000';

    public function index()
    {
        return view('vehicle.show');
    }

    public function optimizeDeliveryRoutes()
    {
        try {
            // 1. Obtener pedidos despachados del día
            $today = Carbon::today();
            $pedidosDespachados = DB::table('pedidos')
                ->select([
                    'pedidos.id',
                    'pedidos.latitud',
                    'pedidos.longitud',
                    'pedidos.cliente_id',
                    'pedidos.total',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono'
                ])
                ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->where('pedidos.estado', 'despachado')
                ->whereDate('pedidos.fecha_pedido', $today)
                ->get();

            if ($pedidosDespachados->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay pedidos despachados para el día de hoy',
                    'data' => []
                ]);
            }

            // 2. Obtener motoristas disponibles
            $motoristas = DB::table('users')
                ->select(['id', 'name', 'email'])
                ->where('role', 'motorista')
                ->orWhere('is_driver', true)
                ->where('active', true)
                ->get();

            if ($motoristas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay motoristas disponibles',
                    'data' => []
                ]);
            }

            // 3. Preparar datos para VROOM
            $vroomData = $this->prepareVroomData($pedidosDespachados, $motoristas);

            // 4. Enviar solicitud a VROOM
            $vroomResponse = $this->callVroomAPI($vroomData);

            if (!$vroomResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al optimizar rutas: ' . $vroomResponse['error'],
                    'data' => []
                ]);
            }

            // 5. Procesar respuesta de VROOM y formatear para el frontend
            $processedRoutes = $this->processVroomResponse(
                $vroomResponse['data'], 
                $motoristas, 
                $pedidosDespachados
            );

            // 6. Guardar rutas optimizadas en la base de datos
            $this->saveOptimizedRoutes($processedRoutes);

            return response()->json([
                'success' => true,
                'message' => 'Rutas optimizadas correctamente',
                'data' => $processedRoutes,
                'summary' => [
                    'total_pedidos' => $pedidosDespachados->count(),
                    'total_motoristas' => $motoristas->count(),
                    'rutas_generadas' => count($processedRoutes)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }

    private function prepareVroomData($pedidos, $motoristas)
    {
        // Coordenadas del restaurante (punto de inicio/retorno)
        $restauranteCoords = [
            'lat' => 14.0723, // Tegucigalpa - ajusta según tu ubicación
            'lng' => -87.1921
        ];

        // Preparar vehículos (uno por motorista)
        $vehicles = [];
        foreach ($motoristas as $index => $motorista) {
            $vehicles[] = [
                'id' => $motorista->id,
                'start' => [$restauranteCoords['lng'], $restauranteCoords['lat']], // [lng, lat]
                'end' => [$restauranteCoords['lng'], $restauranteCoords['lat']],   // Retorna al restaurante
                'capacity' => [10], // Capacidad máxima de pedidos
                'skills' => [1],    // Habilidades del motorista
                'time_window' => [0, 28800] // 8 horas de trabajo (en segundos)
            ];
        }

        // Preparar trabajos (deliveries)
        $jobs = [];
        foreach ($pedidos as $index => $pedido) {
            $jobs[] = [
                'id' => $pedido->id,
                'location' => [(float)$pedido->longitud, (float)$pedido->latitud], // [lng, lat]
                'delivery' => [1], // Cantidad a entregar
                'skills' => [1],   // Requiere motorista con skill 1
                'service' => 300,  // 5 minutos por entrega
                'description' => "Pedido #{$pedido->id} - {$pedido->cliente_nombre}",
                'priority' => $this->calculatePriority($pedido)
            ];
        }

        return [
            'vehicles' => $vehicles,
            'jobs' => $jobs,
            'options' => [
                'g' => true // Usar geometría para rutas detalladas
            ]
        ];
    }

    private function calculatePriority($pedido)
    {
        // Calcular prioridad basada en el total del pedido
        // Pedidos más grandes tienen mayor prioridad
        if ($pedido->total >= 1000) return 100;
        if ($pedido->total >= 500) return 75;
        if ($pedido->total >= 200) return 50;
        return 25;
    }

    private function callVroomAPI($data)
    {
        try {
            $response = Http::timeout(30)
                ->post($this->vroomServerUrl, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function processVroomResponse($vroomData, $motoristas, $pedidos)
    {
        $processedRoutes = [];
        $pedidosMap = $pedidos->keyBy('id');
        $motoristasMap = $motoristas->keyBy('id');

        foreach ($vroomData['routes'] as $route) {
            $vehicleId = $route['vehicle'];
            $motorista = $motoristasMap->get($vehicleId);

            if (!$motorista) continue;

            $routeSteps = [];
            $assignedOrders = [];
            $totalDistance = 0;
            $totalDuration = 0;

            foreach ($route['steps'] as $step) {
                if ($step['type'] === 'job') {
                    $pedido = $pedidosMap->get($step['job']);
                    if ($pedido) {
                        $assignedOrders[] = [
                            'pedido_id' => $pedido->id,
                            'cliente_nombre' => $pedido->cliente_nombre,
                            'cliente_telefono' => $pedido->telefono,
                            'latitud' => $pedido->latitud,
                            'longitud' => $pedido->longitud,
                            'total' => $pedido->total,
                            'orden_entrega' => count($assignedOrders) + 1
                        ];
                    }
                }

                $routeSteps[] = [
                    'type' => $step['type'],
                    'location' => $step['location'],
                    'arrival' => $step['arrival'] ?? 0,
                    'duration' => $step['duration'] ?? 0,
                    'distance' => isset($step['distance']) ? $step['distance'] : 0,
                    'description' => $this->getStepDescription($step, $pedidosMap)
                ];
            }

            // Calcular totales
            if (isset($route['distance'])) $totalDistance = $route['distance'];
            if (isset($route['duration'])) $totalDuration = $route['duration'];

            $processedRoutes[] = [
                'motorista_id' => $vehicleId,
                'motorista_nombre' => $motorista->name,
                'motorista_email' => $motorista->email,
                'pedidos_asignados' => $assignedOrders,
                'total_pedidos' => count($assignedOrders),
                'ruta_pasos' => $routeSteps,
                'distancia_total_metros' => $totalDistance,
                'distancia_total_km' => round($totalDistance / 1000, 2),
                'duracion_total_segundos' => $totalDuration,
                'duracion_total_minutos' => round($totalDuration / 60, 0),
                'duracion_formateada' => $this->formatDuration($totalDuration),
                'geometria' => $route['geometry'] ?? null
            ];
        }

        return $processedRoutes;
    }

    private function getStepDescription($step, $pedidosMap)
    {
        if ($step['type'] === 'start') {
            return 'Inicio - Restaurante';
        } elseif ($step['type'] === 'end') {
            return 'Fin - Retorno al Restaurante';
        } elseif ($step['type'] === 'job' && isset($step['job'])) {
            $pedido = $pedidosMap->get($step['job']);
            return $pedido ? "Entrega - {$pedido->cliente_nombre}" : 'Entrega';
        }
        return 'Paso de ruta';
    }

    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    private function saveOptimizedRoutes($routes)
    {
        foreach ($routes as $route) {
            // Guardar o actualizar ruta del motorista
            UserRoute::updateOrCreate(
                ['user_id' => $route['motorista_id']],
                [
                    'origin_lat' => 14.0723, // Restaurante
                    'origin_lng' => -87.1921,
                    'destination_lat' => 14.0723, // Retorna al restaurante
                    'destination_lng' => -87.1921,
                    'origin_address' => 'Restaurante - Punto de Partida',
                    'destination_address' => 'Restaurante - Punto de Retorno',
                    'route_data' => [
                        'tipo' => 'ruta_optimizada_vroom',
                        'fecha_generacion' => now(),
                        'pedidos_asignados' => $route['pedidos_asignados'],
                        'pasos_ruta' => $route['ruta_pasos'],
                        'distancia_km' => $route['distancia_total_km'],
                        'duracion_minutos' => $route['duracion_total_minutos'],
                        'geometria' => $route['geometria']
                    ]
                ]
            );
        }
    }

    // Método para obtener la ruta optimizada de un motorista específico
    public function getDriverRoute($driverId)
    {
        $route = UserRoute::where('user_id', $driverId)->first();
        
        if (!$route || !isset($route->route_data['tipo']) || 
            $route->route_data['tipo'] !== 'ruta_optimizada_vroom') {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ruta optimizada para este motorista'
            ]);
        }

        return response()->json([
            'success' => true,
            'route' => $route,
            'motorista' => [
                'id' => $driverId,
                'nombre' => DB::table('users')->where('id', $driverId)->value('name')
            ]
        ]);
    }

    // Método para obtener resumen de todas las rutas del día
    public function getDailySummary()
    {
        $today = Carbon::today();
        
        $summary = [
            'pedidos_despachados' => DB::table('pedidos')
                ->where('estado', 'despachado')
                ->whereDate('fecha_pedido', $today)
                ->count(),
            'motoristas_con_rutas' => UserRoute::whereJsonContains('route_data->tipo', 'ruta_optimizada_vroom')
                ->whereDate('updated_at', $today)
                ->count(),
            'total_distancia_km' => 0,
            'total_duracion_minutos' => 0
        ];

        // Calcular totales
        $routes = UserRoute::whereJsonContains('route_data->tipo', 'ruta_optimizada_vroom')
            ->whereDate('updated_at', $today)
            ->get();

        foreach ($routes as $route) {
            if (isset($route->route_data['distancia_km'])) {
                $summary['total_distancia_km'] += $route->route_data['distancia_km'];
            }
            if (isset($route->route_data['duracion_minutos'])) {
                $summary['total_duracion_minutos'] += $route->route_data['duracion_minutos'];
            }
        }

        $summary['total_distancia_km'] = round($summary['total_distancia_km'], 2);

        return response()->json([
            'success' => true,
            'summary' => $summary
        ]);
    }
     public function store(Request $request)
    {
        $request->validate([
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'destination_lat' => 'required|numeric',
            'destination_lng' => 'required|numeric',
            'origin_address' => 'nullable|string',
            'destination_address' => 'nullable|string',
            'route_data' => 'nullable|array'
        ]);

        $userRoute = UserRoute::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'origin_lat' => $request->origin_lat,
                'origin_lng' => $request->origin_lng,
                'destination_lat' => $request->destination_lat,
                'destination_lng' => $request->destination_lng,
                'origin_address' => $request->origin_address,
                'destination_address' => $request->destination_address,
                'route_data' => $request->route_data
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ruta guardada correctamente',
            'route' => $userRoute
        ]);
    }

    public function show()
    {
        $userRoute = UserRoute::where('user_id', Auth::id())->first();
        
        if (!$userRoute) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ruta para este usuario'
            ]);
        }

        return response()->json([
            'success' => true,
            'route' => $userRoute
        ]);
    }

    public function destroy()
    {
        $deleted = UserRoute::where('user_id', Auth::id())->delete();
        
        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Ruta eliminada correctamente' : 'No había ruta que eliminar'
        ]);
    }
}