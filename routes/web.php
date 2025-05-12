<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckPermission;

Route::get('/routes', [VroomController::class, 'index'])->name('routes');

//Rutas publicas
Route::get('/admin/menu/MenuHoy', [AdminController::class, 'obtenerMenuHoy'])->name('admin.menu.hoy');



Route::get('/', function () {
    return view('welcome');
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
    // Rutas para pedidos a futuro
    Route::get('/admin/pedidos-futuros', [AdminController::class, 'pedidosProgramadosView'])->name('admin.pedidos.futuros')->can('Administrador');
    Route::get('/admin/pedidos/por-fecha', [AdminController::class, 'obtenerPedidosPorFecha'])->name('admin.pedidos.por_fecha')->can('Administrador');
    Route::post('/admin/pedidos/programado', [AdminController::class, 'storePedidoProgramado'])->name('admin.pedidos.programado.store')->can('Administrador');
    Route::get('/admin/pedidos/{pedido}/edit', [AdminController::class, 'editPedidoProgramado'])->name('admin.pedidos.programado.edit')->can('Administrador');
    Route::put('/admin/pedidos/{id}', [AdminController::class, 'updatePedidoProgramado'])->can('Administrador');

    //Ruta de cancelacion de pedidos
    Route::delete('/admin/pedidos/{id}', [AdminController::class, 'cancelarPedidoProgramado'])->can('Administrador');

});


require __DIR__ . '/auth.php';
