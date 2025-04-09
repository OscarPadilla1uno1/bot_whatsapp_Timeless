<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

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
            "options" => ["g" => true] // Asegúrate de que la geometría está habilitada
        ];

        // Verifica la URL del servidor VROOM
        $vroomUrl = 'http://154.38.191.25:3000';

        // Debug: Ver los datos que se enviarán
        logger('Datos enviados a VROOM:', $data);

        $response = Http::timeout(30)->post($vroomUrl, $data);

        // Debug: Ver la respuesta cruda
        logger('Respuesta de VROOM:', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        $result = $response->json();

        if (!isset($result['routes'])) {
            logger('Error en respuesta VROOM:', $result);
            return back()->with('error', 'Error al calcular rutas. Verifica los logs.');
        }

        // Transformar las rutas para la vista
        $routes = [];
        foreach ($result['routes'] as $index => $route) {
            if (!isset($route['geometry'])) {
                logger('Ruta sin geometría:', $route);
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

    private function getValidJobs()
    {
        return DB::table('pedidos')
            ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
            ->select(
                'pedidos.id',
                'pedidos.latitud',
                'pedidos.longitud',
                'clientes.nombre as cliente_nombre'
            )
            ->whereNotNull('pedidos.latitud')
            ->whereNotNull('pedidos.longitud')
            ->get()
            ->map(function ($pedido) {
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
                $formatted['job_details'] = $job;
            }

            return $formatted;
        }, $steps);
    }

    public function seguirRuta(Request $request)
    {
        $start = $request->input('current_location');
        $vehiclesRuta = [
            [
                "id" => 1,
                "start" => $start,
                "end" => $start,
                "capacity" => [2]
            ],
            [
                "id" => 2,
                "start" => $start,
                "end" => $start,
                "capacity" => [2]
            ]
        ];
        $jobs = $this->getValidJobs();

        if (empty($jobs)) {
            return back()->with('error', 'No hay pedidos válidos para procesar');
        }

        $vroomRequest = [
            "vehicles" => $vehiclesRuta,
            "jobs" => $jobs,
            "options" => ["g" => true] 
        ];

        $response = Http::post('http://154.38.191.25:3000', $vroomRequest); 
        
        return $response->json();
    }

}