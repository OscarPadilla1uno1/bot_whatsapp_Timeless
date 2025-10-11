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

        $vroomUrl = 'https://lacampañafoodservice.com/vroom/';
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
    // En el método showDriverRoute del VroomController
    public function showDriverRoute(Request $request, $userId = null)
    {
        if (!$userId) {
            $userId = auth()->id();
        }

        $driver = User::where('id', $userId)
            ->whereHas('permissions', function ($query) {
                $query->where('name', 'Motorista');
            })
            ->first();

        if (!$driver) {
            return back()->with('error', 'Usuario no encontrado o no es motorista');
        }

        // Obtener jornada activa del repartidor
        $jornadaActiva = DB::table('jornadas_entrega')
            ->where('driver_id', $userId)
            ->where('fecha', now()->toDateString())
            ->whereIn('estado', ['asignada', 'en_progreso'])
            ->orderBy('fecha_asignacion', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$jornadaActiva) {
            return view('vehicle.driver', [
                'driver' => $driver,
                'route' => null,
                'jobs' => []
            ])->with('message', 'No tienes jornadas asignadas actualmente.');
        }

        // Obtener pedidos de la jornada CON INFORMACIÓN DE PAGO
        $pedidosJornada = DB::table('pedidos')
            ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
            ->leftJoin('pagos', 'pedidos.id', '=', 'pagos.pedido_id') // LEFT JOIN con pagos
            ->select(
                'pedidos.id',
                'pedidos.latitud',
                'pedidos.longitud',
                'pedidos.estado',
                'pedidos.total',
                'clientes.nombre as cliente_nombre',
                'clientes.telefono as cliente_telefono',
                'pagos.metodo_pago', // Obtener método de pago
                'pagos.estado_pago' // Y estado del pago
            )
            ->where('pedidos.jornada_id', $jornadaActiva->id)
            ->whereNotNull('pedidos.latitud')
            ->whereNotNull('pedidos.longitud')
            ->get();

        // Formatear como ruta para la vista existente
        $route = [
            'vehicle' => 1,
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'jornada_id' => $jornadaActiva->id,
            'geometry' => $jornadaActiva->route_geometry,
            'steps' => $this->formatStepsFromJornada($pedidosJornada, $jornadaActiva)
        ];

        return view('vehicle.driver', [
            'driver' => $driver,
            'route' => $route,
            'jobs' => $pedidosJornada->toArray()
        ]);
    }

    // Modificar el método formatStepsFromJornada para incluir información de pago
    private function formatStepsFromJornada($pedidos, $jornada)
    {
        $steps = [];

        // Agregar punto de inicio
        $steps[] = [
            'type' => 'start',
            'location' => [-87.1875, 14.0667] // Punto base
        ];

        // Agregar cada pedido como un job
        foreach ($pedidos as $index => $pedido) {
            $steps[] = [
                'type' => 'job',
                'job' => $pedido->id,
                'location' => [(float) $pedido->longitud, (float) $pedido->latitud],
                'job_details' => [
                    'cliente' => $pedido->cliente_nombre,
                    'telefono' => $pedido->cliente_telefono,
                    'metodo_pago' => $pedido->metodo_pago,
                    'estado_pago' => $pedido->estado_pago,
                    'total' => $pedido->total
                ]
            ];
        }

        return $steps;
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

        $vroomUrl = 'https://lacampañafoodservice.com/vroom/';
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
        $drivers = User::whereHas('permissions', function ($query) {
            $query->where('name', 'Motorista');
        })
            ->where('disponible_jornadas', true) // AGREGAR ESTA LÍNEA
            ->get();

        $vehicles = [];
        foreach ($drivers as $index => $driver) {
            $vehicles[] = [
                "id" => $index + 1,
                "driver_id" => $driver->id,
                "driver_name" => $driver->name,
                "driver_email" => $driver->email,
                "start" => [-87.1875, 14.0667],
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
                'clientes.nombre as cliente_nombre',
                'clientes.telefono as cliente_telefono'

            )
            ->whereNotNull('pedidos.latitud')
            ->whereNotNull('pedidos.longitud')
            ->where('pedidos.estado', 'despachado')
            ->whereDate('pedidos.fecha_pedido', now()->toDateString())
            ->get();

        return $pedidos->map(function ($pedido) {
            return [
                "id" => $pedido->id,
                "location" => [(float) $pedido->longitud, (float) $pedido->latitud],
                "delivery" => [1],
                "cliente" => $pedido->cliente_nombre,
                "telefono" => $pedido->cliente_telefono
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
            $vehicles = [
                [
                    "id" => 1,
                    "start" => $currentLocation,
                    "end" => $currentLocation,
                    "capacity" => [2]
                ]
            ];

            $data = [
                "vehicles" => $vehicles,
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            // Llamar a VROOM
            $vroomUrl = 'https://lacampañafoodservice.com/vroom/';
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
                        'cliente' => $job['cliente'] ?? 'Desconocido',
                        'telefono' => $job['telefono'] ?? 'Desconocido'
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

    /**
     * Actualizar estado de entrega
     */
    public function updateDeliveryStatus(Request $request)
    {
        try {
            // Validar los datos de entrada, permitiendo tanto 'status' como 'estado'
            $validated = $request->validate([
                'delivery_id' => 'sometimes|integer', // a veces puede venir como delivery_id
                'pedido_id' => 'sometimes|integer', // o como pedido_id
                'status' => 'sometimes|string|in:entregado,devuelto', // status en inglés
                'estado' => 'sometimes|string|in:entregado,devuelto', // estado en español
                'driver_id' => 'required|integer|exists:users,id',
                'timestamp' => 'sometimes|string',
                'notes' => 'sometimes|string|max:500',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180'
            ]);

            // Determinar el ID del pedido: priorizar 'delivery_id', si no, 'pedido_id'
            $pedidoId = $validated['delivery_id'] ?? $validated['pedido_id'] ?? null;

            if (!$pedidoId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Se requiere delivery_id o pedido_id'
                ], 422);
            }

            // Determinar el estado: priorizar 'estado', si no, 'status'
            $nuevoEstado = $validated['estado'] ?? $validated['status'] ?? null;

            if (!$nuevoEstado) {
                return response()->json([
                    'success' => false,
                    'error' => 'Se requiere estado o status'
                ], 422);
            }

            // Verificar que el pedido existe
            $pedido = DB::table('pedidos')->where('id', $pedidoId)->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pedido no encontrado'
                ], 404);
            }

            // Actualizar el pedido
            $updateData = [
                'estado' => $nuevoEstado,
                'updated_at' => now()
            ];

            // Si se marca como entregado, agregar timestamp de entrega
            if ($nuevoEstado === 'entregado') {
                $updateData['fecha_entrega'] = $validated['timestamp'] ?
                    \Carbon\Carbon::parse($validated['timestamp']) : now();
            }

            $updated = DB::table('pedidos')
                ->where('id', $pedidoId)
                ->update($updateData);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al actualizar el pedido'
                ], 500);
            }

            // Actualizar en tabla de relación si existe
            if (DB::getSchemaBuilder()->hasTable('jornada_pedidos')) {
                DB::table('jornada_pedidos')
                    ->where('pedido_id', $pedidoId)
                    ->update([
                        'estado' => $nuevoEstado,
                        'updated_at' => now()
                    ]);
            }

            // Registrar en historial si la tabla existe
            if (DB::getSchemaBuilder()->hasTable('delivery_history')) {
                DB::table('delivery_history')->insert([
                    'delivery_id' => $pedidoId,
                    'driver_id' => $validated['driver_id'],
                    'action' => $nuevoEstado,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'metadata' => json_encode($validated),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Estado de entrega actualizado correctamente',
                'data' => [
                    'delivery_id' => $pedidoId,
                    'new_status' => $nuevoEstado,
                    'driver_id' => $validated['driver_id'],
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en updateDeliveryStatus', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'delivery_id' => $request->input('delivery_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Marcar entrega como completada (método simplificado)
     */
    // En el controlador VroomController
    public function markDeliveryCompleted(Request $request)
    {
        $deliveryId = $request->input('delivery_id');
        $driverId = $request->input('driver_id');

        if (!$deliveryId || !$driverId) {
            return response()->json([
                'success' => false,
                'error' => 'delivery_id y driver_id son requeridos'
            ], 400);
        }

        try {
            DB::table('pedidos')
                ->where('id', $deliveryId)
                ->update([
                    'estado' => 'entregado',
                    'fecha_entrega' => now()
                ]);

            // Verificar si todos los pedidos de la jornada están completados
            $this->checkJornadaCompletion($driverId);

            return response()->json([
                'success' => true,
                'message' => 'Entrega completada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function checkJornadaCompletion($driverId)
    {
        $jornadaActiva = DB::table('jornadas_entrega')
            ->where('driver_id', $driverId)
            ->where('fecha', now()->toDateString())
            ->whereIn('estado', ['asignada', 'en_progreso'])
            ->first();

        if (!$jornadaActiva)
            return;

        $pedidosPendientes = DB::table('pedidos')
            ->where('jornada_id', $jornadaActiva->id)
            ->where('estado', 'despachado')
            ->count();

        // Si no hay pedidos pendientes, completar jornada y buscar siguiente
        if ($pedidosPendientes === 0) {
            DB::table('jornadas_entrega')
                ->where('id', $jornadaActiva->id)
                ->update([
                    'estado' => 'completada',
                    'fecha_completada' => now()
                ]);

            // Buscar siguiente jornada y asignar automáticamente
            $siguienteJornada = DB::table('jornadas_entrega')
                ->where('fecha', now()->toDateString())
                ->where('estado', 'planificada')
                ->whereNull('driver_id')
                ->orderBy('hora_inicio')
                ->first();

            if ($siguienteJornada) {
                DB::table('jornadas_entrega')
                    ->where('id', $siguienteJornada->id)
                    ->update([
                        'driver_id' => $driverId,
                        'estado' => 'asignada',
                        'fecha_asignacion' => now()
                    ]);
            }
        }
    }

    /**
     * Marcar entrega como devuelta
     */
    public function markDeliveryReturned(Request $request)
    {
        try {
            $validated = $request->validate([
                'delivery_id' => 'required|integer|exists:pedidos,id',
                'driver_id' => 'required|integer|exists:users,id',
                'reason' => 'sometimes|string|max:500',
                'latitude' => 'sometimes|numeric|between:-90,90',
                'longitude' => 'sometimes|numeric|between:-180,180'
            ]);

            $pedido = DB::table('pedidos')->where('id', $validated['delivery_id'])->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pedido no encontrado'
                ], 404);
            }

            if ($pedido->vehiculo_asignado != $validated['driver_id'] && !Auth::user()->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Este pedido no está asignado a este motorista'
                ], 403);
            }

            // Actualizar estado a devuelto
            $updated = DB::table('pedidos')
                ->where('id', $validated['delivery_id'])
                ->update([
                    'estado' => 'devuelto',
                    'fecha_devolucion' => now(),
                    'motivo_devolucion' => $validated['reason'] ?? 'No especificado',
                    'updated_at' => now()
                ]);

            if ($updated) {
                // Registrar en historial
                $this->logDeliveryHistory($validated['delivery_id'], $validated['driver_id'], 'devuelto', $validated);

                return response()->json([
                    'success' => true,
                    'message' => 'Entrega marcada como devuelta correctamente',
                    'delivery_id' => $validated['delivery_id'],
                    'reason' => $validated['reason'] ?? 'No especificado',
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al actualizar el estado del pedido'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en markDeliveryReturned', [
                'error' => $e->getMessage(),
                'delivery_id' => $request->input('delivery_id'),
                'driver_id' => $request->input('driver_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener estado de entregas para un motorista
     */
    public function getDeliveryStatus(Request $request, $driverId = null)
    {
        try {
            $driverId = $driverId ?? auth()->id();

            // Verificar permisos
            if (auth()->id() != $driverId && !auth()->user()->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para ver estas entregas'
                ], 403);
            }

            $entregas = DB::table('pedidos')
                ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->select(
                    'pedidos.id',
                    'pedidos.estado',
                    'pedidos.fecha_pedido',
                    'pedidos.fecha_entrega',
                    'pedidos.latitud',
                    'pedidos.longitud',
                    'pedidos.total',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono'
                )
                ->where('pedidos.vehiculo_asignado', $driverId)
                ->orderBy('pedidos.fecha_pedido', 'desc')
                ->get();

            $estadisticas = [
                'total' => $entregas->count(),
                'pendientes' => $entregas->where('estado', 'pendiente')->count(),
                'en_preparacion' => $entregas->where('estado', 'en preparación')->count(),
                'entregados' => $entregas->where('estado', 'entregado')->count(),
                'devueltos' => $entregas->where('estado', 'devuelto')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'driver_id' => $driverId,
                    'entregas' => $entregas,
                    'estadisticas' => $estadisticas
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getDeliveryStatus', [
                'error' => $e->getMessage(),
                'driver_id' => $driverId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estado de entregas'
            ], 500);
        }
    }

    /**
     * Obtener historial de entregas
     */
    public function getDeliveryHistory(Request $request, $deliveryId)
    {
        try {
            $history = DB::table('delivery_history')
                ->join('users', 'delivery_history.driver_id', '=', 'users.id')
                ->select(
                    'delivery_history.*',
                    'users.name as driver_name'
                )
                ->where('delivery_history.delivery_id', $deliveryId)
                ->orderBy('delivery_history.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'delivery_id' => $deliveryId,
                    'history' => $history
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getDeliveryHistory', [
                'error' => $e->getMessage(),
                'delivery_id' => $deliveryId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo historial de entrega'
            ], 500);
        }
    }

    /**
     * Resetear estado de entrega (solo para administradores)
     */
    public function resetDeliveryStatus(Request $request)
    {
        try {
            // Solo administradores pueden resetear
            if (!auth()->user()->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo los administradores pueden resetear entregas'
                ], 403);
            }

            $validated = $request->validate([
                'delivery_id' => 'required|integer|exists:pedidos,id',
                'reason' => 'required|string|max:500'
            ]);

            $updated = DB::table('pedidos')
                ->where('id', $validated['delivery_id'])
                ->update([
                    'estado' => 'pendiente',
                    'fecha_entrega' => null,
                    'fecha_devolucion' => null,
                    'motivo_devolucion' => null,
                    'updated_at' => now()
                ]);

            if ($updated) {
                // Registrar el reset en historial
                $this->logDeliveryHistory(
                    $validated['delivery_id'],
                    auth()->id(),
                    'reset',
                    ['reason' => $validated['reason']]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Estado de entrega reseteado correctamente',
                    'delivery_id' => $validated['delivery_id']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al resetear el estado del pedido'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en resetDeliveryStatus', [
                'error' => $e->getMessage(),
                'delivery_id' => $request->input('delivery_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Registrar en historial de entregas
     */
    private function logDeliveryHistory($deliveryId, $driverId, $action, $data = [])
    {
        try {
            DB::table('delivery_history')->insert([
                'delivery_id' => $deliveryId,
                'driver_id' => $driverId,
                'action' => $action,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? $data['reason'] ?? null,
                'metadata' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error registrando historial de entrega', [
                'error' => $e->getMessage(),
                'delivery_id' => $deliveryId,
                'driver_id' => $driverId,
                'action' => $action
            ]);
        }
    }

    /**
     * Crear una nueva jornada de trabajo
     */
    public function createNewShift(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre_jornada' => 'required|string|max:255',
                'fecha' => 'required|date',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'nullable|date_format:H:i',
                'tipo_jornada' => 'required|string|in:mañana,tarde,noche,express',
                'pedidos_asignados' => 'required|array|min:1',
                'pedidos_asignados.*' => 'integer|exists:pedidos,id'
            ]);

            // Verificar que todos los pedidos estén en estado "despachado"
            $pedidosValidos = DB::table('pedidos')
                ->whereIn('id', $validated['pedidos_asignados'])
                ->where('estado', 'despachado')
                ->count();

            if ($pedidosValidos !== count($validated['pedidos_asignados'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo se pueden asignar pedidos en estado "despachado" a las jornadas'
                ], 400);
            }

            // Crear la jornada
            $jornadaId = DB::table('jornadas_entrega')->insertGetId([
                'nombre' => $validated['nombre_jornada'],
                'fecha' => $validated['fecha'],
                'hora_inicio' => $validated['hora_inicio'],
                'hora_fin' => $validated['hora_fin'],
                'tipo' => $validated['tipo_jornada'],
                'estado' => 'planificada',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Verificar si existe la columna jornada_id en pedidos
            if (DB::getSchemaBuilder()->hasColumn('pedidos', 'jornada_id')) {
                // Asignar pedidos a la jornada
                DB::table('pedidos')
                    ->whereIn('id', $validated['pedidos_asignados'])
                    ->update([
                        'jornada_id' => $jornadaId,
                        'estado' => 'asignado_jornada', // Cambiar estado para indicar que está en jornada
                        'updated_at' => now()
                    ]);
            }

            // Crear registros en tabla de relación si existe
            if (DB::getSchemaBuilder()->hasTable('jornada_pedidos')) {
                $pedidosData = [];
                foreach ($validated['pedidos_asignados'] as $pedidoId) {
                    $pedidosData[] = [
                        'jornada_id' => $jornadaId,
                        'pedido_id' => $pedidoId,
                        'estado' => 'pendiente',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                DB::table('jornada_pedidos')->insert($pedidosData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jornada creada exitosamente con pedidos despachados',
                'jornada_id' => $jornadaId,
                'pedidos_count' => count($validated['pedidos_asignados'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando jornada', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al crear jornada: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Asignar jornada a repartidor disponible
     */
    public function assignShiftToDriver(Request $request)
    {
        try {
            $validated = $request->validate([
                'jornada_id' => 'required|integer|exists:jornadas_entrega,id',
                'driver_id' => 'nullable|integer|exists:users,id'
            ]);

            $jornada = DB::table('jornadas_entrega')
                ->where('id', $validated['jornada_id'])
                ->first();

            if (!$jornada) {
                return response()->json(['error' => 'Jornada no encontrada'], 404);
            }

            if ($jornada->estado !== 'planificada') {
                return response()->json(['error' => 'Jornada ya asignada o completada'], 400);
            }

            $driverId = $validated['driver_id'];

            // Si no se especifica driver, buscar uno disponible
            if (!$driverId) {
                $driverId = $this->findAvailableDriver($jornada->fecha);

                if (!$driverId) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No hay repartidores disponibles'
                    ], 404);
                }
            } else {
                // Verificar que el driver esté disponible
                if (!$this->isDriverAvailable($driverId, $jornada->fecha)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Repartidor no disponible'
                    ], 400);
                }
            }

            // Asignar jornada al repartidor
            DB::table('jornadas_entrega')
                ->where('id', $validated['jornada_id'])
                ->update([
                    'driver_id' => $driverId,
                    'estado' => 'asignada',
                    'fecha_asignacion' => now(),
                    'updated_at' => now()
                ]);

            // Actualizar pedidos
            DB::table('pedidos')
                ->where('jornada_id', $validated['jornada_id'])
                ->update([
                    'vehiculo_asignado' => $driverId,
                    'estado' => 'asignado_repartidor',
                    'updated_at' => now()
                ]);

            // Generar ruta optimizada para la jornada
            $routeResult = $this->generateShiftRoute($validated['jornada_id'], $driverId);

            $driver = User::find($driverId);

            return response()->json([
                'success' => true,
                'message' => 'Jornada asignada exitosamente',
                'data' => [
                    'jornada_id' => $validated['jornada_id'],
                    'driver_id' => $driverId,
                    'driver_name' => $driver->name,
                    'route_generated' => $routeResult['success'] ?? false,
                    'pedidos_count' => $this->getShiftOrdersCount($validated['jornada_id'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error asignando jornada', [
                'error' => $e->getMessage(),
                'jornada_id' => $request->input('jornada_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al asignar jornada'
            ], 500);
        }
    }

    /**
     * Marcar jornada como completada y buscar siguiente
     */
    public function completeShiftAndAssignNext(Request $request)
    {
        try {
            $validated = $request->validate([
                'jornada_id' => 'required|integer|exists:jornadas_entrega,id',
                'driver_id' => 'required|integer|exists:users,id',
                'completion_notes' => 'nullable|string|max:1000'
            ]);

            // Verificar que el repartidor puede completar esta jornada
            $jornada = DB::table('jornadas_entrega')
                ->where('id', $validated['jornada_id'])
                ->where('driver_id', $validated['driver_id'])
                ->first();

            if (!$jornada) {
                return response()->json(['error' => 'Jornada no encontrada o no asignada a este repartidor'], 404);
            }

            // Verificar que todos los pedidos estén completados o devueltos
            $pedidosPendientes = DB::table('pedidos')
                ->where('jornada_id', $validated['jornada_id'])
                ->whereNotIn('estado', ['entregado', 'devuelto'])
                ->count();

            if ($pedidosPendientes > 0) {
                return response()->json([
                    'success' => false,
                    'error' => "Aún hay {$pedidosPendientes} pedidos pendientes en esta jornada",
                    'pendientes' => $pedidosPendientes
                ], 400);
            }

            // Marcar jornada como completada
            DB::table('jornadas_entrega')
                ->where('id', $validated['jornada_id'])
                ->update([
                    'estado' => 'completada',
                    'fecha_completada' => now(),
                    'completion_notes' => $validated['completion_notes'],
                    'updated_at' => now()
                ]);

            // Buscar siguiente jornada disponible
            $siguienteJornada = $this->findNextAvailableShift($validated['driver_id']);

            $response = [
                'success' => true,
                'message' => 'Jornada completada exitosamente',
                'completed_shift' => [
                    'jornada_id' => $validated['jornada_id'],
                    'completion_time' => now()->toISOString()
                ]
            ];

            if ($siguienteJornada) {
                // Asignar automáticamente la siguiente jornada
                $assignResult = $this->assignShiftToDriver(new \Illuminate\Http\Request([
                    'jornada_id' => $siguienteJornada->id,
                    'driver_id' => $validated['driver_id']
                ]));

                $response['next_shift'] = [
                    'jornada_id' => $siguienteJornada->id,
                    'nombre' => $siguienteJornada->nombre,
                    'tipo' => $siguienteJornada->tipo,
                    'assigned' => $assignResult->getData()->success ?? false
                ];
            } else {
                $response['next_shift'] = null;
                $response['message'] .= '. No hay más jornadas disponibles por hoy.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error completando jornada', [
                'error' => $e->getMessage(),
                'jornada_id' => $request->input('jornada_id'),
                'driver_id' => $request->input('driver_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al completar jornada'
            ], 500);
        }
    }

    /**
     * Obtener jornadas pendientes y en progreso
     */
    public function getShiftStatus(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $jornadas = DB::table('jornadas_entrega as j')
                ->leftJoin('users as u', 'j.driver_id', '=', 'u.id')
                ->select([
                    'j.*',
                    'u.name as driver_name',
                    'u.email as driver_email',
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id) as total_pedidos'),
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id AND estado = "entregado") as pedidos_entregados'),
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id AND estado = "devuelto") as pedidos_devueltos')
                ])
                ->where('j.fecha', $fecha)
                ->orderBy('j.hora_inicio')
                ->get();

            $estadisticas = [
                'total_jornadas' => $jornadas->count(),
                'planificadas' => $jornadas->where('estado', 'planificada')->count(),
                'asignadas' => $jornadas->where('estado', 'asignada')->count(),
                'en_progreso' => $jornadas->where('estado', 'en_progreso')->count(),
                'completadas' => $jornadas->where('estado', 'completada')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'fecha' => $fecha,
                    'jornadas' => $jornadas,
                    'estadisticas' => $estadisticas
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estado de jornadas', [
                'error' => $e->getMessage(),
                'fecha' => $request->input('fecha')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estado de jornadas'
            ], 500);
        }
    }

    /**
     * Métodos auxiliares
     */
    private function findAvailableDriver($fecha)
    {
        // Buscar repartidores que no tienen jornadas asignadas para esta fecha
        $availableDrivers = DB::table('users')
            ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->leftJoin('jornadas_entrega', function ($join) use ($fecha) {
                $join->on('users.id', '=', 'jornadas_entrega.driver_id')
                    ->where('jornadas_entrega.fecha', $fecha)
                    ->whereIn('jornadas_entrega.estado', ['asignada', 'en_progreso']);
            })
            ->where('permissions.name', 'Motorista')
            ->whereNull('jornadas_entrega.id') // No tiene jornadas asignadas hoy
            ->select('users.id')
            ->first();

        return $availableDrivers ? $availableDrivers->id : null;
    }

    private function isDriverAvailable($driverId, $fecha)
    {
        $activeShifts = DB::table('jornadas_entrega')
            ->where('driver_id', $driverId)
            ->where('fecha', $fecha)
            ->whereIn('estado', ['asignada', 'en_progreso'])
            ->count();

        return $activeShifts === 0;
    }

    private function findNextAvailableShift($driverId)
    {
        return DB::table('jornadas_entrega')
            ->where('fecha', now()->toDateString())
            ->where('estado', 'planificada')
            ->whereNull('driver_id')
            ->orderBy('hora_inicio')
            ->first();
    }

    private function generateShiftRoute($jornadaId, $driverId)
    {
        try {
            // Obtener pedidos de la jornada
            $pedidos = DB::table('pedidos')
                ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->where('pedidos.jornada_id', $jornadaId)
                ->whereNotNull('pedidos.latitud')
                ->whereNotNull('pedidos.longitud')
                ->select(
                    'pedidos.id',
                    'pedidos.latitud',
                    'pedidos.longitud',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono'
                )
                ->get();

            if ($pedidos->isEmpty()) {
                return ['success' => false, 'error' => 'No hay pedidos con ubicación válida'];
            }

            // Preparar datos para VROOM
            $jobs = $pedidos->map(function ($pedido) {
                return [
                    "id" => $pedido->id,
                    "location" => [(float) $pedido->longitud, (float) $pedido->latitud],
                    "delivery" => [1],
                    "cliente" => $pedido->cliente_nombre,
                    "telefono" => $pedido->cliente_telefono
                ];
            })->toArray();

            $vehicles = [
                [
                    "id" => 1,
                    "driver_id" => $driverId,
                    "start" => [-87.1875, 14.0667], // Punto base
                    "capacity" => [count($jobs)]
                ]
            ];

            $data = [
                "vehicles" => $vehicles,
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            // Llamar a VROOM
            $vroomUrl = 'https://lacampañafoodservice.com/vroom/';
            $response = Http::timeout(30)->post($vroomUrl, $data);

            if ($response->successful()) {
                $result = $response->json();

                // Guardar ruta en la jornada
                DB::table('jornadas_entrega')
                    ->where('id', $jornadaId)
                    ->update([
                        'route_geometry' => $result['routes'][0]['geometry'] ?? null,
                        'route_data' => json_encode($result),
                        'updated_at' => now()
                    ]);

                return ['success' => true, 'route_data' => $result];
            }

            return ['success' => false, 'error' => 'Error en VROOM API'];

        } catch (\Exception $e) {
            Log::error('Error generando ruta de jornada', [
                'error' => $e->getMessage(),
                'jornada_id' => $jornadaId
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getShiftOrdersCount($jornadaId)
    {
        return DB::table('pedidos')
            ->where('jornada_id', $jornadaId)
            ->count();
    }

    /**
     * Obtener jornada actual del repartidor
     */
    public function getCurrentShift(Request $request)
    {
        try {
            $driverId = $request->input('driver_id', auth()->id());

            // Verificar permisos
            if (auth()->id() != $driverId && !auth()->user()->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para ver esta información'
                ], 403);
            }

            $jornadaActual = DB::table('jornadas_entrega as j')
                ->leftJoin('users as u', 'j.driver_id', '=', 'u.id')
                ->select([
                    'j.*',
                    'u.name as driver_name',
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id) as total_pedidos'),
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id AND estado = "entregado") as pedidos_entregados'),
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id AND estado = "devuelto") as pedidos_devueltos')
                ])
                ->where('j.driver_id', $driverId)
                ->where('j.fecha', now()->toDateString())
                ->whereIn('j.estado', ['asignada', 'en_progreso'])
                ->first();

            if (!$jornadaActual) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No hay jornadas activas'
                ]);
            }

            // Obtener pedidos de la jornada
            $pedidos = DB::table('pedidos')
                ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->where('pedidos.jornada_id', $jornadaActual->id)
                ->select(
                    'pedidos.*',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono'
                )
                ->orderBy('pedidos.id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'jornada' => $jornadaActual,
                    'pedidos' => $pedidos,
                    'progreso' => [
                        'total' => $jornadaActual->total_pedidos,
                        'completados' => $jornadaActual->pedidos_entregados + $jornadaActual->pedidos_devueltos,
                        'pendientes' => $jornadaActual->total_pedidos - ($jornadaActual->pedidos_entregados + $jornadaActual->pedidos_devueltos),
                        'porcentaje' => $jornadaActual->total_pedidos > 0 ?
                            round((($jornadaActual->pedidos_entregados + $jornadaActual->pedidos_devueltos) / $jornadaActual->total_pedidos) * 100, 1) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo jornada actual', [
                'error' => $e->getMessage(),
                'driver_id' => $driverId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo jornada actual'
            ], 500);
        }
    }

    /**
     * Iniciar una jornada
     */
    public function startShift(Request $request)
    {
        try {
            $validated = $request->validate([
                'jornada_id' => 'required|integer|exists:jornadas_entrega,id',
                'driver_id' => 'sometimes|integer|exists:users,id'
            ]);

            $driverId = $validated['driver_id'] ?? auth()->id();

            // Verificar permisos
            if (auth()->id() != $driverId && !auth()->user()->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permisos para iniciar esta jornada'
                ], 403);
            }

            $jornada = DB::table('jornadas_entrega')
                ->where('id', $validated['jornada_id'])
                ->where('driver_id', $driverId)
                ->where('estado', 'asignada')
                ->first();

            if (!$jornada) {
                return response()->json([
                    'success' => false,
                    'error' => 'Jornada no encontrada o no está en estado para iniciar'
                ], 404);
            }

            // Marcar jornada como en progreso
            DB::table('jornadas_entrega')
                ->where('id', $validated['jornada_id'])
                ->update([
                    'estado' => 'en_progreso',
                    'fecha_inicio_real' => now(),
                    'updated_at' => now()
                ]);

            // Actualizar pedidos
            DB::table('pedidos')
                ->where('jornada_id', $validated['jornada_id'])
                ->update([
                    'estado' => 'en_ruta',
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Jornada iniciada exitosamente',
                'jornada_id' => $validated['jornada_id'],
                'inicio_real' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error iniciando jornada', [
                'error' => $e->getMessage(),
                'jornada_id' => $request->input('jornada_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al iniciar jornada'
            ], 500);
        }
    }

    /**
     * Auto-asignar jornadas pendientes
     */
    public function autoAssignNextShifts(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            // Obtener jornadas pendientes
            $jornadasPendientes = DB::table('jornadas_entrega')
                ->where('fecha', $fecha)
                ->where('estado', 'planificada')
                ->whereNull('driver_id')
                ->orderBy('hora_inicio')
                ->get();

            if ($jornadasPendientes->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay jornadas pendientes para asignar',
                    'assigned_count' => 0
                ]);
            }

            // Obtener repartidores disponibles
            $repartidoresDisponibles = $this->getAvailableDriversForDate($fecha);

            $assignedCount = 0;
            $errors = [];

            foreach ($jornadasPendientes as $jornada) {
                if (empty($repartidoresDisponibles)) {
                    $errors[] = "No hay más repartidores disponibles para jornada {$jornada->nombre}";
                    break;
                }

                $driverId = array_shift($repartidoresDisponibles);

                try {
                    // Asignar jornada
                    DB::table('jornadas_entrega')
                        ->where('id', $jornada->id)
                        ->update([
                            'driver_id' => $driverId,
                            'estado' => 'asignada',
                            'fecha_asignacion' => now(),
                            'updated_at' => now()
                        ]);

                    // Actualizar pedidos
                    DB::table('pedidos')
                        ->where('jornada_id', $jornada->id)
                        ->update([
                            'vehiculo_asignado' => $driverId,
                            'estado' => 'asignado_repartidor',
                            'updated_at' => now()
                        ]);

                    // Generar ruta
                    $this->generateShiftRoute($jornada->id, $driverId);

                    $assignedCount++;

                    Log::info('Jornada auto-asignada', [
                        'jornada_id' => $jornada->id,
                        'driver_id' => $driverId
                    ]);

                } catch (\Exception $e) {
                    $errors[] = "Error asignando jornada {$jornada->nombre}: {$e->getMessage()}";
                    Log::error('Error en auto-asignación', [
                        'jornada_id' => $jornada->id,
                        'driver_id' => $driverId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se asignaron {$assignedCount} jornadas automáticamente",
                'assigned_count' => $assignedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error en auto-asignación de jornadas', [
                'error' => $e->getMessage(),
                'fecha' => $request->input('fecha')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en la auto-asignación'
            ], 500);
        }
    }

    /**
     * Obtener repartidores disponibles para una fecha
     */
    private function getAvailableDriversForDate($fecha)
    {
        return DB::table('users')
            ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->leftJoin('jornadas_entrega', function ($join) use ($fecha) {
                $join->on('users.id', '=', 'jornadas_entrega.driver_id')
                    ->where('jornadas_entrega.fecha', $fecha)
                    ->whereIn('jornadas_entrega.estado', ['asignada', 'en_progreso']);
            })
            ->where('permissions.name', 'Motorista')
            ->where('users.disponible_jornadas', true) // NUEVO: Solo conductores habilitados
            ->whereNull('jornadas_entrega.id')
            ->pluck('users.id')
            ->toArray();
    }
    /**
     * Dashboard de jornadas para admin
     */
    public function shiftDashboard(Request $request)
    {
        $fecha = $request->input('fecha', now()->toDateString());

        return view('admin.jornadas', [
            'fecha' => $fecha,
            'titulo' => 'Dashboard de Jornadas'
        ]);
    }

    /**
     * Obtener métricas del dashboard
     */
    public function getDashboardMetrics(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $metricas = [
                'jornadas' => [
                    'total' => DB::table('jornadas_entrega')->where('fecha', $fecha)->count(),
                    'planificadas' => DB::table('jornadas_entrega')->where('fecha', $fecha)->where('estado', 'planificada')->count(),
                    'asignadas' => DB::table('jornadas_entrega')->where('fecha', $fecha)->where('estado', 'asignada')->count(),
                    'en_progreso' => DB::table('jornadas_entrega')->where('fecha', $fecha)->where('estado', 'en_progreso')->count(),
                    'completadas' => DB::table('jornadas_entrega')->where('fecha', $fecha)->where('estado', 'completada')->count()
                ],
                'pedidos' => [
                    'total' => DB::table('pedidos')->whereDate('fecha_pedido', $fecha)->count(),
                    'asignados' => DB::table('pedidos')->whereDate('fecha_pedido', $fecha)->whereNotNull('jornada_id')->count(),
                    'entregados' => DB::table('pedidos')->whereDate('fecha_pedido', $fecha)->where('estado', 'entregado')->count(),
                    'devueltos' => DB::table('pedidos')->whereDate('fecha_pedido', $fecha)->where('estado', 'devuelto')->count()
                ],
                'repartidores' => [
                    'total' => DB::table('users')
                        ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                        ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                        ->where('permissions.name', 'Motorista')
                        ->count(),
                    'ocupados' => DB::table('jornadas_entrega')
                        ->where('fecha', $fecha)
                        ->whereIn('estado', ['asignada', 'en_progreso'])
                        ->distinct('driver_id')
                        ->count('driver_id')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $metricas,
                'fecha' => $fecha
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo métricas del dashboard', [
                'error' => $e->getMessage(),
                'fecha' => $request->input('fecha')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo métricas'
            ], 500);
        }
    }

    /**
     * Actualizar ubicación del repartidor
     */
    public function updateDriverLocation(Request $request)
    {
        try {
            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'sometimes|numeric',
                'speed' => 'sometimes|numeric',
                'heading' => 'sometimes|numeric|between:0,360'
            ]);

            $driverId = auth()->id();

            // Guardar ubicación en tabla de tracking
            DB::table('driver_locations')->insert([
                'driver_id' => $driverId,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'speed' => $validated['speed'] ?? null,
                'heading' => $validated['heading'] ?? null,
                'created_at' => now()
            ]);

            // Mantener solo las últimas 50 ubicaciones por repartidor
            $this->cleanupOldLocations($driverId);

            return response()->json([
                'success' => true,
                'message' => 'Ubicación actualizada',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando ubicación de repartidor', [
                'error' => $e->getMessage(),
                'driver_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error actualizando ubicación'
            ], 500);
        }
    }

    /**
     * Limpiar ubicaciones antiguas
     */
    private function cleanupOldLocations($driverId, $limit = 50)
    {
        $totalLocations = DB::table('driver_locations')
            ->where('driver_id', $driverId)
            ->count();

        if ($totalLocations > $limit) {
            $oldLocationIds = DB::table('driver_locations')
                ->where('driver_id', $driverId)
                ->orderBy('created_at', 'desc')
                ->skip($limit)
                ->pluck('id');

            DB::table('driver_locations')
                ->whereIn('id', $oldLocationIds)
                ->delete();
        }
    }

    /**
     * Obtener pedidos disponibles para asignar a jornadas
     */
    public function getAvailableOrders(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            // Primero obtener pedidos despachados (sabemos que esto funciona)
            $pedidos = DB::table('pedidos')
                ->select('id', 'estado', 'fecha_pedido', 'total', 'cliente_id')
                ->whereDate('fecha_pedido', $fecha)
                ->where('estado', 'despachado')
                ->get();

            // Verificar si existe tabla clientes
            $hasClientes = DB::getSchemaBuilder()->hasTable('clientes');

            if (!$hasClientes) {
                // Sin tabla clientes, devolver pedidos básicos
                $result = $pedidos->map(function ($pedido) {
                    return [
                        'id' => $pedido->id,
                        'total' => $pedido->total ?? 0,
                        'estado' => $pedido->estado,
                        'fecha_pedido' => $pedido->fecha_pedido,
                        'cliente_nombre' => 'Cliente #' . $pedido->cliente_id,
                        'cliente_telefono' => 'N/A'
                    ];
                })->toArray();

                return response()->json($result);
            }

            // Con tabla clientes - hacer JOIN solo si la tabla existe
            $result = [];

            foreach ($pedidos as $pedido) {
                $cliente = DB::table('clientes')
                    ->where('id', $pedido->cliente_id)
                    ->first();

                $result[] = [
                    'id' => $pedido->id,
                    'total' => $pedido->total ?? 0,
                    'estado' => $pedido->estado,
                    'fecha_pedido' => $pedido->fecha_pedido,
                    'cliente_nombre' => $cliente->nombre ?? 'Cliente #' . $pedido->cliente_id,
                    'cliente_telefono' => $cliente->telefono ?? 'N/A'
                ];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error en getAvailableOrders', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtener repartidores disponibles para asignar jornadas
     */
    public function getAvailableDriversAPI(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $repartidores = DB::table('users')
                ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->leftJoin('jornadas_entrega', function ($join) use ($fecha) {
                    $join->on('users.id', '=', 'jornadas_entrega.driver_id')
                        ->where('jornadas_entrega.fecha', $fecha)
                        ->whereIn('jornadas_entrega.estado', ['asignada', 'en_progreso']);
                })
                ->where('permissions.name', 'Motorista')
                ->whereNull('jornadas_entrega.id') // No tienen jornadas activas
                ->select('users.id', 'users.name', 'users.email')
                ->distinct()
                ->get();

            return response()->json($repartidores);

        } catch (\Exception $e) {
            Log::error('Error obteniendo repartidores disponibles', [
                'error' => $e->getMessage(),
                'fecha' => $request->input('fecha')
            ]);

            return response()->json([
                'error' => 'Error obteniendo repartidores disponibles'
            ], 500);
        }
    }

    /**
     * Crear vista para mostrar la gestión de jornadas
     * Este método maneja la vista principal donde se gestionan las jornadas
     */
    public function adminJornadasIndex(Request $request)
    {
        // Verificar permisos
        if (!auth()->user()->can('Administrador')) {
            abort(403, 'No tienes permisos para acceder a esta página');
        }

        $fecha = $request->input('fecha', now()->toDateString());

        // Obtener estadísticas básicas para la vista
        $estadisticas = [
            'total_jornadas' => DB::table('jornadas_entrega')->where('fecha', $fecha)->count(),
            'jornadas_pendientes' => DB::table('jornadas_entrega')->where('fecha', $fecha)->where('estado', 'planificada')->count(),
            'jornadas_activas' => DB::table('jornadas_entrega')->where('fecha', $fecha)->whereIn('estado', ['asignada', 'en_progreso'])->count(),
            'pedidos_disponibles' => DB::table('pedidos')->whereDate('fecha_pedido', $fecha)->whereNull('jornada_id')->count(),
            'repartidores_libres' => $this->getAvailableDriversCount($fecha)
        ];

        return view('admin.jornadas', compact('fecha', 'estadisticas'));
    }

    /**
     * Método auxiliar para contar repartidores disponibles
     */
    private function getAvailableDriversCount($fecha)
    {
        return DB::table('users')
            ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->leftJoin('jornadas_entrega', function ($join) use ($fecha) {
                $join->on('users.id', '=', 'jornadas_entrega.driver_id')
                    ->where('jornadas_entrega.fecha', $fecha)
                    ->whereIn('jornadas_entrega.estado', ['asignada', 'en_progreso']);
            })
            ->where('permissions.name', 'Motorista')
            ->whereNull('jornadas_entrega.id')
            ->count();
    }

    /**
     * Método para debugging - obtener información del sistema
     */
    public function systemStatus(Request $request)
    {
        // Solo para administradores
        if (!auth()->user()->can('Administrador')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $info = [
                'user' => [
                    'id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'permissions' => auth()->user()->permissions->pluck('name')->toArray()
                ],
                'tables_exist' => [
                    'jornadas_entrega' => DB::getSchemaBuilder()->hasTable('jornadas_entrega'),
                    'jornada_pedidos' => DB::getSchemaBuilder()->hasTable('jornada_pedidos'),
                    'pedidos' => DB::getSchemaBuilder()->hasTable('pedidos'),
                    'users' => DB::getSchemaBuilder()->hasTable('users')
                ],
                'routes_registered' => [
                    'admin.jornadas.status' => route_exists('admin.jornadas.status'),
                    'admin.pedidos.disponibles' => route_exists('admin.pedidos.disponibles'),
                    'admin.repartidores.disponibles' => route_exists('admin.repartidores.disponibles')
                ],
                'database_connection' => DB::connection()->getPdo() ? 'OK' : 'FAILED',
                'timestamp' => now()->toISOString()
            ];

            return response()->json([
                'success' => true,
                'system_info' => $info
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método temporal para testing - crear datos de ejemplo
     */
    public function createSampleData(Request $request)
    {
        // Solo para administradores y solo en desarrollo
        if (!auth()->user()->can('Administrador')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        if (app()->environment('production')) {
            return response()->json(['error' => 'No disponible en producción'], 403);
        }

        try {
            // Crear jornada de ejemplo
            $jornadaId = DB::table('jornadas_entrega')->insertGetId([
                'nombre' => 'Jornada de Prueba - ' . now()->format('H:i'),
                'fecha' => now()->toDateString(),
                'hora_inicio' => now()->format('H:i'),
                'tipo' => 'mañana',
                'estado' => 'planificada',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jornada de prueba creada',
                'jornada_id' => $jornadaId
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando datos de ejemplo', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error creando datos de ejemplo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si una ruta existe
     */
    private function route_exists($name)
    {
        try {
            route($name);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Método de debugging para auto-asignación
     */
    public function debugAutoAssign(Request $request)
    {
        if (!auth()->user()->can('Administrador')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $debug = [
                'fecha_consultada' => $fecha,
                'tablas_existen' => [
                    'jornadas_entrega' => DB::getSchemaBuilder()->hasTable('jornadas_entrega'),
                    'users' => DB::getSchemaBuilder()->hasTable('users'),
                    'model_has_permissions' => DB::getSchemaBuilder()->hasTable('model_has_permissions'),
                    'permissions' => DB::getSchemaBuilder()->hasTable('permissions')
                ]
            ];

            // Verificar jornadas pendientes
            if ($debug['tablas_existen']['jornadas_entrega']) {
                $jornadasPendientes = DB::table('jornadas_entrega')
                    ->where('fecha', $fecha)
                    ->where('estado', 'planificada')
                    ->whereNull('driver_id')
                    ->get();

                $debug['jornadas_pendientes'] = [
                    'total' => $jornadasPendientes->count(),
                    'jornadas' => $jornadasPendientes->toArray()
                ];

                // Todas las jornadas de la fecha
                $todasJornadas = DB::table('jornadas_entrega')
                    ->where('fecha', $fecha)
                    ->get();

                $debug['todas_jornadas'] = [
                    'total' => $todasJornadas->count(),
                    'por_estado' => $todasJornadas->groupBy('estado')->map(function ($group) {
                        return $group->count();
                    })->toArray()
                ];
            } else {
                $debug['jornadas_pendientes'] = ['error' => 'Tabla jornadas_entrega no existe'];
                $debug['todas_jornadas'] = ['error' => 'Tabla jornadas_entrega no existe'];
            }

            // Verificar repartidores disponibles
            if ($debug['tablas_existen']['users'] && $debug['tablas_existen']['permissions']) {
                $repartidores = DB::table('users')
                    ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                    ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                    ->where('permissions.name', 'Motorista')
                    ->select('users.id', 'users.name', 'users.email')
                    ->get();

                $debug['repartidores_sistema'] = [
                    'total' => $repartidores->count(),
                    'lista' => $repartidores->toArray()
                ];

                // Repartidores ocupados hoy
                if ($debug['tablas_existen']['jornadas_entrega']) {
                    $repartidoresOcupados = DB::table('jornadas_entrega')
                        ->where('fecha', $fecha)
                        ->whereIn('estado', ['asignada', 'en_progreso'])
                        ->whereNotNull('driver_id')
                        ->pluck('driver_id')
                        ->toArray();

                    $debug['repartidores_ocupados'] = $repartidoresOcupados;

                    $repartidoresDisponibles = $repartidores->whereNotIn('id', $repartidoresOcupados);
                    $debug['repartidores_disponibles'] = [
                        'total' => $repartidoresDisponibles->count(),
                        'lista' => $repartidoresDisponibles->values()->toArray()
                    ];
                }
            } else {
                $debug['repartidores_sistema'] = ['error' => 'Tablas de usuarios/permisos no configuradas'];
            }

            return response()->json([
                'success' => true,
                'debug_info' => $debug
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Crear jornada de prueba para testing
     */
    public function crearJornadaPrueba(Request $request)
    {
        if (!auth()->user()->can('Administrador')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            // Verificar que exista la tabla
            if (!DB::getSchemaBuilder()->hasTable('jornadas_entrega')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tabla jornadas_entrega no existe. Ejecuta las migraciones primero.'
                ]);
            }

            // Obtener pedidos despachados
            $pedidosDespachados = DB::table('pedidos')
                ->where('estado', 'despachado')
                ->whereDate('fecha_pedido', now()->toDateString())
                ->limit(2) // Solo 2 para prueba
                ->pluck('id')
                ->toArray();

            if (empty($pedidosDespachados)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay pedidos despachados para crear jornada de prueba'
                ]);
            }

            // Crear jornada de prueba
            $jornadaId = DB::table('jornadas_entrega')->insertGetId([
                'nombre' => 'Jornada de Prueba - ' . now()->format('H:i:s'),
                'fecha' => now()->toDateString(),
                'hora_inicio' => now()->addHour()->format('H:i'),
                'hora_fin' => now()->addHours(3)->format('H:i'),
                'tipo' => 'mañana',
                'estado' => 'planificada', // Estado para que pueda ser auto-asignada
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jornada de prueba creada',
                'jornada_id' => $jornadaId,
                'pedidos_incluidos' => $pedidosDespachados,
                'estado' => 'planificada',
                'fecha' => now()->toDateString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error creando jornada de prueba: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Versión simplificada de auto-asignación para debugging
     */
    public function autoAssignSimple(Request $request)
    {
        if (!auth()->user()->can('Administrador')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $fecha = $request->input('fecha', now()->toDateString());

            // Buscar jornadas pendientes
            $jornadasPendientes = DB::table('jornadas_entrega')
                ->where('fecha', $fecha)
                ->where('estado', 'planificada')
                ->whereNull('driver_id')
                ->get();

            if ($jornadasPendientes->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay jornadas pendientes para asignar',
                    'assigned_count' => 0,
                    'debug' => 'Sin jornadas en estado planificada'
                ]);
            }

            // Buscar repartidores disponibles (versión simple)
            $repartidores = DB::table('users')
                ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->where('permissions.name', 'Motorista')
                ->pluck('users.id')
                ->toArray();

            if (empty($repartidores)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay repartidores disponibles',
                    'assigned_count' => 0,
                    'debug' => 'Sin repartidores con permiso Motorista'
                ]);
            }

            $assignedCount = 0;
            $repartidorIndex = 0;

            foreach ($jornadasPendientes as $jornada) {
                if ($repartidorIndex >= count($repartidores)) {
                    break; // No más repartidores disponibles
                }

                $driverId = $repartidores[$repartidorIndex];

                // Asignar jornada
                DB::table('jornadas_entrega')
                    ->where('id', $jornada->id)
                    ->update([
                        'driver_id' => $driverId,
                        'estado' => 'asignada',
                        'fecha_asignacion' => now(),
                        'updated_at' => now()
                    ]);

                $assignedCount++;
                $repartidorIndex++;
            }

            return response()->json([
                'success' => true,
                'message' => "Se asignaron {$assignedCount} jornadas automáticamente",
                'assigned_count' => $assignedCount,
                'jornadas_procesadas' => $jornadasPendientes->count(),
                'repartidores_disponibles' => count($repartidores)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Crear jornada automática para un repartidor con sus pedidos asignados
     */
    private function crearJornadaAutomatica($repartidor, $pedidos, $fecha)
    {
        $horaInicio = now()->format('H:i');
        $horaFin = now()->addHours(4)->format('H:i'); // 4 horas estimadas

        $jornadaId = DB::table('jornadas_entrega')->insertGetId([
            'nombre' => "Jornada {$repartidor->name} - " . now()->format('H:i'),
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'tipo' => $this->determinarTipoJornada(),
            'estado' => 'asignada', // Directamente asignada
            'driver_id' => $repartidor->id,
            'fecha_asignacion' => now(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Generar ruta optimizada usando VROOM
        $this->generarRutaOptimizada($jornadaId, $repartidor->id, $pedidos);

        return $jornadaId;
    }

    /**
     * Actualizar estado de pedidos distribuidos
     */
    private function actualizarEstadoPedidos($pedidosIds)
    {
        $updateData = ['estado' => 'en_ruta'];

        // Solo agregar campos si existen en la tabla
        if (DB::getSchemaBuilder()->hasColumn('pedidos', 'updated_at')) {
            $updateData['updated_at'] = now();
        }

        DB::table('pedidos')
            ->whereIn('id', $pedidosIds)
            ->update($updateData);
    }

    /**
     * Generar ruta optimizada para una jornada
     */
    private function generarRutaOptimizada($jornadaId, $repartidorId, $pedidos)
    {
        try {
            // Preparar datos para VROOM
            $jobs = array_map(function ($pedido) {
                return [
                    "id" => $pedido->id,
                    "location" => [(float) $pedido->longitud, (float) $pedido->latitud],
                    "delivery" => [1]
                ];
            }, $pedidos);

            $vehicles = [
                [
                    "id" => 1,
                    "start" => [-87.1875, 14.0667], // Punto de partida base
                    "capacity" => [count($jobs)]
                ]
            ];

            $data = [
                "vehicles" => $vehicles,
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            // Llamar a VROOM
            $vroomUrl = 'https://lacampañafoodservice.com/vroom/';
            $response = Http::timeout(30)->post($vroomUrl, $data);

            if ($response->successful()) {
                $result = $response->json();

                // Guardar ruta en la jornada
                DB::table('jornadas_entrega')
                    ->where('id', $jornadaId)
                    ->update([
                        'route_geometry' => $result['routes'][0]['geometry'] ?? null,
                        'route_data' => json_encode($result),
                        'updated_at' => now()
                    ]);

                Log::info('Ruta optimizada generada', [
                    'jornada_id' => $jornadaId,
                    'repartidor_id' => $repartidorId,
                    'pedidos_count' => count($jobs)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error generando ruta optimizada', [
                'jornada_id' => $jornadaId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ver estado actual de la distribución
     */
    public function verDistribucionActual(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $jornadas = DB::table('jornadas_entrega as j')
                ->join('users as u', 'j.driver_id', '=', 'u.id')
                ->select([
                    'j.id as jornada_id',
                    'j.nombre as jornada_nombre',
                    'j.estado',
                    'u.id as repartidor_id',
                    'u.name as repartidor_nombre',
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id) as pedidos_asignados'),
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id AND estado = "entregado") as pedidos_entregados'),
                    DB::raw('(SELECT COUNT(*) FROM pedidos WHERE jornada_id = j.id AND estado = "devuelto") as pedidos_devueltos')
                ])
                ->where('j.fecha', $fecha)
                ->get();

            return response()->json([
                'success' => true,
                'fecha' => $fecha,
                'jornadas' => $jornadas,
                'resumen' => [
                    'total_jornadas' => $jornadas->count(),
                    'total_pedidos_asignados' => $jornadas->sum('pedidos_asignados'),
                    'total_entregados' => $jornadas->sum('pedidos_entregados'),
                    'total_devueltos' => $jornadas->sum('pedidos_devueltos')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en verDistribucionActual', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reiniciar distribución (solo para testing)
     */
    public function reiniciarDistribucion(Request $request)
    {
        if (!auth()->user()->can('Administrador')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $fecha = $request->input('fecha', now()->toDateString());

            // Eliminar jornadas del día
            $jornadasEliminadas = DB::table('jornadas_entrega')
                ->where('fecha', $fecha)
                ->delete();

            // MODIFICADO: Resetear TODOS los campos relacionados con jornadas
            $updateData = [
                'estado' => 'despachado',
                'updated_at' => now()
            ];

            if (DB::getSchemaBuilder()->hasColumn('pedidos', 'jornada_id')) {
                $updateData['jornada_id'] = null;
            }
            if (DB::getSchemaBuilder()->hasColumn('pedidos', 'vehiculo_asignado')) {
                $updateData['vehiculo_asignado'] = null;
            }

            $pedidosReseteados = DB::table('pedidos')
                ->whereDate('fecha_pedido', $fecha)
                ->whereIn('estado', ['en_ruta', 'asignado_jornada', 'asignado_repartidor']) // NUEVO: más estados
                ->update($updateData);

            // NUEVO: También limpiar tabla de relación si existe
            if (DB::getSchemaBuilder()->hasTable('jornada_pedidos')) {
                DB::table('jornada_pedidos')
                    ->whereIn('jornada_id', function ($query) use ($fecha) {
                        $query->select('id')
                            ->from('jornadas_entrega')
                            ->where('fecha', $fecha);
                    })
                    ->delete();
            }

            Log::info('Distribución reiniciada', [
                'fecha' => $fecha,
                'jornadas_eliminadas' => $jornadasEliminadas,
                'pedidos_reseteados' => $pedidosReseteados
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Distribución reiniciada correctamente',
                'jornadas_eliminadas' => $jornadasEliminadas,
                'pedidos_reseteados' => $pedidosReseteados
            ]);

        } catch (\Exception $e) {
            Log::error('Error reiniciando distribución', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Distribuir pedidos usando VROOM para optimización de rutas
     */
    public function distribuirPedidosAutomaticamente(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            // Obtener pedidos despachados con coordenadas válidas
            $pedidosDespachados = DB::table('pedidos')
                ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->select(
                    'pedidos.id',
                    'pedidos.latitud',
                    'pedidos.longitud',
                    'pedidos.total',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono'
                )
                ->where('pedidos.estado', 'despachado')
                ->whereDate('pedidos.fecha_pedido', $fecha)
                ->whereNotNull('pedidos.latitud')
                ->whereNotNull('pedidos.longitud')
                ->where('pedidos.latitud', '!=', 0)
                ->where('pedidos.longitud', '!=', 0)
                ->whereNull('pedidos.jornada_id')
                ->get();

            if ($pedidosDespachados->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay pedidos despachados disponibles para distribuir'
                ]);
            }

            // Obtener SOLO repartidores disponibles y habilitados
            $repartidores = DB::table('users')
                ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->where('permissions.name', 'Motorista')
                ->where('users.disponible_jornadas', true) // FILTRO CRÍTICO
                ->where('users.activo', true) // Asegurar que esté activo
                ->select('users.id', 'users.name', 'users.email')
                ->get();

            if ($repartidores->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay repartidores habilitados disponibles'
                ]);
            }

            // Preparar datos para VROOM
            $jobs = [];
            foreach ($pedidosDespachados as $pedido) {
                $jobs[] = [
                    "id" => $pedido->id,
                    "location" => [(float) $pedido->longitud, (float) $pedido->latitud],
                    "delivery" => [1],
                    "cliente" => $pedido->cliente_nombre,
                    "telefono" => $pedido->cliente_telefono
                ];
            }

            $vehicles = [];
            $capacidadPorRepartidor = ceil(count($jobs) / count($repartidores));

            foreach ($repartidores as $index => $repartidor) {
                $vehicles[] = [
                    "id" => $index + 1,
                    "driver_id" => $repartidor->id,
                    "driver_name" => $repartidor->name,
                    "start" => [-87.1875, 14.0667],
                    "capacity" => [$capacidadPorRepartidor] // Distribuir equitativamente
                ];
            }

            $vroomData = [
                "vehicles" => $vehicles,
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            // Resto del código VROOM permanece igual...
            $vroomUrl = 'https://lacampañafoodservice.com/vroom/';
            $response = Http::timeout(30)->post($vroomUrl, $vroomData);

            if ($response->failed()) {
                Log::error('Error en VROOM API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Error al calcular rutas optimizadas'
                ], 500);
            }

            $vroomResult = $response->json();

            if (!isset($vroomResult['routes']) || empty($vroomResult['routes'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudieron generar rutas optimizadas'
                ], 500);
            }

            // Procesar resultados y crear jornadas
            $distribucion = [];
            $totalPedidosAsignados = 0;

            foreach ($vroomResult['routes'] as $index => $route) {
                if (empty($route['steps'])) {
                    continue;
                }

                $vehicle = $vehicles[$index];
                $repartidor = $repartidores->firstWhere('id', $vehicle['driver_id']);

                if (!$repartidor) {
                    continue;
                }

                // Extraer pedidos de los pasos
                $pedidosEnRuta = [];
                foreach ($route['steps'] as $step) {
                    if ($step['type'] === 'job') {
                        $pedidosEnRuta[] = $step['id'];
                    }
                }

                if (empty($pedidosEnRuta)) {
                    continue;
                }

                // Crear jornada
                $jornadaId = DB::table('jornadas_entrega')->insertGetId([
                    'nombre' => "Ruta {$repartidor->name} - " . now()->format('H:i'),
                    'fecha' => $fecha,
                    'hora_inicio' => now()->format('H:i'),
                    'tipo' => 'automática',
                    'estado' => 'asignada',
                    'driver_id' => $repartidor->id,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'route_geometry' => $route['geometry'] ?? null,
                    'route_data' => json_encode($route)
                ]);

                // Asignar pedidos a la jornada
                DB::table('pedidos')
                    ->whereIn('id', $pedidosEnRuta)
                    ->update([
                        'jornada_id' => $jornadaId,
                        'vehiculo_asignado' => $repartidor->id
                    ]);

                $distribucion[] = [
                    'repartidor_id' => $repartidor->id,
                    'repartidor_name' => $repartidor->name,
                    'pedidos_asignados' => count($pedidosEnRuta),
                    'pedidos_ids' => $pedidosEnRuta,
                    'jornada_id' => $jornadaId,
                    'route_distance' => $route['distance'] ?? 0,
                    'route_duration' => $route['duration'] ?? 0
                ];

                $totalPedidosAsignados += count($pedidosEnRuta);
            }

            return response()->json([
                'success' => true,
                'message' => "Se distribuyeron {$totalPedidosAsignados} pedidos entre " . count($distribucion) . " repartidores",
                'distribucion' => $distribucion
            ]);

        } catch (\Exception $e) {
            Log::error('Error en distribución automática', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error en la distribución: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determinar tipo de jornada según la hora
     */
    private function determinarTipoJornada()
    {
        $hora = now()->hour;

        if ($hora < 12)
            return 'mañana';
        if ($hora < 18)
            return 'tarde';
        return 'noche';
    }

    /**
     * Método de respaldo sin VROOM (distribución simple)
     */
    public function distribuirSinVroom(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $pedidosDespachados = DB::table('pedidos')
                ->where('estado', 'despachado')
                ->whereDate('fecha_pedido', $fecha)
                ->get();

            $repartidores = DB::table('users')
                ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->where('permissions.name', 'Motorista')
                ->get();

            $totalPedidos = $pedidosDespachados->count();
            $totalRepartidores = $repartidores->count();

            $distribucion = [];
            $indicePedido = 0;

            foreach ($repartidores as $index => $repartidor) {
                // Calcular pedidos para este repartidor
                $pedidosPorRepartidor = intval($totalPedidos / $totalRepartidores);
                $pedidosExtra = $totalPedidos % $totalRepartidores;
                $pedidosParaEste = $pedidosPorRepartidor + ($index < $pedidosExtra ? 1 : 0);

                if ($pedidosParaEste === 0) {
                    continue;
                }

                $pedidosAsignados = $pedidosDespachados->slice($indicePedido, $pedidosParaEste);
                $indicePedido += $pedidosParaEste;

                if ($pedidosAsignados->isNotEmpty()) {
                    $jornadaId = DB::table('jornadas_entrega')->insertGetId([
                        'nombre' => "Jornada {$repartidor->name} - " . now()->format('H:i'),
                        'fecha' => $fecha,
                        'hora_inicio' => now()->format('H:i'),
                        'tipo' => $this->determinarTipoJornada(),
                        'estado' => 'asignada',
                        'driver_id' => $repartidor->id,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    if (DB::getSchemaBuilder()->hasColumn('pedidos', 'jornada_id')) {
                        DB::table('pedidos')
                            ->whereIn('id', $pedidosAsignados->pluck('id'))
                            ->update(['jornada_id' => $jornadaId]);
                    }

                    if (DB::getSchemaBuilder()->hasColumn('pedidos', 'vehiculo_asignado')) {
                        DB::table('pedidos')
                            ->whereIn('id', $pedidosAsignados->pluck('id'))
                            ->update(['vehiculo_asignado' => $repartidor->id]);
                    }

                    $distribucion[] = [
                        'repartidor_name' => $repartidor->name,
                        'pedidos_asignados' => $pedidosAsignados->count(),
                        'pedidos_ids' => $pedidosAsignados->pluck('id')->toArray(),
                        'jornada_id' => $jornadaId
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se distribuyeron {$totalPedidos} pedidos entre {$totalRepartidores} repartidores (distribución equitativa)",
                'distribucion' => $distribucion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPedidosDelDia(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $pedidos = DB::table('pedidos')
                ->leftJoin('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->leftJoin('jornadas_entrega', 'pedidos.jornada_id', '=', 'jornadas_entrega.id')
                ->leftJoin('users', 'jornadas_entrega.driver_id', '=', 'users.id')
                ->select([
                    'pedidos.id',
                    'pedidos.estado',
                    'pedidos.total',
                    'pedidos.jornada_id',
                    'pedidos.fecha_pedido',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono',
                    'jornadas_entrega.nombre as jornada_nombre',
                    'users.name as repartidor_nombre'
                ])
                ->whereDate('pedidos.fecha_pedido', $fecha)
                ->orderBy('pedidos.estado')
                ->orderBy('pedidos.id')
                ->get();

            // Categorizar pedidos
            $categorias = [
                'despachados_sin_jornada' => $pedidos->where('estado', 'despachado')->whereNull('jornada_id'),
                'despachados_en_jornada' => $pedidos->where('estado', 'despachado')->whereNotNull('jornada_id'),
                'entregados' => $pedidos->where('estado', 'entregado'),
                'devueltos' => $pedidos->where('estado', 'devuelto'),
                'otros_estados' => $pedidos->whereNotIn('estado', ['despachado', 'entregado', 'devuelto'])
            ];

            return response()->json([
                'success' => true,
                'fecha' => $fecha,
                'pedidos' => $pedidos,
                'categorias' => [
                    'despachados_sin_jornada' => $categorias['despachados_sin_jornada']->values()->toArray(),
                    'despachados_en_jornada' => $categorias['despachados_en_jornada']->values()->toArray(),
                    'entregados' => $categorias['entregados']->values()->toArray(),
                    'devueltos' => $categorias['devueltos']->values()->toArray(),
                    'otros_estados' => $categorias['otros_estados']->values()->toArray()
                ],
                'resumen' => [
                    'total' => $pedidos->count(),
                    'despachados_sin_jornada' => $categorias['despachados_sin_jornada']->count(),
                    'despachados_en_jornada' => $categorias['despachados_en_jornada']->count(),
                    'entregados' => $categorias['entregados']->count(),
                    'devueltos' => $categorias['devueltos']->count(),
                    'otros_estados' => $categorias['otros_estados']->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo pedidos del día', [
                'error' => $e->getMessage(),
                'fecha' => $request->input('fecha')
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function vistaDistribucionPedidos()
    {
        return view('admin.jornadas');
    }

    public function toggleDriverAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'driver_id' => 'required|integer|exists:users,id',
                'disponible' => 'required|boolean'
            ]);

            // Verificar que sea motorista
            $driver = User::find($validated['driver_id']);
            if (!$driver->hasPermissionTo('Motorista')) {
                return response()->json([
                    'success' => false,
                    'error' => 'El usuario no es motorista'
                ], 400);
            }

            // Actualizar disponibilidad (asumiendo que tienes una columna disponible_jornadas)
            DB::table('users')
                ->where('id', $validated['driver_id'])
                ->update([
                    'disponible_jornadas' => $validated['disponible'],
                    'updated_at' => now()
                ]);

            $estado = $validated['disponible'] ? 'habilitado' : 'deshabilitado';

            return response()->json([
                'success' => true,
                'message' => "Conductor {$estado} para jornadas",
                'driver_id' => $validated['driver_id'],
                'disponible' => $validated['disponible']
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggleDriverAvailability', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error actualizando disponibilidad'
            ], 500);
        }
    }

    /**
     * Obtener repartidores con estado de disponibilidad
     */
    public function getDriversWithAvailability(Request $request)
    {
        try {
            $fecha = $request->input('fecha', now()->toDateString());

            $repartidores = DB::table('users')
                ->join('model_has_permissions', 'users.id', '=', 'model_has_permissions.model_id')
                ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
                ->leftJoin('jornadas_entrega', function ($join) use ($fecha) {
                    $join->on('users.id', '=', 'jornadas_entrega.driver_id')
                        ->where('jornadas_entrega.fecha', $fecha)
                        ->whereIn('jornadas_entrega.estado', ['asignada', 'en_progreso']);
                })
                ->where('permissions.name', 'Motorista')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.disponible_jornadas',
                    DB::raw('COUNT(jornadas_entrega.id) as jornadas_activas')
                )
                ->groupBy('users.id', 'users.name', 'users.email', 'users.disponible_jornadas')
                ->get();

            return response()->json([
                'success' => true,
                'drivers' => $repartidores
            ]);

        } catch (\Exception $e) {
            Log::error('Error getDriversWithAvailability', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo conductores'
            ], 500);
        }
    }

    /**
     * Verificar si hay nueva jornada asignada al conductor
     */
    public function checkNewShift(Request $request)
    {
        try {
            $driverId = $request->input('driver_id', auth()->id());
            $currentShiftId = $request->input('current_shift_id', null);

            $query = DB::table('jornadas_entrega')
                ->where('driver_id', $driverId)
                ->where('fecha', now()->toDateString())
                ->where('estado', 'asignada')
                ->whereNull('fecha_inicio_real');

            // Excluir la jornada actual si se proporciona
            if ($currentShiftId) {
                $query->where('id', '!=', $currentShiftId);
            }

            $nuevaJornada = $query->orderBy('fecha_asignacion', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            if ($nuevaJornada) {
                $pedidos = DB::table('pedidos')
                    ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                    ->where('pedidos.jornada_id', $nuevaJornada->id)
                    ->select(
                        'pedidos.id',
                        'pedidos.latitud',
                        'pedidos.longitud',
                        'clientes.nombre as cliente_nombre',
                        'clientes.telefono as cliente_telefono'
                    )
                    ->get();

                return response()->json([
                    'success' => true,
                    'has_new_shift' => true,
                    'jornada' => $nuevaJornada,
                    'pedidos_count' => $pedidos->count()
                ]);
            }

            return response()->json([
                'success' => true,
                'has_new_shift' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Error checkNewShift', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error verificando nueva jornada'
            ], 500);
        }
    }

    public function getPedidosJornada($jornadaId)
    {
        try {
            $pedidos = DB::table('pedidos')
                ->leftJoin('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->select([
                    'pedidos.id',
                    'pedidos.estado',
                    'pedidos.total',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono'
                ])
                ->where('pedidos.jornada_id', $jornadaId)
                ->orderBy('pedidos.id')
                ->get();

            return response()->json([
                'success' => true,
                'pedidos' => $pedidos
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getPedidosJornada', [
                'error' => $e->getMessage(),
                'jornada_id' => $jornadaId
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }

    }
    // En VroomController
    public function confirmarCobroEfectivo(Request $request)
    {
        try {
            $validated = $request->validate([
                'delivery_id' => 'required|integer|exists:pedidos,id',
                'monto' => 'required|numeric|min:0',
                'driver_id' => 'required|integer|exists:users,id',
                'timestamp' => 'sometimes|string'
            ]);

            // Actualizar el estado del pago
            DB::table('pagos')
                ->where('pedido_id', $validated['delivery_id'])
                ->update([
                    'estado_pago' => 'confirmado',
                    'fecha_pago' => now(),
                    'updated_at' => now()
                ]);

            // Registrar en historial si existe la tabla
            if (DB::getSchemaBuilder()->hasTable('cobros_efectivo')) {
                DB::table('cobros_efectivo')->insert([
                    'pedido_id' => $validated['delivery_id'],
                    'driver_id' => $validated['driver_id'],
                    'monto' => $validated['monto'],
                    'fecha_cobro' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cobro en efectivo confirmado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error confirmando cobro en efectivo', [
                'error' => $e->getMessage(),
                'delivery_id' => $request->input('delivery_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error confirmando cobro'
            ], 500);
        }
    }
}


