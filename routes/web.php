<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckPermission;

//Rutas publicas

// En web.php - SOLO PARA DEBUG
Route::get('/debug-assignments', [VroomController::class, 'debugAssignments']);
// En web.php - TEMPORAL para debug
// En web.php - Reemplaza la ruta anterior
// En web.php - CORREGIDO
use Illuminate\Http\Request;  // <- Agregar esta línea al inicio del archivo

Route::post('/test-complete', function(Request $request) {
    try {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint funciona',
            'data' => $request->all(),  // <- Usar $request en lugar de Request
            'user' => Auth::user() ? Auth::user()->name : 'No autenticado',
            'session_id' => session()->getId()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

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
    Route::get('/routes', [VroomController::class, 'index'])->name('routes');
    Route::get('/cocina/dashboard', [DashboardController::class, 'cocina'])->name('cocina.dashboard');
    Route::post('/routes/seguir', [VroomController::class, 'seguirRuta'])->name('routes.seguir');
    Route::post('/routes/recalculate', [VroomController::class, 'recalculateRoutes'])->name('routes.recalculate')->can('Administrador');
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
    // Rutas CRUD para platillos
    Route::get('/admin/platillos', [AdminController::class, 'vistaPlatillos'])->name('admin.platillos')->can('Administrador');
    Route::post('/admin/platillos/crear', [AdminController::class, 'crearPlatillo'])->name('admin.platillos.crear')->can('Administrador');
    Route::post('/admin/platillos/actualizar', [AdminController::class, 'actualizarPlatillo'])->name('admin.platillos.actualizar')->can('Administrador');
    Route::post('/admin/platillos/eliminar', [AdminController::class, 'eliminarPlatilloCatalogo'])->name('admin.platillos.eliminar')->can('Administrador');
    Route::get('/admin/platillos/todosPlatillos', [AdminController::class,'obtenerTodosPlatillos'])->name('admin.platillos.obtener')->can('Administrador');
    // Rutas CRUD para usuarios
    Route::get('/admin/users', [AdminController::class,'vistaUsuarios'])->name('admin.users')->can('Administrador');
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
    Route::put('/admin/pedidos/{id}', [AdminController::class, 'updatePedidoProgramado'])->can('Administrador');

    //Ruta de cancelacion de pedidos
    Route::delete('/admin/pedidos/{id}', [AdminController::class, 'cancelarPedidoProgramado'])->can('Administrador');
    Route::post('/admin/pedidos/{pedido}/descargar-factura', [AdminController::class, 'descargarFactura'])
    ->name('admin.pedidos.descargarFactura')
    ->can('Administrador');


});

// Rutas existentes (mantener)
Route::get('/vehicle', [VroomController::class, 'index'])->name('vehicle.show');
Route::post('/vehicle/recalculate', [VroomController::class, 'recalculateRoutes'])->name('vehicle.recalculate');
Route::post('/vehicle/calculate-distance', [VroomController::class, 'calculateDistanceFromVehicle'])->name('vehicle.calculate-distance');

// RUTAS CORREGIDAS para el sistema de asignaciones específicas
Route::group(['prefix' => 'vehicle', 'middleware' => ['auth']], function () {
    
    // Seguir ruta (navegación en tiempo real) - CORREGIDA
    Route::post('/seguir-ruta', [VroomController::class, 'seguirRuta'])->name('vehicle.seguir-ruta');
    
    // Capturar ubicaciones GPS de vehículos (solo admin)
    Route::post('/capture-locations', [VroomController::class, 'captureVehicleLocations'])
         ->name('vehicle.capture-locations')
         ->middleware('permission:Administrador');
    
    // Marcar trabajo como completado
    Route::post('/complete-job', [VroomController::class, 'markJobCompleted'])
         ->name('vehicle.complete-job');
    
    // Obtener estado de asignaciones de un vehículo
    Route::get('/assignment-status', [VroomController::class, 'getVehicleAssignmentStatus'])
         ->name('vehicle.assignment-status');
    
    // Obtener trabajos asignados a un vehículo específico
    Route::get('/assigned-jobs/{vehicleId?}', [VroomController::class, 'getAssignedJobs'])
         ->name('vehicle.assigned-jobs');
    
    // Cambiar estado de un trabajo (admin)
    Route::patch('/job-status', [VroomController::class, 'updateJobStatus'])
         ->name('vehicle.update-job-status')
         ->middleware('permission:Administrador');
    
    // Reasignar trabajo a otro vehículo (admin)
    Route::post('/reassign-job', [VroomController::class, 'reassignJob'])
         ->name('vehicle.reassign-job')
         ->middleware('permission:Administrador');

    // Dashboard de administrador
    Route::get('/admin-dashboard', [VroomController::class, 'getAdminDashboard'])
         ->name('vehicle.admin-dashboard')
         ->middleware('permission:Administrador');

    // Limpiar asignaciones completadas (admin)
    Route::delete('/cleanup-assignments', [VroomController::class, 'cleanupCompletedAssignments'])
         ->name('vehicle.cleanup-assignments')
         ->middleware('permission:Administrador');
});

// API Routes adicionales (si usas api.php)
Route::group(['prefix' => 'api/vehicle', 'middleware' => ['auth:sanctum']], function () {
    
    // Versión API de las rutas principales
    Route::post('/follow-route', [VroomController::class, 'seguirRuta']);
    Route::post('/complete-job', [VroomController::class, 'markJobCompleted']);
    Route::get('/assignment-status', [VroomController::class, 'getVehicleAssignmentStatus']);
    Route::post('/capture-locations', [VroomController::class, 'captureVehicleLocations']);
    Route::get('/assigned-jobs/{vehicleId?}', [VroomController::class, 'getAssignedJobs']);
    Route::patch('/job-status', [VroomController::class, 'updateJobStatus']);
    Route::get('/admin-dashboard', [VroomController::class, 'getAdminDashboard']);
});
Route::group(['prefix' => 'api/vehicle', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/follow-route', [VroomController::class, 'seguirRuta']);
    Route::post('/complete-job', [VroomController::class, 'markJobCompleted']);
    Route::get('/assignment-status', [VroomController::class, 'getVehicleAssignmentStatus']);
    Route::post('/capture-locations', [VroomController::class, 'captureVehicleLocations']);
});

require __DIR__ . '/auth.php';
