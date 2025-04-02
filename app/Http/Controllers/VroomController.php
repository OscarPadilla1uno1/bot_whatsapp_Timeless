<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VroomController extends Controller
{
    public function index()
    {
        // Varios repartidores (con diferentes puntos de inicio)
        $data = [
            "vehicles" => [
                [
                    "id" => 1,
                    "start" => [-87.1875, 14.0667],
                    "end" => [-87.1875, 14.0667],
                    "capacity" => [3]
                ],
                [
                    "id" => 2,
                    "start" => [-87.1890, 14.0680],
                    "end" => [-87.1890, 14.0680],
                    "capacity" => [3]
                ],
            ],
            "jobs" => [
                ["id" => 1, "location" => [-87.1800, 14.0700], "delivery" => [1]],
                ["id" => 2, "location" => [-87.1820, 14.0720], "delivery" => [1]],
                ["id" => 3, "location" => [-87.1850, 14.0740], "delivery" => [1]],
                ["id" => 4, "location" => [-87.1830, 14.0685], "delivery" => [1]],
                ["id" => 5, "location" => [-87.21999967445643, 14.062946497848914], "delivery" => [1]],
                ["id" => 6, "location" => [-87.21926306490725, 14.060941348644857], "delivery" => [1]]
            ],
            "options" => [
                "g" => true  // Solicita geometría explícitamente
            ]

        ];


        $response = Http::post('http://154.38.191.25:3000/?geometry=true', $data);
        $result = $response->json();

        if (!isset($result['routes'])) {
            // Opcional: ver la respuesta completa para depurar
            dd('Error desde VROOM:', $result);
        }

        return view('vroom.map', compact('result'));
    }
}
