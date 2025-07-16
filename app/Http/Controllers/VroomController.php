<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
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

    private function getValidJobs()
    {
        $pedidos = DB::table('pedidos')
            ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
            ->select(
                'pedidos.id',
                'pedidos.latitud',
                'pedidos.longitud',
                'clientes.nombre as cliente_nombre'
            )
            ->whereNotNull('pedidos.latitud')
            ->whereNotNull('pedidos.longitud')
            ->get();

        return $pedidos->map(function ($pedido) {
            return [
                "id" => $pedido->id,
                "location" => [(float) $pedido->longitud, (float) $pedido->latitud],
                "delivery" => [1],
                "cliente" => $pedido->cliente_nombre
            ];
        })->toArray();
    }

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

            if (isset($step['id'])) {
                $formatted['job'] = $step['id'];
                $job = collect($jobs)->firstWhere('id', $step['id']);
                if ($job) {
                    $formatted['job_details'] = [
                        'cliente' => $job['cliente'] ?? 'Desconocido'
                    ];
                }
            }

            return $formatted;
        }, $steps);
    
    }
 public function calculateDistanceFromVehicle(Request $request): JsonResponse
    {
        try {
            // Validación de entrada
            $validated = $request->validate([
                'target_lat' => 'required|numeric|between:-90,90',
                'target_lng' => 'required|numeric|between:-180,180',
                'vehicle_id' => 'sometimes|integer|exists:users,id',
                'delivery_type' => 'sometimes|string|in:express,standard,economy',
                'traffic_condition' => 'sometimes|string|in:light,moderate,heavy',
                'weather_condition' => 'sometimes|string|in:clear,rain,storm',
                'use_vroom' => 'sometimes|boolean'
            ]);

            $user = Auth::user() ?? User::first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay usuarios en la base de datos'
                ], 500);
            }

            // Determinar vehículo a usar
            $vehicleId = $validated['vehicle_id'] ?? $user->id;
            
            // Si no es admin, solo puede usar su propio vehículo
            if (!$user->can('Administrador') && $vehicleId != $user->id) {
                $vehicleId = $user->id;
            }

            // Obtener posición inicial del vehículo
            $vehiclePosition = $this->getVehicleStartPositionSimple($vehicleId);

            // Usar VROOM por defecto
            $useVroom = $validated['use_vroom'] ?? true;
            
            if ($useVroom) {
                // Calcular usando VROOM
                $vroomResult = $this->calculateRouteWithVroom(
                    $vehiclePosition['lat'],
                    $vehiclePosition['lng'],
                    $validated['target_lat'],
                    $validated['target_lng'],
                    $vehicleId
                );
                
                if ($vroomResult['success']) {
                    $routeData = $vroomResult['data'];
                    
                    // Aplicar factores externos al tiempo base de VROOM
                    $adjustedTime = $this->adjustVroomTime(
                        $routeData['duration_seconds'],
                        $validated['delivery_type'] ?? 'standard',
                        $validated['traffic_condition'] ?? 'moderate',
                        $validated['weather_condition'] ?? 'clear'
                    );
                    
                    $vehicle = User::find($vehicleId);
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'vehicle' => [
                                'id' => $vehicleId,
                                'name' => $vehicle ? $vehicle->name : 'Vehículo ' . $vehicleId,
                                'position' => $vehiclePosition
                            ],
                            'target_coordinates' => [
                                'lat' => $validated['target_lat'],
                                'lng' => $validated['target_lng']
                            ],
                            'route_info' => [
                                'distance' => [
                                    'km' => $routeData['distance_km'],
                                    'meters' => $routeData['distance_meters'],
                                    'formatted' => $routeData['distance_formatted']
                                ],
                                'vroom_base_time' => [
                                    'seconds' => $routeData['duration_seconds'],
                                    'minutes' => $routeData['duration_minutes'],
                                    'formatted' => $routeData['duration_formatted']
                                ],
                                'adjusted_delivery_time' => $adjustedTime,
                                'geometry' => $routeData['geometry'],
                                'steps_count' => $routeData['steps_count']
                            ],
                            'calculation_details' => [
                                'method' => 'vroom_real_route',
                                'delivery_type' => $validated['delivery_type'] ?? 'standard',
                                'traffic_condition' => $validated['traffic_condition'] ?? 'moderate',
                                'weather_condition' => $validated['weather_condition'] ?? 'clear',
                                'vroom_response_time_ms' => $vroomResult['response_time_ms']
                            ]
                        ],
                        'timestamp' => now()->toISOString()
                    ]);
                } else {
                    // Log de error y usar fallback
                    Log::warning('VROOM falló, usando cálculo de respaldo', [
                        'vroom_error' => $vroomResult['error'],
                        'target_coords' => [$validated['target_lat'], $validated['target_lng']]
                    ]);
                }
            }

            // Fallback: cálculo directo
            $distance = $this->calculateDistanceSimple(
                $vehiclePosition['lat'],
                $vehiclePosition['lng'],
                $validated['target_lat'],
                $validated['target_lng']
            );

            $deliveryEstimate = $this->calculateDeliveryTime(
                $distance,
                $validated['delivery_type'] ?? 'standard',
                $validated['traffic_condition'] ?? 'moderate',
                $validated['weather_condition'] ?? 'clear'
            );

            $vehicle = User::find($vehicleId);

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicle' => [
                        'id' => $vehicleId,
                        'name' => $vehicle ? $vehicle->name : 'Vehículo ' . $vehicleId,
                        'position' => $vehiclePosition
                    ],
                    'target_coordinates' => [
                        'lat' => $validated['target_lat'],
                        'lng' => $validated['target_lng']
                    ],
                    'route_info' => [
                        'distance' => [
                            'km' => round($distance, 3),
                            'meters' => round($distance * 1000, 0),
                            'formatted' => $distance < 1 ? round($distance * 1000) . ' m' : round($distance, 2) . ' km'
                        ],
                        'delivery_estimate' => $deliveryEstimate
                    ],
                    'calculation_details' => [
                        'method' => 'fallback_calculation',
                        'note' => 'VROOM no disponible, usando cálculo directo'
                    ]
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en calculateDistanceFromVehicle', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }
// Agregar estos métodos a tu VroomController

/**
 * Obtiene la posición inicial del vehículo de manera simple
 */
private function getVehicleStartPositionSimple($vehicleId)
{
    // Opción 1: Posición fija para pruebas
    return [
        'lat' => 14.106417,  // Lima, Perú (ejemplo)
        'lng' => -87.185089
    ];
    
    /* Opción 2: Obtener desde la base de datos
    $vehicle = User::find($vehicleId);
    if ($vehicle && $vehicle->lat && $vehicle->lng) {
        return [
            'lat' => (float) $vehicle->lat,
            'lng' => (float) $vehicle->lng
        ];
    }
    
    // Posición por defecto si no se encuentra
    return [
        'lat' => -12.0500,
        'lng' => -77.0500
    ];
    */
}

/**
 * Calcula la distancia entre dos puntos usando la fórmula de Haversine
 */
private function calculateDistanceSimple($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371; // Radio de la Tierra en kilómetros

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return $distance; // Retorna distancia en kilómetros
}

/**
 * Calcula el tiempo estimado de entrega
 */
private function calculateDeliveryTime($distance, $deliveryType = 'standard', $trafficCondition = 'moderate', $weatherCondition = 'clear')
{
    // Velocidad base en km/h según tipo de entrega
    $baseSpeed = [
        'express' => 45,   // Más rápido
        'standard' => 35,  // Velocidad normal
        'economy' => 25    // Más lento pero económico
    ];

    // Factores de ajuste por tráfico (multiplicador)
    $trafficFactors = [
        'light' => 1.0,    // Sin retraso
        'moderate' => 1.3, // 30% más lento
        'heavy' => 1.8     // 80% más lento
    ];

    // Factores de ajuste por clima (multiplicador)
    $weatherFactors = [
        'clear' => 1.0,    // Sin retraso
        'rain' => 1.2,     // 20% más lento
        'storm' => 1.5     // 50% más lento
    ];

    $speed = $baseSpeed[$deliveryType] ?? $baseSpeed['standard'];
    $trafficFactor = $trafficFactors[$trafficCondition] ?? $trafficFactors['moderate'];
    $weatherFactor = $weatherFactors[$weatherCondition] ?? $weatherFactors['clear'];

    // Tiempo base en horas
    $baseTime = $distance / $speed;
    
    // Aplicar factores de ajuste
    $adjustedTime = $baseTime * $trafficFactor * $weatherFactor;
    
    // Convertir a minutos
    $timeInMinutes = $adjustedTime * 60;
    
    // Tiempo mínimo de 5 minutos
    $timeInMinutes = max($timeInMinutes, 5);

    return [
        'seconds' => round($timeInMinutes * 60),
        'minutes' => round($timeInMinutes, 1),
        'formatted' => $timeInMinutes < 60 
            ? round($timeInMinutes) . ' min' 
            : round($timeInMinutes / 60, 1) . ' h'
    ];
}

/**
 * Método placeholder para VROOM (implementar según tu lógica)
 */
private function calculateRouteWithVroom($fromLat, $fromLng, $toLat, $toLng, $vehicleId)
{
    // Por ahora retorna error para usar el fallback
    return [
        'success' => false,
        'error' => 'VROOM no implementado aún'
    ];
    
    /* Implementación real con VROOM API:
    try {
        // Aquí iría tu lógica para llamar a VROOM
        // $response = Http::post('http://tu-vroom-server/route', [...]);
        
        return [
            'success' => true,
            'data' => [
                'distance_km' => 5.2,
                'distance_meters' => 5200,
                'distance_formatted' => '5.2 km',
                'duration_seconds' => 900,
                'duration_minutes' => 15,
                'duration_formatted' => '15 min',
                'geometry' => 'polyline_string_here',
                'steps_count' => 8
            ],
            'response_time_ms' => 150
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    */
}

/**
 * Ajusta el tiempo base de VROOM según condiciones externas
 */
private function adjustVroomTime($baseTimeSeconds, $deliveryType, $trafficCondition, $weatherCondition)
{
    // Factores similares a calculateDeliveryTime pero aplicados al tiempo de VROOM
    $deliveryFactors = [
        'express' => 0.8,   // 20% más rápido
        'standard' => 1.0,  // Sin cambio
        'economy' => 1.2    // 20% más lento
    ];

    $trafficFactors = [
        'light' => 0.9,     // 10% más rápido
        'moderate' => 1.0,  // Sin cambio
        'heavy' => 1.5      // 50% más lento
    ];

    $weatherFactors = [
        'clear' => 1.0,     // Sin cambio
        'rain' => 1.2,      // 20% más lento
        'storm' => 1.4      // 40% más lento
    ];

    $deliveryFactor = $deliveryFactors[$deliveryType] ?? 1.0;
    $trafficFactor = $trafficFactors[$trafficCondition] ?? 1.0;
    $weatherFactor = $weatherFactors[$weatherCondition] ?? 1.0;

    $adjustedSeconds = $baseTimeSeconds * $deliveryFactor * $trafficFactor * $weatherFactor;
    $adjustedMinutes = $adjustedSeconds / 60;

    return [
        'seconds' => round($adjustedSeconds),
        'minutes' => round($adjustedMinutes, 1),
        'formatted' => $adjustedMinutes < 60 
            ? round($adjustedMinutes) . ' min' 
            : round($adjustedMinutes / 60, 1) . ' h'
    ];
}
}

 
