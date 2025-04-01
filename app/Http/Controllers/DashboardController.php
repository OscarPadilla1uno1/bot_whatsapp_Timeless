<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;


class DashboardController extends Controller
{
    public function admin()
    {
        if (!auth()->user()->can('admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        return view('admin.dashboard');
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
        if (!auth()->user()->can('cocina')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        return view('cocina.dashboard');
    }
}
