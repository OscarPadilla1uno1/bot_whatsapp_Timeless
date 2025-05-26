<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class DashboardController extends Controller
{
    public function admin()
    {
        if (!auth()->user()->can('Administrador')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }

        // Traer datos específicos para el dashboard del admin
        $menu = DB::select('CALL obtener_menu_diario()');

        // Ventas mensuales (solo pedidos entregados)
        $ventasMensuales = DB::table('pedidos')
            ->selectRaw('DATE_FORMAT(fecha_pedido, "%Y-%m") as mes, SUM(total) as total')
            ->where('estado', 'ENTREGADO')
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        // Platillos más vendidos (solo en pedidos entregados)
        $platillosMasVendidos = DB::table('detalle_pedido')
            ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
            ->join('platillos', 'detalle_pedido.platillo_id', '=', 'platillos.id')
            ->where('pedidos.estado', 'ENTREGADO')
            ->select('platillos.nombre', DB::raw('SUM(detalle_pedido.cantidad) as total_vendido'))
            ->groupBy('platillos.nombre')
            ->orderByDesc('total_vendido')
            ->limit(10)
            ->get();

        // Clientes frecuentes (solo pedidos entregados)
        $clientesFrecuentes = DB::table('pedidos')
            ->join('clientes', 'pedidos.cliente_id', '=', 'clientes.id')
            ->where('pedidos.estado', 'ENTREGADO')
            ->select('clientes.nombre', DB::raw('COUNT(pedidos.id) as total_pedidos'))
            ->groupBy('clientes.nombre')
            ->orderByDesc('total_pedidos')
            ->limit(10)
            ->get();

        // Ventas de los últimos 7 días
        $ventasSemanales = DB::table('pedidos')
            ->selectRaw('DATE(fecha_pedido) as dia, SUM(total) as total')
            ->where('estado', 'ENTREGADO')
            ->whereBetween('fecha_pedido', [
                Carbon::now()->subDays(6)->toDateString(),
                Carbon::now()->toDateString()
            ])
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();




        return view('admin.dashboard', compact('menu', 'ventasMensuales', 'platillosMasVendidos', 'clientesFrecuentes', 'ventasSemanales'));
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
