<?php

namespace App\Http\Controllers;

use App\Models\UserRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
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
=======
use Illuminate\Support\Facades\Log;
use App\Models\User;

class VroomController extends Controller
{
    public function index(Request $request)
    {
        $jobs = $this->getValidJobs();

        if (empty($jobs)) {
            return back()->with('error', 'No hay pedidos válidos para procesar');
        }

        // Obtener vehículos desde usuarios con permiso "motorista"
        $vehicles = $this->getVehiclesFromDrivers();

        if (empty($vehicles)) {
            return back()->with('error', 'No hay motoristas disponibles');
        }

        $data = [
            "vehicles" => $vehicles,
            "jobs" => $jobs,
            "options" => ["g" => true]
        ];

        $vroomUrl = 'http://154.38.191.25:3000';
        $response = Http::timeout(30)->post($vroomUrl, $data);

        if ($response->failed()) {
            Log::error('Error en la respuesta de VROOM', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return back()->with('error', 'Error al contactar el servidor VROOM.');
        }

        $result = $response->json();

        if (!isset($result['routes'])) {
            Log::error('Respuesta inesperada de VROOM', $result);
            return back()->with('error', 'Error al calcular rutas. Verifica los logs.');
        }

        // Asignar rutas a motoristas
        $routes = $this->assignRoutesToDrivers($result['routes'], $vehicles, $jobs);

        if (empty($routes)) {
            return back()->with('error', 'No se generaron rutas con geometría válida');
        }

        return view('vehicle.show', [
            'routes' => $routes,
            'vehicles' => $vehicles,
            'jobs' => $jobs
        ]);
    }

    /**
     * Vista individual para motorista
     */
    public function showDriverRoute(Request $request, $userId = null)
    {
        // Si no se especifica userId, usar el del usuario autenticado
        if (!$userId) {
            $userId = auth()->id();
        }

        // Verificar que el usuario sea motorista
        $driver = User::where('id', $userId)
            ->whereHas('permissions', function($query) {
                $query->where('name', 'Motorista');
            })
            ->first();

        if (!$driver) {
            return back()->with('error', 'Usuario no encontrado o no es motorista');
        }

        // Verificar permisos: solo el mismo motorista o un admin pueden ver la ruta
        if (auth()->id() != $userId && !auth()->user()->can('Administrador')) {
            return back()->with('error', 'No tienes permisos para ver esta ruta');
        }

        $jobs = $this->getValidJobs();

        if (empty($jobs)) {
            return view('vehicle.driver', [
                'driver' => $driver,
                'route' => null,
                'jobs' => []
            ])->with('error', 'No hay pedidos válidos para procesar');
        }

        $vehicles = $this->getVehiclesFromDrivers();
        
        // Encontrar el vehículo del motorista
        $driverVehicle = collect($vehicles)->firstWhere('driver_id', $userId);

        if (!$driverVehicle) {
            return view('vehicle.driver', [
                'driver' => $driver,
                'route' => null,
                'jobs' => []
            ])->with('error', 'No se encontró vehículo asignado');
        }

        $data = [
            "vehicles" => $vehicles,
            "jobs" => $jobs,
            "options" => ["g" => true]
        ];

        $vroomUrl = 'http://154.38.191.25:3000';
        $response = Http::timeout(30)->post($vroomUrl, $data);

        if ($response->failed()) {
            Log::error('Error en la respuesta de VROOM para motorista', [
                'driver_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return view('vehicle.driver', [
                'driver' => $driver,
                'route' => null,
                'jobs' => []
            ])->with('error', 'Error al contactar el servidor VROOM.');
        }

        $result = $response->json();
        $routes = $this->assignRoutesToDrivers($result['routes'], $vehicles, $jobs);

        // Encontrar la ruta del motorista específico
        $driverRoute = collect($routes)->firstWhere('driver_id', $userId);

        return view('vehicle.driver', [
            'driver' => $driver,
            'route' => $driverRoute,
            'jobs' => $jobs
        ]);
    }

    /**
     * Vista para admin - ver todas las rutas de motoristas
     */
    public function adminViewDriverRoutes(Request $request)
    {
        $jobs = $this->getValidJobs();
        $vehicles = $this->getVehiclesFromDrivers();

        if (empty($vehicles)) {
            return view('admin.driver-routes', [
                'drivers' => [],
                'routes' => [],
                'jobs' => []
            ])->with('error', 'No hay motoristas disponibles');
        }

        if (empty($jobs)) {
            return view('admin.driver-routes', [
                'drivers' => $vehicles,
                'routes' => [],
                'jobs' => []
            ])->with('error', 'No hay pedidos válidos para procesar');
        }

        $data = [
            "vehicles" => $vehicles,
            "jobs" => $jobs,
            "options" => ["g" => true]
        ];

        $vroomUrl = 'http://154.38.191.25:3000';
        $response = Http::timeout(30)->post($vroomUrl, $data);

        if ($response->failed()) {
            Log::error('Error en la respuesta de VROOM para admin', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return view('admin.driver-routes', [
                'drivers' => $vehicles,
                'routes' => [],
                'jobs' => []
            ])->with('error', 'Error al contactar el servidor VROOM.');
        }

        $result = $response->json();
        $routes = $this->assignRoutesToDrivers($result['routes'], $vehicles, $jobs);

        return view('admin.driver-routes', [
            'drivers' => $vehicles,
            'routes' => $routes,
            'jobs' => $jobs
        ]);
    }

    /**
     * Obtener vehículos desde usuarios con permiso "Motorista"
     */
    private function getVehiclesFromDrivers()
    {
        $drivers = User::whereHas('permissions', function($query) {
            $query->where('name', 'Motorista');
        })->get();

        $vehicles = [];
        foreach ($drivers as $index => $driver) {
            $vehicles[] = [
                "id" => $index + 1,
                "driver_id" => $driver->id,
                "driver_name" => $driver->name,
                "driver_email" => $driver->email,
                "start" => [-87.1875, 14.0667], // Coordenadas base - punto de inicio
                // SIN punto "end" para evitar ruta de regreso
                "capacity" => [2]
            ];
>>>>>>> 499d89f (Hoy si; pinches rutas en tiempo real)
        }

        return $vehicles;
    }

    /**
     * Asignar rutas a motoristas
     */
    private function assignRoutesToDrivers($vroomRoutes, $vehicles, $jobs)
    {
        $routes = [];
        
        // Verificar que $vroomRoutes sea un array válido
        if (!is_array($vroomRoutes)) {
            Log::warning('vroomRoutes no es un array válido', ['data' => $vroomRoutes]);
            return [];
        }

        foreach ($vroomRoutes as $index => $route) {
            if (!isset($route['geometry'])) {
                Log::warning('Ruta sin geometría', $route);
                continue;
            }

            $vehicle = $vehicles[$index] ?? null;
            if (!$vehicle) {
                continue;
            }

            $routes[] = [
                'vehicle' => $index + 1,
                'driver_id' => $vehicle['driver_id'],
                'driver_name' => $vehicle['driver_name'],
                'driver_email' => $vehicle['driver_email'],
                'geometry' => $route['geometry'],
                'steps' => $this->formatSteps($route['steps'] ?? [], $jobs)
            ];
        }

        return $routes;
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

<<<<<<< HEAD
    private function calculatePriority($pedido)
    {
        // Calcular prioridad basada en el total del pedido
        // Pedidos más grandes tienen mayor prioridad
        if ($pedido->total >= 1000) return 100;
        if ($pedido->total >= 500) return 75;
        if ($pedido->total >= 200) return 50;
        return 25;
    }
=======
    /**
     * Obtener ruta optimizada usando servidor OSRM propio
     */
    public function getOptimizedRoute(Request $request)
    {
        try {
            $currentLocation = $request->input('current_location');
            $deliveryPoints = $request->input('delivery_points', []);
            
            if (!$currentLocation || empty($deliveryPoints)) {
                return response()->json(['error' => 'Ubicación actual y puntos de entrega requeridos'], 400);
            }

            // Usar servidor OSRM propio para cálculo de ruta
            $osrmUrl = 'http://154.38.191.25:5000';
            
            // Construir waypoints para OSRM
            $coordinates = [];
            $coordinates[] = $currentLocation[0] . ',' . $currentLocation[1]; // lng,lat actual
            
            foreach ($deliveryPoints as $point) {
                $coordinates[] = $point['lng'] . ',' . $point['lat']; // lng,lat
            }
            
            $coordinatesStr = implode(';', $coordinates);
            
            // Llamar a OSRM
            $osrmRequestUrl = $osrmUrl . '/route/v1/driving/' . $coordinatesStr . '?overview=full&geometries=polyline&steps=true';
            
            $response = Http::timeout(30)->get($osrmRequestUrl);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['routes']) && !empty($result['routes'])) {
                    return response()->json([
                        'success' => true,
                        'route' => $result['routes'][0],
                        'delivery_points' => $deliveryPoints,
                        'current_location' => $currentLocation,
                        'server_used' => 'OSRM_PROPIO'
                    ]);
                } else {
                    return response()->json(['error' => 'No se encontraron rutas'], 404);
                }
            } else {
                Log::error('Error en servidor OSRM propio', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $osrmRequestUrl
                ]);
                return response()->json(['error' => 'Error en servidor OSRM'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en getOptimizedRoute', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Método para seguimiento en tiempo real (actualizado para usar OSRM propio)
     */
    public function seguirRuta(Request $request)
    {
        try {
            // Obtener ubicación actual del usuario
            $currentLocation = $request->input('current_location');
            
            if (!$currentLocation || !is_array($currentLocation) || count($currentLocation) !== 2) {
                return response()->json(['error' => 'Ubicación actual requerida'], 400);
            }

            // Obtener trabajos válidos
            $jobs = $this->getValidJobs();
            
            if (empty($jobs)) {
                return response()->json(['error' => 'No hay trabajos disponibles'], 404);
            }

            // Crear datos para VROOM
            $vehicles = [[
                "id" => 1,
                "start" => $currentLocation,
                "end" => $currentLocation,
                "capacity" => [2]
            ]];

            $data = [
                "vehicles" => $vehicles,
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            // Llamar a VROOM
            $vroomUrl = 'http://154.38.191.25:3000';
            $response = Http::timeout(30)->post($vroomUrl, $data);

            if ($response->failed()) {
                Log::error('Error en VROOM seguimiento', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json(['error' => 'Error calculando ruta'], 500);
            }

            $result = $response->json();

            if (!isset($result['routes']) || empty($result['routes'])) {
                return response()->json(['error' => 'No se pudo calcular ruta'], 404);
            }

            // Formatear respuesta
            $route = $result['routes'][0];
            
            return response()->json([
                'routes' => [$route],
                'current_location' => $currentLocation,
                'timestamp' => now()->toISOString(),
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en seguirRuta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos GPS en tiempo real (para Leaflet Realtime)
     */
    public function getGpsData(Request $request)
    {
        try {
            // Obtener datos GPS simulados o reales
            $gpsData = [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => [
                            'id' => auth()->id(),
                            'driver_name' => auth()->user()->name,
                            'timestamp' => now()->toISOString(),
                            'speed' => rand(0, 50),
                            'heading' => rand(0, 360)
                        ],
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [
                                -87.1875 + (rand(-100, 100) / 10000), // Longitud
                                14.0667 + (rand(-100, 100) / 10000)   // Latitud
                            ]
                        ]
                    ]
                ]
            ];

            return response()->json($gpsData);

        } catch (\Exception $e) {
            Log::error('Error en getGpsData', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error obteniendo datos GPS'
            ], 500);
        }
    }

    /**
     * Manejar alertas de emergencia
     */
    public function emergencyAlert(Request $request)
    {
        try {
            $driverId = $request->input('driver_id');
            $location = $request->input('location');
            
            // Registrar la emergencia en logs
            Log::emergency('ALERTA DE EMERGENCIA', [
                'driver_id' => $driverId,
                'driver_name' => auth()->user()->name,
                'location' => $location,
                'timestamp' => now(),
                'ip' => $request->ip()
            ]);
            
            // Aquí puedes agregar lógica adicional como:
            // - Enviar notificación a supervisores
            // - Guardar en base de datos
            // - Enviar SMS/email
            
            return response()->json([
                'success' => true,
                'message' => 'Alerta de emergencia registrada',
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en emergencyAlert', [
                'error' => $e->getMessage(),
                'driver_id' => $request->input('driver_id')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando alerta de emergencia'
            ], 500);
        }
    }

    private function formatSteps($steps, $jobs)
    {
        // Verificar que $steps sea un array válido
        if (!is_array($steps)) {
            return [];
        }

        return array_map(function ($step) use ($jobs) {
            $formatted = [
                'type' => $step['type'] ?? 'unknown',
                'location' => $step['location'] ?? [0, 0]
            ];
>>>>>>> 499d89f (Hoy si; pinches rutas en tiempo real)

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
<<<<<<< HEAD

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
=======
>>>>>>> 499d89f (Hoy si; pinches rutas en tiempo real)
}