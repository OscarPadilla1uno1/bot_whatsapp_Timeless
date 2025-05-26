<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\RouteAssignment;
use Carbon\Carbon;

class VroomController extends Controller
{
    /**
     * Obtiene la lista de vehículos (motoristas) disponibles de la base de datos
     */
    private function getAvailableVehicles()
    {
        // Obtener los usuarios con el permiso "Motorista"
        $motoristas = User::permission('Motorista')->get();

        if ($motoristas->isEmpty()) {
            return [];
        }

        // Convertir los motoristas a formato de vehículos para VROOM
        $vehicles = $motoristas->map(function ($motorista) {
            return [
                "id" => $motorista->id,
                "start" => [-87.1875, 14.0667],
                "end" => [-87.1875, 14.0667],
                "capacity" => [2]
            ];
        })->toArray();

        return $vehicles;
    }

    /**
     * Calcula un hash basado en los trabajos para detectar cambios
     */
    private function calculateJobsHash($jobs)
    {
        return md5(json_encode($jobs));
    }

    /**
     * Verifica si las rutas necesitan ser recalculadas
     */
    private function shouldRecalculateRoutes($jobs)
    {
        $currentJobsHash = $this->calculateJobsHash($jobs);

        // Verificar si hay asignaciones existentes
        $existingAssignments = RouteAssignment::count();

        if ($existingAssignments === 0) {
            return true; // No hay asignaciones, necesitamos calcular
        }

        // Verificar si el hash de trabajos cambió
        $latestAssignment = RouteAssignment::latest('calculated_at')->first();

        if (!$latestAssignment || $latestAssignment->route_hash !== $currentJobsHash) {
            return true; // Los trabajos cambiaron, necesitamos recalcular
        }

        // Verificar si las asignaciones son muy antiguas (más de 1 hora)
        $oneHourAgo = Carbon::now()->subHour();
        if ($latestAssignment->calculated_at < $oneHourAgo) {
            return true; // Las asignaciones son muy antiguas
        }

        return false; // No necesitamos recalcular
    }

    /**
     * Guarda las asignaciones de rutas en la base de datos
     */
    private function saveRouteAssignments($routes, $jobsHash)
    {
        try {
            DB::transaction(function () use ($routes, $jobsHash) {
                // Limpiar asignaciones anteriores
                RouteAssignment::truncate();

                // Guardar nuevas asignaciones
                foreach ($routes as $route) {
                    // Validar que el usuario existe
                    $user = User::find($route['vehicle']);
                    if (!$user) {
                        Log::warning('Usuario no encontrado para ruta', ['vehicle_id' => $route['vehicle']]);
                        continue;
                    }

                    // Crear la asignación
                    RouteAssignment::create([
                        'user_id' => $route['vehicle'],
                        'route_data' => json_encode($route), // Asegurar que es JSON
                        'route_hash' => $jobsHash,
                        'calculated_at' => Carbon::now()
                    ]);

                    Log::debug('Ruta asignada guardada', [
                        'user_id' => $route['vehicle'],
                        'user_name' => $user->name
                    ]);
                }
            });

            Log::info('Todas las asignaciones de rutas guardadas exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al guardar asignaciones de rutas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    /**
     * Obtiene las rutas asignadas desde la base de datos
     */
    private function getStoredRouteAssignments()
    {
        return RouteAssignment::with('user')
            ->get()
            ->map(function ($assignment) {
                $routeData = $assignment->route_data;

                // Asegurar que tenemos la información del usuario actualizada
                $routeData['vehicle_name'] = $assignment->user->name;

                // Encontrar posición del motorista
                $motoristPosition = User::permission('Motorista')
                    ->orderBy('id')
                    ->pluck('id')
                    ->search($assignment->user_id) + 1;

                $routeData['route_identifier'] = "Motorista $motoristPosition";
                $routeData['motorist_position'] = $motoristPosition;

                return $routeData;
            })
            ->toArray();
    }

    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar permisos
        $isAdmin = $user->can('Administrador');
        $isMotorista = $user->can('Motorista');

        if (!$isAdmin && !$isMotorista) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        // Obtener todos los motoristas disponibles
        $allVehicles = $this->getAvailableVehicles();

        if (empty($allVehicles)) {
            return back()->with('error', 'No hay motoristas configurados en el sistema.');
        }

        // Obtener los pedidos a procesar
        $jobs = $this->getValidJobs();

        if (empty($jobs)) {
            return back()->with('error', 'No hay pedidos válidos para procesar.');
        }

        // Verificar si necesitamos recalcular las rutas
        $shouldRecalculate = $this->shouldRecalculateRoutes($jobs);

        if ($shouldRecalculate) {
            Log::info('Recalculando rutas con VROOM');

            // Datos para la API de VROOM
            $data = [
                "vehicles" => $allVehicles,
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

            // Procesar las rutas devueltas por VROOM
            $allRoutes = [];

            foreach ($result['routes'] as $routeData) {
                if (!isset($routeData['geometry'])) {
                    Log::warning('Ruta sin geometría', $routeData);
                    continue;
                }

                // Obtener el ID del vehículo directamente de la respuesta de VROOM
                $vehicleId = $routeData['vehicle'];

                // Obtener información del motorista
                $motorista = User::find($vehicleId);
                $vehicleName = $motorista ? $motorista->name : "Vehículo $vehicleId";

                // Encontrar posición del motorista
                $motoristPosition = User::permission('Motorista')
                    ->orderBy('id')
                    ->pluck('id')
                    ->search($vehicleId) + 1;

                $routeIdentifier = "Motorista $motoristPosition";

                // Crear objeto de ruta
                $route = [
                    'vehicle' => $vehicleId,
                    'vehicle_name' => $vehicleName,
                    'route_identifier' => $routeIdentifier,
                    'motorist_position' => $motoristPosition,
                    'geometry' => $routeData['geometry'],
                    'steps' => $this->formatSteps($routeData['steps'], $jobs)
                ];

                $allRoutes[] = $route;
            }

            // Guardar las asignaciones en la base de datos
            $jobsHash = $this->calculateJobsHash($jobs);
            $this->saveRouteAssignments($allRoutes, $jobsHash);

            Log::info('Rutas guardadas en la base de datos', [
                'count' => count($allRoutes),
                'jobs_hash' => $jobsHash
            ]);
        } else {
            // Usar las rutas almacenadas
            Log::info('Usando rutas almacenadas de la base de datos');
            $allRoutes = $this->getStoredRouteAssignments();
        }

        // Guardar en la sesión para acceso rápido
        session(['all_routes' => $allRoutes]);

        // Filtrar rutas para motoristas
        $filteredRoutes = $allRoutes;
        if ($isMotorista && !$isAdmin) {
            $filteredRoutes = array_filter($allRoutes, function ($route) use ($user) {
                return $route['vehicle'] == $user->id;
            });
            $filteredRoutes = array_values($filteredRoutes);
        }

        // Registrar información de depuración
        Log::debug('Rutas enviadas a vista', [
            'user_id' => $user->id,
            'is_admin' => $isAdmin,
            'is_motorista' => $isMotorista,
            'filtered_routes_count' => count($filteredRoutes),
            'user_route_ids' => array_column($filteredRoutes, 'vehicle')
        ]);

        return view('vehicle.show', [
            'routes' => $filteredRoutes,
            'vehicles' => $allVehicles,
            'jobs' => $jobs,
            'is_admin' => $isAdmin,
            'is_motorista' => $isMotorista,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);
    }

    /**
     * Forzar recálculo de rutas (para administradores)
     */
    public function recalculateRoutes(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('Administrador')) {
            return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
        }

        try {
            // Limpiar asignaciones existentes para forzar recálculo
            RouteAssignment::truncate();

            return response()->json(['success' => true, 'message' => 'Rutas marcadas para recálculo']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al marcar rutas para recálculo'], 500);
        }
    }
    /**
     * API para seguir una ruta en tiempo real
     */
    /**
     * API para seguir una ruta en tiempo real - VERSIÓN CORREGIDA
     */
    public function seguirRuta(Request $request)
    {
        try {
            // Log inicial para debugging
            Log::info('🧭 Iniciando seguirRuta', [
                'user_id' => Auth::id(),
                'request_data' => $request->all(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);

            // Obtener el usuario autenticado
            $user = Auth::user();

            if (!$user) {
                Log::warning('Usuario no autenticado en seguirRuta');
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // Verificar permisos
            $isAdmin = $user->can('Administrador');
            $isMotorista = $user->can('Motorista');

            Log::info('Permisos de usuario', [
                'user_id' => $user->id,
                'is_admin' => $isAdmin,
                'is_motorista' => $isMotorista
            ]);

            // Si no es admin ni motorista, denegar acceso
            if (!$isAdmin && !$isMotorista) {
                Log::warning('Usuario sin permisos suficientes', [
                    'user_id' => $user->id,
                    'permissions' => $user->getAllPermissions()->pluck('name')
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para acceder a esta funcionalidad.'
                ], 403);
            }

            // Validación de entrada mejorada
            try {
                $validated = $request->validate([
                    'current_location' => 'required|array|size:2',
                    'current_location.0' => 'required|numeric|between:-90,90', // latitud
                    'current_location.1' => 'required|numeric|between:-180,180', // longitud
                    'vehicle_id' => 'sometimes|integer|exists:users,id'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Error de validación en seguirRuta', [
                    'errors' => $e->errors(),
                    'input' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Datos de ubicación inválidos',
                    'details' => $e->errors()
                ], 422);
            }

            // Obtener ubicación actual desde el request
            $currentLocation = $validated['current_location'];

            Log::info('Ubicación recibida', [
                'lat' => $currentLocation[0],
                'lng' => $currentLocation[1]
            ]);

            // Determinar el ID del vehículo a usar
            $vehicleId = $user->id; // Por defecto, usar el ID del usuario actual

            // Para administradores, permitir especificar vehículo
            if ($isAdmin && isset($validated['vehicle_id'])) {
                $requestedVehicleId = $validated['vehicle_id'];

                // Verificar que el vehículo solicitado existe y tiene permisos de motorista
                $requestedUser = User::find($requestedVehicleId);
                if ($requestedUser && $requestedUser->can('Motorista')) {
                    $vehicleId = $requestedVehicleId;
                    Log::info('Admin usando vehículo específico', [
                        'admin_id' => $user->id,
                        'vehicle_id' => $vehicleId,
                        'vehicle_name' => $requestedUser->name
                    ]);
                } else {
                    Log::warning('Vehículo solicitado inválido', [
                        'requested_vehicle_id' => $requestedVehicleId,
                        'exists' => $requestedUser ? 'sí' : 'no',
                        'is_motorista' => $requestedUser ? $requestedUser->can('Motorista') : false
                    ]);
                }
            }

            // Para motoristas no-admin, SIEMPRE forzar su propio ID
            if ($isMotorista && !$isAdmin) {
                $vehicleId = $user->id;
                Log::info('Motorista usando su propio vehículo', [
                    'motorista_id' => $user->id
                ]);
            }

            // Obtener información del motorista/vehículo
            $motorista = User::find($vehicleId);
            if (!$motorista) {
                Log::error('Motorista no encontrado', ['vehicle_id' => $vehicleId]);
                return response()->json([
                    'success' => false,
                    'error' => 'Vehículo no encontrado'
                ], 404);
            }

            $vehicleName = $motorista->name;
            Log::info('Usando vehículo', [
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $vehicleName
            ]);

            // Configurar vehículo con ubicación actual
            $vehicle = [
                "id" => $vehicleId,
                "start" => [$currentLocation[1], $currentLocation[0]], // VROOM usa [lng, lat]
                "end" => [$currentLocation[1], $currentLocation[0]],   // VROOM usa [lng, lat]
                "capacity" => [2]
            ];

            // Obtener trabajos/pedidos válidos
            $jobs = $this->getValidJobs();

            if (empty($jobs)) {
                Log::warning('No hay trabajos válidos disponibles');
                return response()->json([
                    'success' => false,
                    'error' => 'No hay pedidos válidos para procesar'
                ], 400);
            }

            Log::info('Trabajos obtenidos', [
                'jobs_count' => count($jobs),
                'jobs_preview' => array_slice($jobs, 0, 3) // Solo los primeros 3 para el log
            ]);

            // Configurar solicitud para VROOM
            $vroomRequest = [
                "vehicles" => [$vehicle], // Solo un vehículo
                "jobs" => $jobs,
                "options" => ["g" => true] // Habilitar geometría
            ];

            Log::info('Enviando solicitud a VROOM', [
                'vehicle_count' => count($vroomRequest['vehicles']),
                'jobs_count' => count($vroomRequest['jobs']),
                'vehicle_location' => $vehicle['start']
            ]);

            // Enviar solicitud a VROOM con manejo de errores mejorado
            try {
                $vroomUrl = config('app.vroom_url', 'http://154.38.191.25:3000');

                $response = Http::timeout(30)
                    ->retry(2, 1000) // Reintentar 2 veces con 1 segundo de espera
                    ->post($vroomUrl, $vroomRequest);

                if ($response->failed()) {
                    Log::error('Error en respuesta de VROOM', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'url' => $vroomUrl
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Error al calcular la ruta desde tu ubicación actual',
                        'details' => 'Servidor de rutas no disponible (HTTP ' . $response->status() . ')'
                    ], 500);
                }

                $result = $response->json();

                Log::info('Respuesta de VROOM recibida', [
                    'has_routes' => isset($result['routes']),
                    'routes_count' => isset($result['routes']) ? count($result['routes']) : 0,
                    'has_geometry' => isset($result['routes'][0]['geometry']) ? 'sí' : 'no'
                ]);

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Error de conexión con VROOM', [
                    'error' => $e->getMessage(),
                    'url' => $vroomUrl ?? 'URL no definida'
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'No se puede conectar con el servidor de rutas',
                    'details' => 'Verifica tu conexión a internet'
                ], 503);

            } catch (\Exception $e) {
                Log::error('Error inesperado al contactar VROOM', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error interno al calcular ruta',
                    'details' => 'Error de servidor interno'
                ], 500);
            }

            // Validar respuesta de VROOM
            if (!isset($result['routes']) || empty($result['routes'])) {
                Log::warning('VROOM no devolvió rutas', [
                    'result_keys' => array_keys($result ?? []),
                    'full_result' => $result
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'No se encontraron rutas disponibles desde tu ubicación',
                    'details' => 'El servidor de rutas no pudo calcular una ruta válida'
                ], 404);
            }

            // Procesar la primera ruta
            $routeData = $result['routes'][0];

            if (!isset($routeData['geometry'])) {
                Log::error('Ruta sin geometría', [
                    'route_keys' => array_keys($routeData),
                    'route_data' => $routeData
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'La ruta calculada no contiene información geométrica'
                ], 500);
            }

            // Crear la respuesta de ruta
            $routeResponse = [
                'vehicle' => $vehicleId,
                'vehicle_name' => $vehicleName,
                'geometry' => $routeData['geometry'],
                'steps' => isset($routeData['steps']) ? $this->formatSteps($routeData['steps'], $jobs) : [],
                'distance' => $routeData['distance'] ?? null,
                'duration' => $routeData['duration'] ?? null,
                'cost' => $routeData['cost'] ?? null
            ];

            Log::info('Ruta procesada exitosamente', [
                'vehicle_id' => $vehicleId,
                'vehicle_name' => $vehicleName,
                'steps_count' => count($routeResponse['steps']),
                'has_geometry' => !empty($routeResponse['geometry']),
                'distance' => $routeResponse['distance'],
                'duration' => $routeResponse['duration']
            ]);

            return response()->json([
                'success' => true,
                'routes' => [$routeResponse],
                'message' => 'Ruta calculada exitosamente',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error crítico en seguirRuta', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id() ?? 'no_auth',
                'request_data' => $request->all() ?? []
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => config('app.debug') ? $e->getMessage() : 'Ha ocurrido un error inesperado',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Método auxiliar mejorado para obtener trabajos válidos
     */
    private function getValidJobs()
    {
        try {
            Log::info('🔍 Obteniendo trabajos válidos...');

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
                ->where('pedidos.latitud', '!=', 0)
                ->where('pedidos.longitud', '!=', 0)
                ->where('pedidos.latitud', '>=', -90)
                ->where('pedidos.latitud', '<=', 90)
                ->where('pedidos.longitud', '>=', -180)
                ->where('pedidos.longitud', '<=', 180)
                ->get();

            $validJobs = $pedidos->map(function ($pedido) {
                return [
                    "id" => $pedido->id,
                    "location" => [(float) $pedido->longitud, (float) $pedido->latitud], // VROOM usa [lng, lat]
                    "delivery" => [1],
                    "cliente" => $pedido->cliente_nombre ?? 'Cliente desconocido'
                ];
            })->toArray();

            Log::info('Trabajos válidos obtenidos', [
                'total_pedidos' => $pedidos->count(),
                'valid_jobs' => count($validJobs)
            ]);

            return $validJobs;

        } catch (\Exception $e) {
            Log::error('Error al obtener trabajos válidos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
        }
    }

    /**
     * Método auxiliar mejorado para formatear pasos
     */
    private function formatSteps($steps, $jobs)
    {
        if (!is_array($steps) || empty($steps)) {
            return [];
        }

        return array_map(function ($step) use ($jobs) {
            $formatted = [
                'type' => $step['type'] ?? 'unknown',
                'location' => $step['location'] ?? null,
                'distance' => $step['distance'] ?? null,
                'duration' => $step['duration'] ?? null,
                'arrival' => $step['arrival'] ?? null
            ];

            if (isset($step['id']) && is_array($jobs)) {
                $formatted['job'] = $step['id'];
                $job = collect($jobs)->firstWhere('id', $step['id']);
                if ($job) {
                    $formatted['job_details'] = [
                        'cliente' => $job['cliente'] ?? 'Desconocido',
                        'id' => $job['id'] ?? null
                    ];
                }
            }

            return $formatted;
        }, $steps);
    }
/**
 * API para calcular distancia desde vehículo hasta coordenadas específicas
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function calculateDistanceFromVehicle(Request $request)
{
    try {
        // Validación de entrada
        $validated = $request->validate([
            'target_lat' => 'required|numeric|between:-90,90',
            'target_lng' => 'required|numeric|between:-180,180',
            'vehicle_id' => 'sometimes|integer|exists:users,id',
            'method' => 'sometimes|string|in:haversine,euclidean,manhattan'
        ]);

        // Usar usuario por defecto para testing
$user = Auth::user() ?? User::first(); // Toma el primer usuario de la BD

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
        $vehiclePosition = $this->getVehicleStartPosition($vehicleId);
        
        if (!$vehiclePosition) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo obtener la posición del vehículo'
            ], 404);
        }

        // Calcular distancia
        $method = $validated['method'] ?? 'haversine';
        $distance = $this->calculateDistance(
            $vehiclePosition['lat'],
            $vehiclePosition['lng'],
            $validated['target_lat'],
            $validated['target_lng'],
            $method
        );

        return response()->json([
            'success' => true,
            'data' => [
                'vehicle_id' => $vehicleId,
                'vehicle_position' => $vehiclePosition,
                'target_coordinates' => [
                    'lat' => $validated['target_lat'],
                    'lng' => $validated['target_lng']
                ],
                'distance' => [
                    'km' => round($distance, 3),
                    'meters' => round($distance * 1000, 0),
                    'formatted' => $distance < 1 ? round($distance * 1000) . ' m' : round($distance, 2) . ' km'
                ],
                'method' => $method
            ]
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

/**
 * Obtiene la posición inicial del vehículo
 */
private function getVehicleStartPosition($vehicleId)
{
    try {
        // 1. Buscar en asignaciones de rutas
        $routeAssignment = RouteAssignment::where('user_id', $vehicleId)
            ->latest('calculated_at')
            ->first();
            
        if ($routeAssignment && $routeAssignment->route_data) {
            $routeData = $routeAssignment->route_data;
            
            if (isset($routeData['steps']) && is_array($routeData['steps'])) {
                foreach ($routeData['steps'] as $step) {
                    if ($step['type'] === 'start' && isset($step['location'])) {
                        return [
                            'lat' => $step['location'][1], // VROOM usa [lng, lat]
                            'lng' => $step['location'][0],
                            'source' => 'route_assignment'
                        ];
                    }
                }
            }
        }

        // 2. Usar posición por defecto de getAvailableVehicles()
        $vehicles = $this->getAvailableVehicles();
        $vehicle = collect($vehicles)->firstWhere('id', $vehicleId);
        
        if ($vehicle && isset($vehicle['start'])) {
            return [
                'lat' => $vehicle['start'][1],
                'lng' => $vehicle['start'][0],
                'source' => 'default_position'
            ];
        }

        // 3. Posición por defecto (Tegucigalpa)
        return [
            'lat' => 14.0667,
            'lng' => -87.1875,
            'source' => 'fallback'
        ];

    } catch (\Exception $e) {
        Log::error('Error obteniendo posición del vehículo', [
            'vehicle_id' => $vehicleId,
            'error' => $e->getMessage()
        ]);
        
        return null;
    }
}

/**
 * Calcula distancia entre dos puntos
 */
private function calculateDistance($lat1, $lng1, $lat2, $lng2, $method = 'haversine')
{
    switch ($method) {
        case 'euclidean':
            return $this->euclideanDistance($lat1, $lng1, $lat2, $lng2);
            
        case 'manhattan':
            return $this->manhattanDistance($lat1, $lng1, $lat2, $lng2);
            
        case 'haversine':
        default:
            return $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
    }
}

/**
 * Fórmula de Haversine (más precisa para la Tierra)
 */
private function haversineDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371; // Radio de la Tierra en km

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Distancia Euclidiana (línea recta)
 */
private function euclideanDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371;
    
    $latDiff = $lat2 - $lat1;
    $lngDiff = $lng2 - $lng1;
    
    $latKm = $latDiff * ($earthRadius * M_PI / 180);
    $lngKm = $lngDiff * ($earthRadius * M_PI / 180) * cos(deg2rad(($lat1 + $lat2) / 2));
    
    return sqrt($latKm * $latKm + $lngKm * $lngKm);
}

}