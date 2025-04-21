<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VroomController extends Controller
{
    private $vehicles = [
        [
            "id" => 1,
            "start" => [-87.1875, 14.0667],
            "end" => [-87.1875, 14.0667],
            "capacity" => [2]
        ],
        [
            "id" => 2,
            "start" => [-87.1890, 14.0680],
            "end" => [-87.1890, 14.0680],
            "capacity" => [2]
        ]
    ];

    public function index(Request $request)
    {
        $jobs = $this->getValidJobs();

        if (empty($jobs)) {
            return back()->with('error', 'No hay pedidos válidos para procesar');
        }

        $data = [
            "vehicles" => $this->vehicles,
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

        $routes = [];
        foreach ($result['routes'] as $index => $route) {
            if (!isset($route['geometry'])) {
                Log::warning('Ruta sin geometría', $route);
                continue;
            }

            $routes[] = [
                'vehicle' => $index + 1,
                'geometry' => $route['geometry'],
                'steps' => $this->formatSteps($route['steps'], $jobs)
            ];
        }

        if (empty($routes)) {
            return back()->with('error', 'No se generaron rutas con geometría válida');
        }

        return view('vehicle.show', [
            'routes' => $routes,
            'vehicles' => $this->vehicles,
            'jobs' => $jobs
        ]);
    }

    public function seguirRuta(Request $request)
    {
        // Validación de entrada
        $validated = $request->validate([
            'current_location' => 'required|array|size:2',
            'current_location.0' => 'required|numeric', // longitud
            'current_location.1' => 'required|numeric'  // latitud
        ]);

        try {
            // Obtener ubicación actual desde el request
            $currentLocation = $validated['current_location'];

            // Configurar vehículo con ubicación actual
            $vehicle = [
                "id" => 1,
                "start" => [$currentLocation[1], $currentLocation[0]], // [lat, lng]
                "end" => [$currentLocation[1], $currentLocation[0]],
                "capacity" => [2]
            ];

            // Obtener trabajos/pedidos válidos
            $jobs = $this->getValidJobs();

            if (empty($jobs)) {
                return response()->json([
                    'error' => 'No hay pedidos válidos para procesar'
                ], 400);
            }

            // Configurar solicitud para VROOM
            $vroomRequest = [
                "vehicles" => [$vehicle],
                "jobs" => $jobs,
                "options" => ["g" => true] // Habilitar geometría
            ];

            // Enviar solicitud a VROOM
            $response = Http::timeout(30)->post('http://154.38.191.25:3000', $vroomRequest);
            
            if ($response->failed()) {
                return response()->json([
                    'error' => 'Error al calcular la ruta desde tu ubicación actual',
                    'vroom_error' => $response->body()
                ], 500);
            }

            $result = $response->json();

            if (!isset($result['routes'])) {
                return response()->json([
                    'error' => 'Formato de respuesta inválido desde el servidor de rutas'
                ], 500);
            }

            // Procesar la primera ruta
            $route = $result['routes'][0];

            return response()->json([
                'success' => true,
                'routes' => [
                    [
                        'geometry' => $route['geometry'],
                        'steps' => $this->formatSteps($route['steps'], $jobs)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
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

    private function formatSteps($steps, $jobs)
    {
        return array_map(function ($step) use ($jobs) {
            $formatted = [
                'type' => $step['type'],
                'location' => $step['location']
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
}
