<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function admin()
    {
        if (!auth()->user()->can('Administrador')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        // Traer datos específicos para el dashboard del admin
        $menu = DB::select('CALL obtener_menu_diario()');
        return view('admin.dashboard', compact('menu'));
    }

    public function motorista()
    {
        if (!auth()->user()->can('Motorista')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        return view('motorista.dashboard');
    }

    public function cocina()
    {
        if (!auth()->user()->can('Cocina')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        return view('cocina.dashboard');
    }
}
