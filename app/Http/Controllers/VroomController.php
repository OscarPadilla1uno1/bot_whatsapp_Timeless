<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
                "start" => [-87.18718148767947, 14.107193715226044],
                "end" => [-87.18718148767947, 14.107193715226044],
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
    private function saveRouteAssignments($routes, $jobsHash)
    {
        try {
            // 1. Limpiar asignaciones anteriores
            RouteAssignment::truncate();
            $this->clearPreviousJobAssignments($jobsHash);

            // 2. Guardar nuevas asignaciones
            DB::transaction(function () use ($routes, $jobsHash) {
                foreach ($routes as $route) {
                    $user = User::find($route['vehicle']);
                    if (!$user) {
                        Log::warning('Usuario no encontrado para ruta', ['vehicle_id' => $route['vehicle']]);
                        continue;
                    }

                    // Guardar la ruta
                    RouteAssignment::create([
                        'user_id' => $route['vehicle'],
                        'route_data' => json_encode($route),
                        'route_hash' => $jobsHash,
                        'calculated_at' => Carbon::now()
                    ]);

                    // FORZAR guardado de asignaciones específicas
                    Log::info('🔄 Ejecutando saveVehicleJobAssignments', [
                        'vehicle_id' => $route['vehicle'],
                        'steps_count' => count($route['steps'] ?? [])
                    ]);

                    $this->saveVehicleJobAssignments($route, $jobsHash);

                    Log::debug('Ruta y trabajos asignados guardados', [
                        'user_id' => $route['vehicle'],
                        'user_name' => $user->name,
                        'jobs_count' => $this->countJobsInRoute($route)
                    ]);
                }
            });

            Log::info('Todas las asignaciones guardadas exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al guardar asignaciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * NUEVA FUNCIÓN: Guarda asignaciones específicas de trabajos por vehículo
     */
    private function saveVehicleJobAssignments($route, $assignmentHash)
    {
        try {
            if (!isset($route['steps']) || !is_array($route['steps'])) {
                Log::warning('Route sin steps válidos', ['vehicle_id' => $route['vehicle']]);
                return;
            }

            $vehicleId = $route['vehicle'];
            $jobsAssigned = [];

            foreach ($route['steps'] as $step) {
                // Solo procesar pasos que sean trabajos/entregas
                if ($step['type'] === 'job' && isset($step['job'])) {
                    $jobId = $step['job'];

                    // Obtener datos completos del trabajo
                    $jobData = $this->getJobCompleteData($jobId);

                    if ($jobData) {
                        Log::info('💾 Guardando asignación de trabajo', [
                            'vehicle_id' => $vehicleId,
                            'job_id' => $jobId,
                            'cliente' => $jobData['cliente']
                        ]);

                        // FORZAR inserción directa en la tabla
                        DB::table('vehicle_job_assignments')->insertOrIgnore([
                            'user_id' => $vehicleId,
                            'job_id' => $jobId,
                            'assignment_hash' => $assignmentHash,
                            'status' => 'pending',
                            'job_data' => json_encode(array_merge($jobData, [
                                'step_info' => $step,
                                'route_order' => count($jobsAssigned) + 1
                            ])),
                            'assigned_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $jobsAssigned[] = $jobId;
                    }
                }
            }

            Log::info('✅ Trabajos asignados guardados', [
                'vehicle_id' => $vehicleId,
                'jobs_assigned' => $jobsAssigned,
                'total_jobs' => count($jobsAssigned)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error guardando asignaciones de trabajos', [
                'vehicle_id' => $route['vehicle'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    /**
     * NUEVA FUNCIÓN: Obtiene datos completos de un trabajo/pedido
     */
    private function getJobCompleteData($jobId)
    {
        try {
            $pedido = DB::table('pedidos')
                ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
                ->select(
                    'pedidos.id',
                    'pedidos.latitud',
                    'pedidos.longitud',
                    'pedidos.created_at as pedido_fecha',
                    'pedidos.estado',
                    'clientes.nombre as cliente_nombre',
                    'clientes.telefono as cliente_telefono'
                    // Removida 'direccion'
                )
                ->where('pedidos.id', $jobId)
                ->first();

            if ($pedido) {
                return [
                    'id' => $pedido->id,
                    'cliente' => $pedido->cliente_nombre,
                    'telefono' => $pedido->cliente_telefono ?: 'No disponible',
                    'coordenadas' => [
                        'lat' => (float) $pedido->latitud,
                        'lng' => (float) $pedido->longitud
                    ],
                    'estado' => $pedido->estado,
                    'fecha_pedido' => $pedido->pedido_fecha
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error obteniendo datos del trabajo', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * NUEVA FUNCIÓN CRÍTICA: Obtiene SOLO los trabajos asignados a un vehículo específico
     */
    private function getAssignedJobsForVehicle($vehicleId)
    {
        try {
            Log::info('🔍 Buscando asignaciones para vehículo', ['vehicle_id' => $vehicleId]);

            $assignedJobs = [];

            // Buscar SOLO trabajos NO completados
            $assignments = DB::table('vehicle_job_assignments')
                ->where('user_id', $vehicleId)
                ->whereIn('status', ['pending', 'in_progress']) // <- SOLO activos, NO completados
                ->orderBy('assigned_at', 'asc')
                ->get();

            Log::info('📋 Asignaciones activas encontradas', [
                'vehicle_id' => $vehicleId,
                'assignments_count' => $assignments->count()
            ]);

            foreach ($assignments as $assignment) {
                $jobData = json_decode($assignment->job_data, true);
                if ($jobData && isset($jobData['coordenadas'])) {
                    $assignedJobs[] = [
                        "id" => $assignment->job_id,
                        "location" => [
                            $jobData['coordenadas']['lng'],
                            $jobData['coordenadas']['lat']
                        ],
                        "delivery" => [1],
                        "cliente" => $jobData['cliente'] ?? 'Cliente desconocido',
                        "status" => $assignment->status,
                        "assignment_data" => $jobData
                    ];
                }
            }

            Log::info('✅ Trabajos activos para vehículo', [
                'vehicle_id' => $vehicleId,
                'active_jobs_count' => count($assignedJobs)
            ]);

            return $assignedJobs;

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo trabajos asignados', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * NUEVA FUNCIÓN: API para marcar trabajos como completados
     */
    public function markJobCompleted(Request $request)
    {
        try {
            Log::info('🔄 Iniciando markJobCompleted', [
                'request_data' => $request->all()
            ]);

            $validated = $request->validate([
                'job_id' => 'required|integer'
            ]);

            $jobId = $validated['job_id'];
            $userId = 8; // <- HARDCODEADO temporalmente (tu user_id)

            Log::info('✅ Datos para procesar', [
                'job_id' => $jobId,
                'user_id' => $userId
            ]);

            // Actualizar en vehicle_job_assignments
            $updated = DB::table('vehicle_job_assignments')
                ->where('job_id', $jobId)
                ->where('user_id', $userId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);

            Log::info('📊 Resultado actualización', [
                'rows_updated' => $updated,
                'job_id' => $jobId
            ]);

            if ($updated > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Entrega completada exitosamente',
                    'job_id' => $jobId
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontró la asignación para completar'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error crítico en markJobCompleted', [
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FUNCIONES DE APOYO
     */

    private function vehicleJobAssignmentsTableExists()
    {
        try {
            return Schema::hasTable('vehicle_job_assignments');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function clearPreviousJobAssignments($newAssignmentHash)
    {
        try {
            if ($this->vehicleJobAssignmentsTableExists()) {
                // Marcar asignaciones anteriores como canceladas en lugar de eliminarlas
                DB::table('vehicle_job_assignments')
                    ->where('assignment_hash', '!=', $newAssignmentHash)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'cancelled',
                        'updated_at' => now()
                    ]);
            } else {
                // Limpiar asignaciones de trabajos en RouteAssignment
                RouteAssignment::where('route_hash', 'LIKE', 'JOB_%')
                    ->where('route_hash', 'NOT LIKE', 'JOB_' . $newAssignmentHash . '%')
                    ->delete();
            }

            Log::info('Asignaciones anteriores limpiadas', [
                'new_hash' => $newAssignmentHash
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando asignaciones anteriores', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function countJobsInRoute($route)
    {
        if (!isset($route['steps'])) {
            return 0;
        }

        return count(array_filter($route['steps'], function ($step) {
            return $step['type'] === 'job';
        }));
    }

    /**
     * NUEVA FUNCIÓN: API para obtener estado de asignaciones
     */
    public function getVehicleAssignmentStatus(Request $request)
    {
        try {
            $user = Auth::user();
            $vehicleId = $user->can('Administrador') && $request->has('vehicle_id')
                ? $request->vehicle_id
                : $user->id;

            $assignedJobs = $this->getAssignedJobsForVehicle($vehicleId);

            $statusSummary = [
                'total_assigned' => count($assignedJobs),
                'pending' => 0,
                'completed' => 0,
                'in_progress' => 0
            ];

            foreach ($assignedJobs as $job) {
                $status = $job['status'] ?? 'pending';
                if (isset($statusSummary[$status])) {
                    $statusSummary[$status]++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicle_id' => $vehicleId,
                    'status_summary' => $statusSummary,
                    'assigned_jobs' => $assignedJobs
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo estado de asignaciones'
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
     * API para calcular distancia y tiempo real usando VROOM
     * Reemplaza el método calculateDistanceFromVehicle existente
     */
    public function calculateDistanceFromVehicle(Request $request)
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
                'use_vroom' => 'sometimes|boolean' // Opción para usar VROOM o cálculo directo
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
                // Calcular usando VROOM (distancia y tiempo reales de la ruta)
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

                    // Obtener información del vehículo
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

            // Fallback: cálculo directo (método anterior)
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

    /**
     * Calcula ruta real usando VROOM (aprovechando tu configuración existente)
     */
    private function calculateRouteWithVroom($startLat, $startLng, $endLat, $endLng, $vehicleId)
    {
        $startTime = microtime(true);

        try {
            // Configurar vehículo para VROOM (mismo formato que usas en seguirRuta)
            $vehicle = [
                "id" => $vehicleId,
                "start" => [$startLng, $startLat], // VROOM usa [lng, lat]
                "end" => [$startLng, $startLat],   // Punto final igual al inicial
                "capacity" => [1]
            ];

            // Configurar trabajo/destino
            $job = [
                "id" => 1,
                "location" => [$endLng, $endLat], // VROOM usa [lng, lat]
                "delivery" => [1]
            ];

            // Solicitud a VROOM (mismo formato que ya usas)
            $vroomRequest = [
                "vehicles" => [$vehicle],
                "jobs" => [$job],
                "options" => ["g" => true] // Habilitar geometría
            ];

            // Usar la misma URL que ya tienes configurada
            $vroomUrl = config('app.vroom_url', 'http://154.38.191.25:3000');

            Log::info('🚛 Enviando solicitud a VROOM para cálculo de distancia', [
                'url' => $vroomUrl,
                'vehicle_id' => $vehicleId,
                'start' => [$startLat, $startLng],
                'end' => [$endLat, $endLng]
            ]);

            // Usar el mismo timeout y configuración que ya tienes
            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->post($vroomUrl, $vroomRequest);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['routes'][0])) {
                    $route = $result['routes'][0];

                    // Extraer distancia y duración de VROOM
                    $distanceMeters = $route['distance'] ?? 0;
                    $durationSeconds = $route['duration'] ?? 0;

                    Log::info('✅ VROOM respondió exitosamente', [
                        'distance_m' => $distanceMeters,
                        'duration_s' => $durationSeconds,
                        'response_time_ms' => $responseTime
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'distance_meters' => $distanceMeters,
                            'distance_km' => round($distanceMeters / 1000, 3),
                            'distance_formatted' => $this->formatDistance($distanceMeters / 1000),
                            'duration_seconds' => $durationSeconds,
                            'duration_minutes' => round($durationSeconds / 60, 1),
                            'duration_formatted' => $this->formatDuration($durationSeconds),
                            'geometry' => $route['geometry'] ?? null,
                            'steps_count' => isset($route['steps']) ? count($route['steps']) : 0,
                            'steps' => $route['steps'] ?? []
                        ],
                        'response_time_ms' => $responseTime
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'VROOM no devolvió rutas válidas',
                        'vroom_response' => $result
                    ];
                }
            } else {
                Log::error('❌ Error HTTP desde VROOM', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Error HTTP ' . $response->status() . ' desde VROOM',
                    'response_body' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Error al conectar con VROOM', [
                'error' => $e->getMessage(),
                'start' => [$startLat, $startLng],
                'end' => [$endLat, $endLng]
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con VROOM: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ajusta el tiempo base de VROOM con factores externos
     */
    private function adjustVroomTime($baseSeconds, $deliveryType, $trafficCondition, $weatherCondition)
    {
        // Factores de ajuste para el tiempo base de VROOM
        $deliveryFactors = [
            'express' => 0.9,   // 10% más rápido (prioridad alta)
            'standard' => 1.0,  // Sin cambio
            'economy' => 1.1    // 10% más lento (sin prisa)
        ];

        $trafficFactors = [
            'light' => 1.0,     // Sin impacto adicional
            'moderate' => 1.2,  // 20% más lento
            'heavy' => 1.5      // 50% más lento
        ];

        $weatherFactors = [
            'clear' => 1.0,     // Sin impacto
            'rain' => 1.15,     // 15% más lento
            'storm' => 1.3      // 30% más lento
        ];

        // Aplicar factores al tiempo base de VROOM
        $deliveryFactor = $deliveryFactors[$deliveryType] ?? 1.0;
        $trafficFactor = $trafficFactors[$trafficCondition] ?? 1.2;
        $weatherFactor = $weatherFactors[$weatherCondition] ?? 1.0;

        $adjustedSeconds = $baseSeconds * $deliveryFactor * $trafficFactor * $weatherFactor;

        // Tiempo adicional por paradas (más realista para entregas)
        $additionalMinutes = $this->calculateAdditionalTimeForDelivery($deliveryType);
        $additionalSeconds = $additionalMinutes * 60;

        $totalSeconds = $adjustedSeconds + $additionalSeconds;
        $totalMinutes = round($totalSeconds / 60);

        // Crear rango de estimación
        $minMinutes = round($totalMinutes * 0.9);   // 10% más rápido
        $maxMinutes = round($totalMinutes * 1.3);   // 30% más lento

        // ETA
        $eta = now()->addMinutes($totalMinutes);

        return [
            'vroom_base_time' => [
                'seconds' => $baseSeconds,
                'minutes' => round($baseSeconds / 60, 1),
                'formatted' => $this->formatDuration($baseSeconds)
            ],
            'adjusted_time' => [
                'seconds' => round($totalSeconds),
                'minutes' => $totalMinutes,
                'formatted' => $this->formatDeliveryTime($totalMinutes)
            ],
            'time_range' => [
                'min_minutes' => $minMinutes,
                'max_minutes' => $maxMinutes,
                'range_text' => $this->formatDeliveryTime($minMinutes) . ' - ' . $this->formatDeliveryTime($maxMinutes)
            ],
            'eta' => [
                'timestamp' => $eta->toISOString(),
                'time' => $eta->format('H:i'),
                'date_time' => $eta->format('d/m/Y H:i'),
                'relative' => $eta->diffForHumans()
            ],
            'adjustment_factors' => [
                'delivery_factor' => $deliveryFactor . 'x (' . $deliveryType . ')',
                'traffic_factor' => $trafficFactor . 'x (' . $trafficCondition . ')',
                'weather_factor' => $weatherFactor . 'x (' . $weatherCondition . ')',
                'additional_time_minutes' => $additionalMinutes,
                'note' => 'Tiempo base de VROOM ajustado con factores externos'
            ]
        ];
    }

    /**
     * Tiempo adicional específico para entregas
     */
    private function calculateAdditionalTimeForDelivery($deliveryType)
    {
        // Tiempo realista por entrega (carga/descarga, búsqueda de dirección, etc.)
        $deliveryTimes = [
            'express' => 3,    // 3 min (entrega rápida)
            'standard' => 5,   // 5 min (entrega estándar) 
            'economy' => 8     // 8 min (entrega sin prisa)
        ];

        return $deliveryTimes[$deliveryType] ?? 5;
    }

    /**
     * Formatea duración en segundos a texto legible
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        } elseif ($seconds < 3600) {
            $minutes = round($seconds / 60);
            return $minutes . ' min';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'min';
        }
    }

    /**
     * Formatea distancia
     */
    private function formatDistance($km)
    {
        if ($km < 1) {
            return round($km * 1000) . ' m';
        } else {
            return round($km, 2) . ' km';
        }
    }

    /**
     * Obtiene la posición inicial del vehículo (versión simplificada)
     */
    private function getVehicleStartPositionSimple($vehicleId)
    {
        try {
            // 1. Buscar en asignaciones de rutas existentes
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

            // 3. Posición por defecto (Tegucigalpa) - mismo que usas en otras funciones
            return [
                'lat' => 14.10719371522,
                'lng' => -87.18718148767947,
                'source' => 'fallback'
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo posición del vehículo', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            return [
                'lat' => 14.0667,
                'lng' => -87.1875,
                'source' => 'error_fallback'
            ];
        }
    }

    /**
     * Calcula distancia simple (método de respaldo)
     */
    private function calculateDistanceSimple($lat1, $lng1, $lat2, $lng2)
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
    private function formatDeliveryTime($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        } else {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;

            if ($remainingMinutes == 0) {
                return $hours . 'h';
            } else {
                return $hours . 'h ' . $remainingMinutes . 'min';
            }
        }
    }

    public function captureVehicleLocations(Request $request)
    {
        try {
            $validated = $request->validate([
                'locations' => 'required|array',
                'locations.*.vehicle_id' => 'required|integer|exists:users,id',
                'locations.*.lat' => 'required|numeric|between:-90,90',
                'locations.*.lng' => 'required|numeric|between:-180,180',
                'locations.*.accuracy' => 'sometimes|numeric'
            ]);

            $user = Auth::user();

            if (!$user->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo administradores pueden capturar ubicaciones'
                ], 403);
            }

            $capturedLocations = [];

            foreach ($validated['locations'] as $location) {
                // Guardar/actualizar ubicación del vehículo
                $this->updateVehicleLocation(
                    $location['vehicle_id'],
                    $location['lat'],
                    $location['lng'],
                    $location['accuracy'] ?? null
                );

                $vehicle = User::find($location['vehicle_id']);
                $capturedLocations[] = [
                    'vehicle_id' => $location['vehicle_id'],
                    'vehicle_name' => $vehicle ? $vehicle->name : 'Desconocido',
                    'coordinates' => [
                        'lat' => $location['lat'],
                        'lng' => $location['lng']
                    ],
                    'captured_at' => now()->toISOString()
                ];
            }

            Log::info('Ubicaciones de vehículos capturadas', [
                'count' => count($capturedLocations),
                'captured_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'captured_locations' => $capturedLocations,
                    'message' => 'Ubicaciones capturadas exitosamente'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error capturando ubicaciones de vehículos', [
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
     * Actualiza o crea registro de ubicación del vehículo
     */
    private function updateVehicleLocation($vehicleId, $lat, $lng, $accuracy = null)
    {
        try {
            // Verificar si existe tabla de ubicaciones de vehículos
            if (!Schema::hasTable('vehicle_locations')) {
                // Si no existe, usar el sistema de RouteAssignment para guardar la ubicación
                $this->saveVehicleLocationInAssignment($vehicleId, $lat, $lng, $accuracy);
                return;
            }

            // Si existe la tabla, usarla
            DB::table('vehicle_locations')->updateOrInsert(
                ['user_id' => $vehicleId],
                [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy' => $accuracy,
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );

        } catch (\Exception $e) {
            Log::warning('Error actualizando ubicación de vehículo', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            // Fallback: guardar en RouteAssignment
            $this->saveVehicleLocationInAssignment($vehicleId, $lat, $lng, $accuracy);
        }
    }

    /**
     * Guarda ubicación del vehículo usando el sistema de RouteAssignment existente
     */
    private function saveVehicleLocationInAssignment($vehicleId, $lat, $lng, $accuracy = null)
    {
        try {
            // Crear o actualizar un registro especial para la ubicación
            RouteAssignment::updateOrCreate(
                [
                    'user_id' => $vehicleId,
                    'route_hash' => 'LOCATION_' . $vehicleId // Hash especial para ubicaciones
                ],
                [
                    'route_data' => json_encode([
                        'type' => 'vehicle_location',
                        'vehicle' => $vehicleId,
                        'current_location' => [
                            'lat' => $lat,
                            'lng' => $lng,
                            'accuracy' => $accuracy,
                            'captured_at' => now()->toISOString()
                        ]
                    ]),
                    'calculated_at' => now()
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error guardando ubicación en RouteAssignment', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * MODIFICAR: Función index mejorada con captura de ubicaciones GPS
     */
    public function index(Request $request)
    {
        $user = Auth::user();
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
            Log::info('Recalculando rutas con VROOM usando ubicaciones actuales');

            // MEJORA: Obtener ubicaciones actuales de los vehículos si están disponibles
            $vehiclesWithCurrentLocations = $this->getVehiclesWithCurrentLocations($allVehicles);

            // Datos para la API de VROOM con ubicaciones actualizadas
            $data = [
                "vehicles" => $vehiclesWithCurrentLocations,
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            $vroomUrl = config('app.vroom_url', 'http://154.38.191.25:3000');
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

                $vehicleId = $routeData['vehicle'];
                $motorista = User::find($vehicleId);
                $vehicleName = $motorista ? $motorista->name : "Vehículo $vehicleId";

                $motoristPosition = User::permission('Motorista')
                    ->orderBy('id')
                    ->pluck('id')
                    ->search($vehicleId) + 1;

                $routeIdentifier = "Motorista $motoristPosition";

                // MEJORA: Incluir información de ubicación actual en la ruta
                $currentLocation = $this->getVehicleCurrentLocation($vehicleId);

                $route = [
                    'vehicle' => $vehicleId,
                    'vehicle_name' => $vehicleName,
                    'route_identifier' => $routeIdentifier,
                    'motorist_position' => $motoristPosition,
                    'geometry' => $routeData['geometry'],
                    'steps' => $this->formatSteps($routeData['steps'], $jobs),
                    'distance' => $routeData['distance'] ?? null,
                    'duration' => $routeData['duration'] ?? null,
                    'current_location' => $currentLocation, // NUEVA: Ubicación actual
                    'calculated_with_gps' => $currentLocation['source'] !== 'fallback'
                ];

                $allRoutes[] = $route;
            }

            // Guardar las asignaciones en la base de datos
            $jobsHash = $this->calculateJobsHash($jobs);
            $this->saveRouteAssignments($allRoutes, $jobsHash);

            Log::info('Rutas guardadas en la base de datos', [
                'count' => count($allRoutes),
                'jobs_hash' => $jobsHash,
                'with_gps_locations' => count(array_filter($allRoutes, function ($r) {
                    return $r['calculated_with_gps'];
                }))
            ]);
        } else {
            // Usar las rutas almacenadas
            Log::info('Usando rutas almacenadas de la base de datos');
            $allRoutes = $this->getStoredRouteAssignments();
        }

        // Resto del código existente...
        session(['all_routes' => $allRoutes]);

        $filteredRoutes = $allRoutes;
        if ($isMotorista && !$isAdmin) {
            $filteredRoutes = array_filter($allRoutes, function ($route) use ($user) {
                return $route['vehicle'] == $user->id;
            });
            $filteredRoutes = array_values($filteredRoutes);
        }

        if ($request->wantsJson() || $request->get('format') === 'json') {
            return response()->json([
                'routes' => $filteredRoutes,
                'vehicles' => $allVehicles,
                'jobs' => $jobs,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_admin' => $isAdmin,
                    'is_motorista' => $isMotorista
                ]
            ]);
        }

        return view('vehicle.show', [
            'routes' => $filteredRoutes,
            'vehicles' => $allVehicles,
            'jobs' => $jobs,
            'is_admin' => $isAdmin,
            'is_motorista' => $isMotorista,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'gps_enabled' => true
        ]);
    }

    /**
     * Obtiene vehículos con sus ubicaciones actuales si están disponibles
     */
    private function getVehiclesWithCurrentLocations($defaultVehicles)
    {
        $updatedVehicles = [];

        foreach ($defaultVehicles as $vehicle) {
            $vehicleId = $vehicle['id'];
            $currentLocation = $this->getVehicleCurrentLocation($vehicleId);

            // Usar ubicación actual si está disponible, sino usar la por defecto
            $updatedVehicle = $vehicle;
            if ($currentLocation && $currentLocation['source'] !== 'fallback') {
                $updatedVehicle['start'] = [$currentLocation['lng'], $currentLocation['lat']];
                $updatedVehicle['end'] = [$currentLocation['lng'], $currentLocation['lat']];

                Log::debug('Vehículo con ubicación GPS', [
                    'vehicle_id' => $vehicleId,
                    'location' => [$currentLocation['lat'], $currentLocation['lng']],
                    'source' => $currentLocation['source']
                ]);
            }

            $updatedVehicles[] = $updatedVehicle;
        }

        return $updatedVehicles;
    }

    /**
     * Obtiene la ubicación actual de un vehículo específico
     */
    private function getVehicleCurrentLocation($vehicleId)
    {
        try {
            // Opción 1: Buscar en tabla de ubicaciones si existe
            if (Schema::hasTable('vehicle_locations')) {
                $location = DB::table('vehicle_locations')
                    ->where('user_id', $vehicleId)
                    ->where('updated_at', '>=', now()->subHours(1)) // Solo ubicaciones recientes
                    ->first();

                if ($location) {
                    return [
                        'lat' => $location->latitude,
                        'lng' => $location->longitude,
                        'accuracy' => $location->accuracy ?? null,
                        'source' => 'vehicle_locations_table',
                        'updated_at' => $location->updated_at
                    ];
                }
            }

            // Opción 2: Buscar en RouteAssignment (ubicaciones capturadas)
            $locationAssignment = RouteAssignment::where('user_id', $vehicleId)
                ->where('route_hash', 'LIKE', 'LOCATION_%')
                ->latest('calculated_at')
                ->first();

            if ($locationAssignment && $locationAssignment->route_data) {
                $routeData = $locationAssignment->route_data;
                if (isset($routeData['current_location'])) {
                    $loc = $routeData['current_location'];
                    return [
                        'lat' => $loc['lat'],
                        'lng' => $loc['lng'],
                        'accuracy' => $loc['accuracy'] ?? null,
                        'source' => 'route_assignment_location',
                        'updated_at' => $locationAssignment->calculated_at
                    ];
                }
            }

            // Opción 3: Buscar en rutas existentes (último punto conocido)
            $routeAssignment = RouteAssignment::where('user_id', $vehicleId)
                ->where('route_hash', 'NOT LIKE', 'LOCATION_%')
                ->latest('calculated_at')
                ->first();

            if ($routeAssignment && $routeAssignment->route_data) {
                $routeData = $routeAssignment->route_data;
                if (isset($routeData['steps']) && is_array($routeData['steps'])) {
                    foreach ($routeData['steps'] as $step) {
                        if ($step['type'] === 'start' && isset($step['location'])) {
                            return [
                                'lat' => $step['location'][1],
                                'lng' => $step['location'][0],
                                'source' => 'route_assignment_start',
                                'updated_at' => $routeAssignment->calculated_at
                            ];
                        }
                    }
                }
            }

            // Fallback: usar posición por defecto
            return [
                'lat' => 14.0667,
                'lng' => -87.1875,
                'source' => 'fallback'
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo ubicación actual del vehículo', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            return [
                'lat' => 14.0667,
                'lng' => -87.1875,
                'source' => 'error_fallback'
            ];
        }
    }

    /**
     * MODIFICAR: SeguirRuta mejorado para usar rutas existentes
     */
    public function seguirRuta(Request $request)
    {
        try {
            Log::info('🧭 Iniciando seguirRuta', [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            $isAdmin = $user->can('Administrador');
            $isMotorista = $user->can('Motorista');

            if (!$isAdmin && !$isMotorista) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para acceder a esta funcionalidad.'
                ], 403);
            }

            // Validación
            $validated = $request->validate([
                'current_location' => 'required|array|size:2',
                'current_location.0' => 'required|numeric|between:-90,90',
                'current_location.1' => 'required|numeric|between:-180,180',
                'vehicle_id' => 'sometimes|integer|exists:users,id',
                'force_recalculate' => 'sometimes|boolean' // NUEVA: Opción para forzar recálculo
            ]);

            $currentLocation = $validated['current_location'];
            $vehicleId = $isAdmin && isset($validated['vehicle_id'])
                ? $validated['vehicle_id']
                : $user->id;

            // MEJORA: Actualizar ubicación actual del vehículo
            $this->updateVehicleLocation(
                $vehicleId,
                $currentLocation[0],
                $currentLocation[1],
                null // accuracy - podrías agregarlo al request si lo necesitas
            );

            Log::info('Ubicación actualizada para vehículo', [
                'vehicle_id' => $vehicleId,
                'location' => $currentLocation
            ]);

            // NUEVA LÓGICA: Verificar si existe una ruta válida y reciente para este vehículo
            $forceRecalculate = $validated['force_recalculate'] ?? false;
            $existingRoute = $this->getExistingValidRoute($vehicleId, $forceRecalculate);

            if ($existingRoute && !$forceRecalculate) {
                Log::info('Usando ruta existente válida', [
                    'vehicle_id' => $vehicleId,
                    'route_age_minutes' => now()->diffInMinutes($existingRoute['calculated_at'])
                ]);

                // Ajustar la ruta existente con la nueva ubicación
                $adjustedRoute = $this->adjustRouteForCurrentLocation($existingRoute, $currentLocation);

                return response()->json([
                    'success' => true,
                    'routes' => [$adjustedRoute],
                    'message' => 'Ruta existente ajustada para tu ubicación actual',
                    'route_source' => 'existing_route_adjusted',
                    'timestamp' => now()->toISOString()
                ]);
            }

            // Si no hay ruta válida o se fuerza recálculo, generar nueva ruta
            Log::info('Generando nueva ruta desde ubicación actual', [
                'vehicle_id' => $vehicleId,
                'forced' => $forceRecalculate
            ]);

            // Resto del código existente de seguirRuta...
            $motorista = User::find($vehicleId);
            if (!$motorista) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vehículo no encontrado'
                ], 404);
            }

            $vehicle = [
                "id" => $vehicleId,
                "start" => [$currentLocation[1], $currentLocation[0]],
                "end" => [$currentLocation[1], $currentLocation[0]],
                "capacity" => [2]
            ];

            $jobs = $this->getValidJobs();

            if (empty($jobs)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay pedidos válidos para procesar'
                ], 400);
            }

            $vroomRequest = [
                "vehicles" => [$vehicle],
                "jobs" => $jobs,
                "options" => ["g" => true]
            ];

            $vroomUrl = config('app.vroom_url', 'http://154.38.191.25:3000');
            $response = Http::timeout(30)->retry(2, 1000)->post($vroomUrl, $vroomRequest);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al calcular la ruta desde tu ubicación actual'
                ], 500);
            }

            $result = $response->json();

            if (!isset($result['routes']) || empty($result['routes'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontraron rutas disponibles desde tu ubicación'
                ], 404);
            }

            $routeData = $result['routes'][0];

            if (!isset($routeData['geometry'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'La ruta calculada no contiene información geométrica'
                ], 500);
            }

            // MEJORA: Guardar la nueva ruta en la base de datos para reutilización
            $newRoute = [
                'vehicle' => $vehicleId,
                'vehicle_name' => $motorista->name,
                'geometry' => $routeData['geometry'],
                'steps' => $this->formatSteps($routeData['steps'], $jobs),
                'distance' => $routeData['distance'] ?? null,
                'duration' => $routeData['duration'] ?? null,
                'cost' => $routeData['cost'] ?? null,
                'generated_from_gps' => true,
                'gps_location' => $currentLocation
            ];

            // Guardar para reutilización futura
            $this->saveRealTimeRoute($vehicleId, $newRoute, $jobs);

            return response()->json([
                'success' => true,
                'routes' => [$newRoute],
                'message' => 'Nueva ruta calculada y guardada',
                'route_source' => 'new_route_from_gps',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error crítico en seguirRuta mejorado', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtiene una ruta existente válida para el vehículo
     */
    private function getExistingValidRoute($vehicleId, $forceRecalculate = false)
    {
        if ($forceRecalculate) {
            return null;
        }

        try {
            // Buscar rutas recientes (últimas 2 horas)
            $recentRoute = RouteAssignment::where('user_id', $vehicleId)
                ->where('calculated_at', '>=', now()->subHours(2))
                ->where('route_hash', 'NOT LIKE', 'LOCATION_%') // Excluir registros de ubicación
                ->latest('calculated_at')
                ->first();

            if ($recentRoute && $recentRoute->route_data) {
                $routeData = $recentRoute->route_data;

                // Verificar que la ruta tenga la información necesaria
                if (isset($routeData['geometry']) && isset($routeData['steps'])) {
                    $routeData['calculated_at'] = $recentRoute->calculated_at;
                    return $routeData;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error obteniendo ruta existente', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Ajusta una ruta existente para la ubicación actual
     */
    private function adjustRouteForCurrentLocation($existingRoute, $currentLocation)
    {
        try {
            // La ruta base se mantiene igual, pero se ajusta el contexto
            $adjustedRoute = $existingRoute;

            // Agregar información de ubicación actual
            $adjustedRoute['current_gps_location'] = [
                'lat' => $currentLocation[0],
                'lng' => $currentLocation[1],
                'updated_at' => now()->toISOString()
            ];

            // Calcular distancia desde ubicación actual al primer destino
            if (isset($existingRoute['steps']) && count($existingRoute['steps']) > 0) {
                foreach ($existingRoute['steps'] as $index => $step) {
                    if ($step['type'] === 'job' && isset($step['location'])) {
                        $stepLat = $step['location'][1]; // VROOM usa [lng, lat]
                        $stepLng = $step['location'][0];

                        $distanceToStep = $this->calculateDistanceSimple(
                            $currentLocation[0],
                            $currentLocation[1],
                            $stepLat,
                            $stepLng
                        );

                        $adjustedRoute['steps'][$index]['distance_from_current'] = [
                            'km' => round($distanceToStep, 3),
                            'formatted' => $distanceToStep < 1
                                ? round($distanceToStep * 1000) . ' m'
                                : round($distanceToStep, 2) . ' km'
                        ];
                    }
                }
            }

            // Marcar como ruta ajustada
            $adjustedRoute['route_type'] = 'existing_adjusted';
            $adjustedRoute['adjustment_time'] = now()->toISOString();

            return $adjustedRoute;

        } catch (\Exception $e) {
            Log::error('Error ajustando ruta existente', [
                'error' => $e->getMessage()
            ]);

            // Si hay error, devolver la ruta original
            return $existingRoute;
        }
    }

    /**
     * Guarda una ruta generada en tiempo real
     */
    private function saveRealTimeRoute($vehicleId, $routeData, $jobs)
    {
        try {
            $jobsHash = $this->calculateJobsHash($jobs);

            // Crear hash único para rutas de tiempo real
            $realtimeHash = 'REALTIME_' . $vehicleId . '_' . now()->timestamp;

            RouteAssignment::create([
                'user_id' => $vehicleId,
                'route_data' => json_encode($routeData),
                'route_hash' => $realtimeHash,
                'calculated_at' => now()
            ]);

            Log::info('Ruta de tiempo real guardada', [
                'vehicle_id' => $vehicleId,
                'hash' => $realtimeHash
            ]);

        } catch (\Exception $e) {
            Log::error('Error guardando ruta de tiempo real', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getAssignedJobs(Request $request, $vehicleId = null)
    {
        try {
            $user = Auth::user();

            // Determinar el vehículo a consultar
            if ($vehicleId && $user->can('Administrador')) {
                $targetVehicleId = $vehicleId;
            } else {
                $targetVehicleId = $user->id;
            }

            // Verificar que el vehículo existe y es motorista
            $vehicle = User::find($targetVehicleId);
            if (!$vehicle || !$vehicle->can('Motorista')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vehículo no encontrado o no es motorista'
                ], 404);
            }

            // Obtener trabajos asignados
            $assignedJobs = $this->getAssignedJobsForVehicle($targetVehicleId);

            // Obtener información adicional del vehículo
            $vehicleInfo = [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'email' => $vehicle->email,
                'current_location' => $this->getVehicleCurrentLocation($targetVehicleId)
            ];

            // Estadísticas
            $stats = [
                'total_assigned' => count($assignedJobs),
                'pending' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'pending')),
                'completed' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'completed')),
                'in_progress' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'in_progress'))
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicle' => $vehicleInfo,
                    'assigned_jobs' => $assignedJobs,
                    'statistics' => $stats,
                    'last_updated' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo trabajos asignados', [
                'vehicle_id' => $vehicleId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar estado de un trabajo específico (admin)
     */
    public function updateJobStatus(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo administradores pueden cambiar el estado de trabajos'
                ], 403);
            }

            $validated = $request->validate([
                'job_id' => 'required|integer',
                'vehicle_id' => 'required|integer|exists:users,id',
                'status' => 'required|string|in:pending,in_progress,completed,cancelled',
                'notes' => 'sometimes|string|max:500'
            ]);

            $updated = $this->updateJobStatus(
                $validated['job_id'],
                $validated['vehicle_id'],
                $validated['status'],
                $validated['notes'] ?? null
            );

            if ($updated) {
                Log::info('Estado de trabajo actualizado por admin', [
                    'job_id' => $validated['job_id'],
                    'vehicle_id' => $validated['vehicle_id'],
                    'new_status' => $validated['status'],
                    'admin_id' => $user->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Estado del trabajo actualizado exitosamente',
                    'data' => [
                        'job_id' => $validated['job_id'],
                        'new_status' => $validated['status'],
                        'updated_by' => $user->name
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo actualizar el estado del trabajo'
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error actualizando estado de trabajo', [
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
     * Reasignar trabajo a otro vehículo (admin)
     */
    public function reassignJob(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo administradores pueden reasignar trabajos'
                ], 403);
            }

            $validated = $request->validate([
                'job_id' => 'required|integer',
                'from_vehicle_id' => 'required|integer|exists:users,id',
                'to_vehicle_id' => 'required|integer|exists:users,id',
                'reason' => 'sometimes|string|max:500'
            ]);

            // Verificar que ambos vehículos son motoristas
            $fromVehicle = User::find($validated['from_vehicle_id']);
            $toVehicle = User::find($validated['to_vehicle_id']);

            if (!$fromVehicle->can('Motorista') || !$toVehicle->can('Motorista')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ambos vehículos deben ser motoristas válidos'
                ], 400);
            }

            DB::transaction(function () use ($validated, $user) {
                // Obtener datos del trabajo actual
                $currentAssignment = $this->getCurrentJobAssignment(
                    $validated['job_id'],
                    $validated['from_vehicle_id']
                );

                if (!$currentAssignment) {
                    throw new \Exception('Asignación actual no encontrada');
                }

                // Cancelar asignación actual
                $this->updateJobStatus(
                    $validated['job_id'],
                    $validated['from_vehicle_id'],
                    'cancelled',
                    'Reasignado a otro vehículo: ' . ($validated['reason'] ?? 'Sin razón especificada')
                );

                // Crear nueva asignación
                $this->createNewJobAssignment(
                    $validated['job_id'],
                    $validated['to_vehicle_id'],
                    $currentAssignment['job_data'],
                    'REASSIGN_' . now()->timestamp
                );

                Log::info('Trabajo reasignado', [
                    'job_id' => $validated['job_id'],
                    'from_vehicle' => $validated['from_vehicle_id'],
                    'to_vehicle' => $validated['to_vehicle_id'],
                    'reason' => $validated['reason'] ?? 'No especificada',
                    'admin_id' => $user->id
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Trabajo reasignado exitosamente',
                'data' => [
                    'job_id' => $validated['job_id'],
                    'from_vehicle' => $fromVehicle->name,
                    'to_vehicle' => $toVehicle->name,
                    'reassigned_by' => $user->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error reasignando trabajo', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al reasignar trabajo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener dashboard de administrador con todas las asignaciones
     */
    public function getAdminDashboard(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo administradores pueden acceder al dashboard'
                ], 403);
            }

            // Obtener todos los motoristas
            $motoristas = User::permission('Motorista')->get();

            $dashboardData = [];
            $totalStats = [
                'total_vehicles' => $motoristas->count(),
                'total_jobs_assigned' => 0,
                'total_pending' => 0,
                'total_completed' => 0,
                'total_in_progress' => 0,
                'total_cancelled' => 0
            ];

            foreach ($motoristas as $motorista) {
                $assignedJobs = $this->getAssignedJobsForVehicle($motorista->id);
                $currentLocation = $this->getVehicleCurrentLocation($motorista->id);

                $vehicleStats = [
                    'total' => count($assignedJobs),
                    'pending' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'pending')),
                    'completed' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'completed')),
                    'in_progress' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'in_progress')),
                    'cancelled' => count(array_filter($assignedJobs, fn($job) => ($job['status'] ?? 'pending') === 'cancelled'))
                ];

                $dashboardData[] = [
                    'vehicle' => [
                        'id' => $motorista->id,
                        'name' => $motorista->name,
                        'email' => $motorista->email
                    ],
                    'current_location' => $currentLocation,
                    'job_statistics' => $vehicleStats,
                    'assigned_jobs' => $assignedJobs,
                    'last_activity' => $this->getLastVehicleActivity($motorista->id)
                ];

                // Sumar a totales
                $totalStats['total_jobs_assigned'] += $vehicleStats['total'];
                $totalStats['total_pending'] += $vehicleStats['pending'];
                $totalStats['total_completed'] += $vehicleStats['completed'];
                $totalStats['total_in_progress'] += $vehicleStats['in_progress'];
                $totalStats['total_cancelled'] += $vehicleStats['cancelled'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicles_data' => $dashboardData,
                    'summary_statistics' => $totalStats,
                    'generated_at' => now()->toISOString(),
                    'generated_by' => $user->name
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando dashboard de admin', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error generando dashboard'
            ], 500);
        }
    }

    /**
     * Funciones de apoyo privadas
     */

    private function getCurrentJobAssignment($jobId, $vehicleId)
    {
        try {
            if ($this->vehicleJobAssignmentsTableExists()) {
                $assignment = DB::table('vehicle_job_assignments')
                    ->where('job_id', $jobId)
                    ->where('user_id', $vehicleId)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->first();

                if ($assignment) {
                    return [
                        'assignment_id' => $assignment->id,
                        'job_data' => json_decode($assignment->job_data, true),
                        'status' => $assignment->status,
                        'assigned_at' => $assignment->assigned_at
                    ];
                }
            } else {
                $assignment = RouteAssignment::where('user_id', $vehicleId)
                    ->where('route_hash', 'LIKE', 'JOB_%_' . $jobId)
                    ->first();

                if ($assignment) {
                    return [
                        'assignment_id' => $assignment->id,
                        'job_data' => $assignment->route_data['job_data'] ?? [],
                        'status' => $assignment->route_data['status'] ?? 'pending',
                        'assigned_at' => $assignment->calculated_at
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error obteniendo asignación actual', [
                'job_id' => $jobId,
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    private function createNewJobAssignment($jobId, $vehicleId, $jobData, $assignmentHash)
    {
        try {
            if ($this->vehicleJobAssignmentsTableExists()) {
                DB::table('vehicle_job_assignments')->insert([
                    'user_id' => $vehicleId,
                    'job_id' => $jobId,
                    'assignment_hash' => $assignmentHash,
                    'status' => 'pending',
                    'job_data' => json_encode($jobData),
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                RouteAssignment::create([
                    'user_id' => $vehicleId,
                    'route_data' => json_encode([
                        'type' => 'job_assignment',
                        'job_id' => $jobId,
                        'job_data' => $jobData,
                        'status' => 'pending'
                    ]),
                    'route_hash' => 'JOB_' . $assignmentHash . '_' . $jobId,
                    'calculated_at' => now()
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error creando nueva asignación', [
                'job_id' => $jobId,
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function getLastVehicleActivity($vehicleId)
    {
        try {
            // Buscar la actividad más reciente del vehículo
            $lastRoute = RouteAssignment::where('user_id', $vehicleId)
                ->latest('calculated_at')
                ->first();

            if ($lastRoute) {
                return [
                    'type' => 'route_calculation',
                    'timestamp' => $lastRoute->calculated_at,
                    'relative_time' => $lastRoute->calculated_at->diffForHumans()
                ];
            }

            return [
                'type' => 'no_activity',
                'timestamp' => null,
                'relative_time' => 'Sin actividad reciente'
            ];

        } catch (\Exception $e) {
            return [
                'type' => 'error',
                'timestamp' => null,
                'relative_time' => 'Error obteniendo actividad'
            ];
        }
    }

    /**
     * API para limpiar asignaciones completadas o canceladas (admin)
     */
    public function cleanupCompletedAssignments(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->can('Administrador')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo administradores pueden limpiar asignaciones'
                ], 403);
            }

            $daysOld = $request->input('days_old', 7); // Por defecto 7 días

            $deletedCount = 0;

            if ($this->vehicleJobAssignmentsTableExists()) {
                $deletedCount = DB::table('vehicle_job_assignments')
                    ->whereIn('status', ['completed', 'cancelled'])
                    ->where('updated_at', '<', now()->subDays($daysOld))
                    ->delete();
            } else {
                $deletedCount = RouteAssignment::where('route_hash', 'LIKE', 'JOB_%')
                    ->where('calculated_at', '<', now()->subDays($daysOld))
                    ->whereJsonContains('route_data->status', ['completed', 'cancelled'])
                    ->delete();
            }

            Log::info('Asignaciones limpiadas', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld,
                'admin_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deletedCount} asignaciones antiguas",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'days_old' => $daysOld
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error limpiando asignaciones', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al limpiar asignaciones'
            ], 500);
        }
    }

    // Agregar al VroomController - función de DEBUG
    public function debugAssignments(Request $request)
    {
        $user = Auth::user();

        // Ver datos actuales
        $routeAssignments = RouteAssignment::where('user_id', $user->id)->get();
        $vehicleJobs = [];

        if (Schema::hasTable('vehicle_job_assignments')) {
            $vehicleJobs = DB::table('vehicle_job_assignments')
                ->where('user_id', $user->id)
                ->get();
        }

        return response()->json([
            'user_id' => $user->id,
            'route_assignments_count' => $routeAssignments->count(),
            'route_assignments' => $routeAssignments,
            'vehicle_jobs_count' => count($vehicleJobs),
            'vehicle_jobs' => $vehicleJobs,
            'has_vehicle_jobs_table' => Schema::hasTable('vehicle_job_assignments')
        ]);
    }

}