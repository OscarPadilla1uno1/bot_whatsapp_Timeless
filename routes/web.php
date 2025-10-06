<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CheckPermission;
use App\Http\Controllers\HoraController;
use App\Models\Pago;
use Dnetix\Redirection\PlacetoPay;
use App\Models\Pedido;
use Illuminate\Http\Request;


Route::get('/routes', [VroomController::class, 'index'])->name('routes');

// Nueva ruta para vista individual de motorista
Route::middleware(['auth'])->group(function () {
    Route::get('/motorista/mi-ruta', [VroomController::class, 'showDriverRoute'])
        ->name('motorista.mi_ruta')
        ->can('Motorista');

    Route::get('/motorista/{userId}/ruta', [VroomController::class, 'showDriverRoute'])
        ->name('motorista.ruta')
        ->can('Administrador'); // Solo admin puede ver rutas de otros motoristas
});

Route::post('/seguir', [VroomController::class, 'seguirRuta'])->name('seguir.ruta');
Route::get('/seguir', [VroomController::class, 'seguirRuta'])->name('seguir.ruta.get');
Route::get('/gps-data', [VroomController::class, 'getGpsData'])->name('gps.data')->middleware('auth');
Route::post('/emergency', [VroomController::class, 'emergencyAlert'])->name('emergency.alert')->middleware('auth');
Route::post('/get-optimized-route', [VroomController::class, 'getOptimizedRoute'])->name('get.optimized.route')->middleware('auth');

// Nueva ruta para marcar entregas como completadas
Route::post('/mark-delivery-completed', [VroomController::class, 'markDeliveryCompleted'])
    ->name('mark.delivery.completed')
    ->middleware('auth')
    ->can('Motorista');

Route::get('/admin/menu/MenuHoy', [AdminController::class, 'obtenerMenuHoy'])->name('admin.menu.hoy');

Route::get('/', function () {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login');
    }

    // Redirigir según permisos
    if ($user->hasPermissionTo('Administrador')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasPermissionTo('Motorista')) {
        return redirect()->route('motorista.dashboard');
    } elseif ($user->hasPermissionTo('Cocina')) {
        return redirect()->route('cocina.pedidosCocina');
    }

    // Usuario sin permisos válidos
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    abort(403, 'No tienes permiso para acceder a esta página.');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/motorista/dashboard', [DashboardController::class, 'motorista'])->name('motorista.dashboard');
    Route::get('/cocina/dashboard', [DashboardController::class, 'cocina'])->name('cocina.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//Rutas de admin

Route::middleware('auth')->group(function () {
    Route::get('/admin/menu-diario', [AdminController::class, 'paginaMenuDelDia'])->name('admin.menu')->can('Administrador');
    Route::post('/menu/agregar', [AdminController::class, 'agregarPlatillo'])->name('admin.menu.agregar')->can('Administrador');
    Route::get('/admin/menu/fecha', [AdminController::class, 'obtenerMenuPorFecha'])->name('admin.menu.fecha')->can('Administrador');
    Route::post('/admin/menu/eliminar', [AdminController::class, 'eliminarPlatillo'])->name('admin.menu.eliminar')->can('Administrador');
    Route::post('/admin/menu/actualizar-cantidad', [AdminController::class, 'actualizarCantidad'])->name('admin.menu.actualizarCantidad')->can('Administrador');
    Route::get('/admin/envios-gratis/{fecha}', [AdminController::class, 'estadoEnvioGratis'])->name('admin.envios-gratis')->can('Administrador');
    Route::patch('/admin/envios-gratis/{fecha}', [AdminController::class, 'actualizarEnvioGratis']);

    //Horario bot
    Route::get('/configuracion/hora', [HoraController::class, 'index'])->name('configuracion.index')->can('Administrador');
    Route::put('/configuracion/hora', [HoraController::class, 'update'])->name('configuracion.update')->can('Administrador');


    // Rutas CRUD para platillos
    Route::get('/admin/platillos', [AdminController::class, 'vistaPlatillos'])->name('admin.platillos')->can('Administrador');
    Route::post('/admin/platillos/crear', [AdminController::class, 'crearPlatillo'])->name('admin.platillos.crear')->can('Administrador');
    Route::post('/admin/platillos/actualizar', [AdminController::class, 'actualizarPlatillo'])->name('admin.platillos.actualizar')->can('Administrador');
    Route::post('/admin/platillos/eliminar', [AdminController::class, 'eliminarPlatilloCatalogo'])->name('admin.platillos.eliminar')->can('Administrador');
    Route::get('/admin/platillos/todosPlatillos', [AdminController::class, 'obtenerTodosPlatillos'])->name('admin.platillos.obtener')->can('Administrador');
    // Rutas CRUD para usuarios
    Route::get('/admin/users', [AdminController::class, 'vistaUsuarios'])->name('admin.users')->can('Administrador');
    Route::post('/admin/users/create', [AdminController::class, 'UserStore'])->name('admin.users.create')->can('Administrador');
    Route::get('/admin/users/{id}', [AdminController::class, 'showUser'])->name('admin.users.show')->can('Administrador');
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update')->can('Administrador');
    Route::delete('/admin/usuarios/{id}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy')->can('Administrador');
    // Rutas de manejo de pedidos
    Route::get('/admin/pedidos', [AdminController::class, 'pedidosStatusView'])->name('admin.pedidos')->can('Administrador');
    Route::post('pedidos/{id}/actualizar-estado', [AdminController::class, 'actualizarEstado'])->name('admin.pedidos.actualizarEstado')->can('Administrador');

    // Para manejo de pedidos cocina cocina
    Route::get('/cocina/pedidos-Cocina', [AdminController::class, 'pedidosStatusViewCocina'])->name('cocina.pedidosCocina')->can('Cocina');
    Route::post('pedidos/{id}/actualizar-estado-cocina', [AdminController::class, 'actualizarEstadoCocina'])->name('cocina.pedidos.actualizarEstado.cocina')->can('Cocina');


    // Rutas para pedidos a futuro
    Route::get('/admin/pedidos-futuros', [AdminController::class, 'pedidosProgramadosView'])->name('admin.pedidos.futuros')->can('Administrador');
    Route::get('/admin/pedidos/por-fecha', [AdminController::class, 'obtenerPedidosPorFecha'])->name('admin.pedidos.por_fecha')->can('Administrador');
    Route::post('/admin/pedidos/programado', [AdminController::class, 'storePedidoProgramado'])->name('admin.pedidos.programado.store')->can('Administrador');
    Route::get('/admin/pedidos/{pedido}/edit', [AdminController::class, 'editPedidoProgramado'])->name('admin.pedidos.programado.edit')->can('Administrador');
    Route::put('/admin/pedidos/{id}', [AdminController::class, 'updatePedidoProgramado'])->name('admin.pedidos.programado.actualizar')->can('Administrador');

    //Ruta de cancelacion de pedidos
    Route::delete('/admin/pedidos/{id}', [AdminController::class, 'cancelarPedidoProgramado'])->name('admin.borrar.programado')->can('Administrador');
    Route::post('/admin/pedidos/{pedido}/descargar-factura', [AdminController::class, 'descargarFactura'])
        ->name('admin.pedidos.descargarFactura')
        ->can('Administrador');

});

Route::get('/pago/exito', function (Request $request) {
    $reference = $request->query('reference');

    if (!$reference) {
        abort(400, 'Referencia no proporcionada');
    }

    // Buscar el pago en base de datos
    $pago = Pago::where('referencia_transaccion', $reference)->firstOrFail();

    try {
        // Inicializar SDK PlacetoPay
        $placetopay = new PlacetoPay([
            'login' => env('PLACETOPAY_LOGIN'),
            'tranKey' => env('SecretKey'),
            'baseUrl' => env('PLACETOPAY_BASE_URL'),
        ]);

        // Usar el request_id asociado al pago para validar con PlacetoPay
        $response = $placetopay->query($pago->request_id);

        if (!$response->isSuccessful()) {
            Log::warning("Error verificando transacción {$pago->request_id}: {$response->status()->message()}");
            return view('pago.error', ['mensaje' => 'No se pudo validar el estado de su pago']);
        }

        // Obtener estado real de PlacetoPay
        $estadoReal = $response->status()->status();

        // Actualizar estado en DB según lo que dice PlacetoPay
        if ($response->isApproved()) {
            $pago->estado_pago = 'confirmado';
            $payments = $response->toArray()['payment'] ?? [];
            if (!empty($payments) && !empty($payments[0]['internalReference'])) {
                $pago->internal_reference = $payments[0]['internalReference'];
            }

            // ✅ Mover el pedido a "en preparacion"
            $pedido = $pago->pedido;
            if ($pedido && $pedido->estado !== 'en preparacion') {
                $pedido->estado = 'en preparacion';
                $pedido->save();
            }

            $pago->save();

            if (!$pago->notificado) {
                $cliente = $pago->pedido ? $pago->pedido->cliente : null;
                $telefono = $cliente ? $cliente->telefono : null;
                $nombre = $cliente ? $cliente->nombre : 'Usuario';

                if ($telefono) {
                    $mensaje = "Hola {$nombre}, hemos recibido la actualización de tu pago con referencia {$pago->referencia_transaccion}. Estado: {$pago->estado_pago}.";

                    $payload = [
                        'numero' => $telefono,
                        'mensaje' => $mensaje,
                    ];

                    $botResponse = Http::post("http://xn--lacampaafoodservice-13b.com:3008/v1/send-message", $payload);

                    if ($botResponse->successful()) {
                        $pago->notificado = true;
                        $pago->save();
                    } else {
                        Log::warning("Error enviando notificación de pago {$pago->id}: " . $botResponse->body());
                    }
                } else {
                    Log::warning("El pago {$pago->id} no tiene teléfono asociado para notificación.");
                }
            }

        } elseif ($estadoReal === 'FAILED' || $response->isRejected()) {
            $pago->estado_pago = 'fallido';
            $pago->save();

            if (!$pago->notificado) {
                $cliente = $pago->pedido ? $pago->pedido->cliente : null;
                $telefono = $cliente ? $cliente->telefono : null;
                $nombre = $cliente ? $cliente->nombre : 'Usuario';

                if ($telefono) {
                    $mensaje = "Hola {$nombre}, hemos recibido la actualización de tu pago con referencia {$pago->referencia_transaccion}. Estado: {$pago->estado_pago}.";

                    $payload = [
                        'numero' => $telefono,
                        'mensaje' => $mensaje,
                    ];

                    $botResponse = Http::post("http://xn--lacampaafoodservice-13b.com:3008/v1/send-message", $payload);

                    if ($botResponse->successful()) {
                        $pago->notificado = true;
                        $pago->save();
                    } else {
                        Log::warning("Error enviando notificación de pago {$pago->id}: " . $botResponse->body());
                    }
                } else {
                    Log::warning("El pago {$pago->id} no tiene teléfono asociado para notificación.");
                }
            }


        } else {
            $pago->estado_pago = 'pendiente';
            $pago->save();
        }



        // Renderizar la vista con el estado actualizado
        return view('pago.exito', compact('pago'));
    } catch (\Exception $e) {
        Log::error("Error en return URL de PlacetoPay: " . $e->getMessage());
        abort(400, 'Datos inválidos en la respuesta de PlacetoPay');
    }
})->name('pago.exito');

Route::get('/pago/cancelado', function () {
    return view('pago.cancelado');
})->name('pago.cancelado');

// Rutas para entregas
Route::post('/update-delivery-status', [VroomController::class, 'updateDeliveryStatus'])->name('delivery.update');
Route::post('/mark-delivery-completed', [VroomController::class, 'markDeliveryCompleted'])->name('delivery.completed');
Route::post('/mark-delivery-returned', [VroomController::class, 'markDeliveryReturned'])->name('delivery.returned');
Route::get('/delivery-status/{driverId?}', [VroomController::class, 'getDeliveryStatus'])->name('delivery.status');
Route::get('/delivery-history/{deliveryId}', [VroomController::class, 'getDeliveryHistory'])->name('delivery.history');
Route::post('/reset-delivery-status', [VroomController::class, 'resetDeliveryStatus'])->name('delivery.reset');

Route::middleware(['auth'])->group(function () {

    // === RUTAS EXISTENTES DEL SISTEMA DE JORNADAS ===

    // Rutas principales de jornadas (que ya tienes)
    Route::post('/admin/jornadas/create', [VroomController::class, 'createNewShift'])
        ->name('admin.jornadas.create')
        ->can('Administrador');

    Route::post('/admin/jornadas/assign', [VroomController::class, 'assignShiftToDriver'])
        ->name('admin.jornadas.assign')
        ->can('Administrador');

    Route::get('/admin/jornadas/status', [VroomController::class, 'getShiftStatus'])
        ->name('admin.jornadas.status')
        ->can('Administrador');

    // === RUTAS FALTANTES QUE CAUSAN EL ERROR 403 ===

    // Ruta para obtener pedidos disponibles
    Route::get('/admin/pedidos/disponibles', [VroomController::class, 'getAvailableOrders'])
        ->name('admin.pedidos.disponibles')
        ->can('Administrador');

    // Ruta para obtener repartidores disponibles  
    Route::get('/admin/repartidores/disponibles', [VroomController::class, 'getAvailableDriversAPI'])
        ->name('admin.repartidores.disponibles')
        ->can('Administrador');

    // === RUTAS ADICIONALES PARA EL SISTEMA COMPLETO ===

    // Motoristas - jornada actual
    Route::get('/motorista/jornadas/current', [VroomController::class, 'getCurrentShift'])
        ->name('motorista.jornadas.current')
        ->can('Motorista');

    Route::post('/motorista/jornadas/start', [VroomController::class, 'startShift'])
        ->name('motorista.jornadas.start')
        ->can('Motorista');

    Route::post('/motorista/jornadas/complete', [VroomController::class, 'completeShiftAndAssignNext'])
        ->name('motorista.jornadas.complete')
        ->can('Motorista');

    // Auto-asignación de jornadas
    Route::post('/sistema/auto-assign-next', [VroomController::class, 'autoAssignNextShifts'])
        ->name('sistema.auto_assign')
        ->can('Administrador');

    // Dashboard de métricas
    Route::get('/api/dashboard/metrics', [VroomController::class, 'getDashboardMetrics'])
        ->name('api.dashboard.metrics')
        ->can('Administrador');

    // Tracking de ubicación
    Route::post('/api/driver/update-location', [VroomController::class, 'updateDriverLocation'])
        ->name('api.driver.update_location')
        ->can('Motorista');

    // === RUTA PARA LA VISTA DE GESTIÓN DE JORNADAS ===

    Route::get('/admin/jornadas', [VroomController::class, 'shiftDashboard'])
        ->name('admin.jornadas')
        ->can('Administrador');

    Route::get('/admin/debug-autoassign', [VroomController::class, 'debugAutoAssign'])
        ->name('admin.debug_autoassign')
        ->can('Administrador');

    Route::get('/admin/pedidos-del-dia', [VroomController::class, 'getPedidosDelDia'])
        ->name('admin.pedidos_del_dia')
        ->can('Administrador');

    Route::post('/admin/drivers/toggle-availability', [VroomController::class, 'toggleDriverAvailability'])
        ->name('admin.drivers.toggle_availability')
        ->can('Administrador');

    Route::get('/admin/drivers/with-availability', [VroomController::class, 'getDriversWithAvailability'])
        ->name('admin.drivers.with_availability')
        ->can('Administrador');

    // Ruta para verificar nueva jornada
    Route::get('/motorista/check-new-shift', [VroomController::class, 'checkNewShift'])
        ->name('motorista.check_new_shift')
        ->can('Motorista');
    Route::get('/admin/jornada/{jornadaId}/pedidos', [VroomController::class, 'getPedidosJornada'])
        ->name('admin.jornada.pedidos')
        ->can('Administrador');
});
Route::post('/admin/distribuir-pedidos-automaticamente', [VroomController::class, 'distribuirPedidosAutomaticamente'])
    ->name('admin.distribuir_automatico')
    ->can('Administrador');

Route::get('/admin/ver-distribucion-actual', [VroomController::class, 'verDistribucionActual'])
    ->name('admin.ver_distribucion')
    ->can('Administrador');

Route::post('/admin/reiniciar-distribucion', [VroomController::class, 'reiniciarDistribucion'])
    ->name('admin.reiniciar_distribucion')
    ->can('Administrador');
require __DIR__ . '/auth.php';