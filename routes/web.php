<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CheckPermission;
use App\Http\Controllers\HoraController;
use App\Http\Controllers\EntregaController;
use App\Models\Pago;
use Dnetix\Redirection\PlacetoPay;
use App\Models\Pedido;
use Illuminate\Http\Request;
use App\Models\PagoConsolidado;


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


    Route::post('/admin/drivers/toggle-availability', [AdminController::class, 'toggleDriverAvailability']);
    Route::get('/admin/drivers/with-availability', [AdminController::class, 'getDriversWithAvailability']);

    // routes/api.php o routes/web.php
    Route::post('/api/entregas/actualizar-estado', [EntregaController::class, 'actualizarEstado']);
    // O la ruta alternativa
    Route::post('/update-delivery-status', [EntregaController::class, 'updateDeliveryStatus'])->name('delivery.update');
});

Route::get('/pago/exito', function (Request $request) {
    $reference = $request->query('reference');
    Log::info("Iniciando retorno de pago para referencia: {$reference}");

    if (!$reference) {
        abort(400, 'Referencia no proporcionada');
    }

    // Buscar el pago en base de datos
    $pago = Pago::where('referencia_transaccion', $reference)->first();
    Log::info("Pago encontrado en base de datos: {$pago}");

    $pagoConsolidado = null;
    $esConsolidado = false;

    if (!$pago) {
        $pagoConsolidado = PagoConsolidado::where('referencia_transaccion', $reference)->first();
        Log::info("Buscando pago consolidado para referencia: {$reference}");
        if ($pagoConsolidado) {
            $esConsolidado = true;
            Log::info("Pago consolidado encontrado en base de datos: ID {$pagoConsolidado->id}");
        } else {
            abort(404, 'Pago no encontrado.');
        }
    }

    try {
        // Inicializar SDK PlacetoPay
        $placetopay = new PlacetoPay([
            'login' => env('PLACETOPAY_LOGIN'),
            'tranKey' => env('SecretKey'),
            'baseUrl' => env('PLACETOPAY_BASE_URL'),
        ]);
        Log::info("SDK PlacetoPay inicializado.");

        // Usar el request_id asociado al pago para validar con PlacetoPay
        $requestId = $esConsolidado ? $pagoConsolidado->request_id : $pago->request_id;
        $response = $placetopay->query($requestId);
        Log::info("Respuesta de PlacetoPay para {$requestId}: " . $response->status()->message());

        if (!$response->isSuccessful()) {
            Log::warning("Error verificando transacción {$requestId}: {$response->status()->message()}");
            abort('404', 'No se pudo validar el estado de su pago');
        }
        Log::info("Respuesta exitosa de PlacetoPay para {$requestId}.");

        // Obtener estado real de PlacetoPay
        $estadoReal = $response->status()->status();

        if ($esConsolidado && $pagoConsolidado) {
            if ($response->isApproved()) {
                $pagoConsolidado->update([
                    'estado_pago' => 'confirmado',
                    'fecha_pago' => now(),
                ]);

                $pagoConsolidado->load(['pedidos.pago']);

                DB::transaction(function () use ($pagoConsolidado) {
                    foreach ($pagoConsolidado->pedidos as $pedido) {
                        $pedido->update(['estado' => 'en preparación']);
                        $pagoConsolidado->pedidos()->updateExistingPivot($pedido->id, ['pagado' => true]);
                        if ($pedido->pago) {
                            $pedido->pago->update([
                                'estado_pago' => 'confirmado',
                                'fecha_pago' => now(),
                                'observaciones' => 'Pago consolidado confirmado (retorno)',
                            ]);
                        }
                    }
                });

                if (!$pagoConsolidado->notificado) {
                    $cliente = $pagoConsolidado->cliente;
                    $telefono = $cliente->telefono ?? null;
                    $nombre = $cliente->nombre ?? 'Cliente';

                    $mensaje = "✅ Hola {$nombre}, tu pago consolidado por L. {$pagoConsolidado->monto_total} ha sido confirmado. Tus pedidos pasarán a preparación.";

                    try {
                        $botResponse = Http::post("http://xn--lacampaafoodservice-13b.com:3008/v1/send-message", [
                            'numero' => $telefono,
                            'mensaje' => $mensaje,
                        ]);

                        if ($botResponse->successful()) {
                            $pagoConsolidado->marcarNotificado();
                            Log::info("Notificación enviada al cliente {$nombre} ({$telefono})");
                        } else {
                            Log::warning("Error notificando al bot consolidado: " . $botResponse->body());
                        }
                    } catch (\Throwable $ex) {
                        Log::error("Error notificando bot consolidado: " . $ex->getMessage());
                    }
                }

                return view('pago.exito', [
                    'pago' => $pagoConsolidado,
                    'tipo' => 'consolidado',
                ]);

            } elseif ($response->isRejected() || $estadoReal === 'FAILED') {
                $pagoConsolidado->update(['estado_pago' => 'fallido']);

                if (!$pagoConsolidado->notificado) {
                        $cliente = $pagoConsolidado->cliente;
                        $telefono = $cliente->telefono ?? null;
                        $nombre = $cliente->nombre ?? 'Cliente';

                        $mensaje = "⚠️ Hola {$nombre}, tu pago consolidado por L. {$pagoConsolidado->monto_total} fue rechazado. Intenta nuevamente.";

                        try {
                            $botRespuesta = Http::post("http://xn--lacampaafoodservice-13b.com:3008/v1/send-message", [
                                'numero' => $telefono,
                                'mensaje' => $mensaje,
                            ]);

                            if ($botRespuesta->successful()) {
                                $pagoConsolidado->marcarNotificado();
                                Log::info("Notificación de pago fallido enviada al cliente {$nombre} ({$telefono})");
                            } else {
                                Log::warning("Error notificando al bot consolidado: " . $botRespuesta->body());
                            }
                        } catch (\Throwable $ex) {
                            Log::error("Error notificando bot consolidado: " . $ex->getMessage());
                        }
                    }

                return view('pago.cancelado', ['mensaje' => 'El pago consolidado fue rechazado.']);
            } else {
                return view('pago.exito', [
                    'pago' => $pagoConsolidado,
                    'tipo' => 'consolidado',
                    'mensaje' => 'Tu pago está en estado pendiente de confirmación.',
                ]);
            }
        }

        // Actualizar estado en DB según lo que dice PlacetoPay
        if ($response->isApproved()) {
            $pago->estado_pago = 'confirmado';
            $payments = $response->toArray()['payment'] ?? [];
            if (!empty($payments) && !empty($payments[0]['internalReference'])) {
                $pago->internal_reference = $payments[0]['internalReference'];
            }

            // ✅ Mover el pedido a "en preparación"
            $pedido = $pago->pedido;
            if ($pedido && $pedido->estado !== 'en preparación') {
                $pedido->estado = 'en preparación';
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

                $pedido = $pago->pedido;
                if ($pedido) {
                    $adminController = new AdminController();
                    $result = $adminController->cancelarPedidoBot($pedido->id);
                    Log::info("Resultado cancelación automática: " . $result->getContent());
                }

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

Route::get('/admin/pedidos/programados/tarjeta', [AdminController::class, 'listarPedidosTarjeta'])
    ->name('admin.pedidos.programados.tarjeta')
    ->can('Administrador');

Route::post('/admin/pagos/consolidado/create', [AdminController::class, 'crearPagoConsolidado'])
    ->name('admin.pagos.consolidado.create');
//->can('Administrador');

Route::get('/admin/clientes/listar', [AdminController::class, 'listarClientes'])
    ->name('admin.clientes.listar')
    ->can('Administrador');

Route::get('/admin/clientes/{id}/pagos-consolidados', [AdminController::class, 'listarPagosConsolidadosCliente'])
    ->name('admin.cliente.pagos.consolidados')
    ->can('Administrador');

use App\Http\Controllers\ExportController;

Route::prefix('export')->group(function () {
    Route::get('/clientes', [ExportController::class, 'exportClientes'])->name('export.clientes')->can('Administrador');
    Route::get('/pedidos', [ExportController::class, 'exportPedidos'])->name('export.pedidos')->can('Administrador');
    Route::get('/pagos', [ExportController::class, 'exportPagos'])->name('export.pagos')->can('Administrador');
    Route::get('/pagos-consolidados', [ExportController::class, 'exportPagosConsolidados'])->name('export.pagos.consolidados')->can('Administrador');
    Route::get('/platillos', [ExportController::class, 'exportPlatillos'])->name('export.platillos')->can('Administrador');
    Route::get('/todo', [ExportController::class, 'exportTodo'])->name('export.todo')->can('Administrador');
    Route::get('/export/pago-consolidado-pedidos', [ExportController::class, 'exportPagoConsolidadoPedidos'])
    ->name('export.pago.consolidado.pedidos')->can('Administrador');

});



Route::get('/bot/qr', function () {
    $qrPath = '/var/www/base-js-wppconnect-mysqlCHATBOTV2/bot.qr.png';

    if (file_exists($qrPath)) {
        return response()->file($qrPath, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    // Si no existe el QR, devolver una imagen placeholder o error
    abort(404, 'QR no disponible');
})->name('bot.qr');
require __DIR__ . '/auth.php';