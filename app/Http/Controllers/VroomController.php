<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class VroomController extends Controller
{
    // Vehículos definidos correctamente
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
        // Obtener pedidos con coordenadas válidas
        $jobs = DB::table('pedidos')
            ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
            ->select(
                'pedidos.id',
                'pedidos.latitud',
                'pedidos.longitud',
                'clientes.nombre as cliente_nombre'
            )
            ->get()
            ->map(function($pedido) {
                return [
                    "id" => $pedido->id,
                    "location" => [$pedido->longitud, $pedido->latitud],
                    "delivery" => [1],
                    "cliente" => $pedido->cliente_nombre
                ];
            })->toArray();

        // Configurar datos para VROOM
        $data = [
            "vehicles" => $this->vehicles,
            "jobs" => $jobs,
            "options" => ["g" => true]
        ];

        // Enviar a VROOM
        $response = Http::post('http://154.38.191.25:3000', $data);
        $result = $response->json();
        

        if (!isset($result['routes'])) {
            return back()->with('error', 'Error al calcular rutas: ' . json_encode($result));
        }

        // Guardar en sesión
        session(['vroom_data' => [
            'routes' => $result['routes'],
            'vehicles' => $this->vehicles,
            'jobs' => $jobs
        ]]);

        return redirect()->route('vehicle.show', ['id' => 1]);
    }

    public function showVehicle($id)
    {
        $data = session('vroom_data');
        
        if (!$data || !isset($data['routes'][$id - 1])) {
            return redirect()->route('vroom.index')
                   ->with('error', 'Ruta no disponible para este vehículo');
        }

        return view('vehicle.show', [
            'route' => $data['routes'][$id - 1],
            'vehicleId' => $id,
            'totalVehicles' => count($data['vehicles'])
        ]);
    }
}