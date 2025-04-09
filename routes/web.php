<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckPermission;

Route::get('/routes', [VroomController::class, 'index'])->name('routes');
Route::post('/seguir-ruta', [VroomController::class, 'seguirRuta'])->name('seguirRuta');


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
    Route::get('/admin/menu-diario', [AdminController::class, 'paginaMenuDelDia'])->name('admin.menu')->can('admin');
    Route::post('/menu/agregar', [AdminController::class, 'agregarPlatillo'])->name('admin.menu.agregar')->can('admin');
    Route::get('/admin/menu/fecha', [AdminController::class, 'obtenerMenuPorFecha'])->name('admin.menu.fecha')->can('admin');
    Route::post('/admin/menu/eliminar', [AdminController::class, 'eliminarPlatillo'])->name('admin.menu.eliminar')->can('admin');
    Route::post('/admin/menu/actualizar-cantidad', [AdminController::class, 'actualizarCantidad'])->name('admin.menu.actualizarCantidad')->can('admin');
    Route::get('/admin/menu/MenuHoy', [AdminController::class, 'obtenerMenuHoy'])->name('admin.menu.hoy')->can('admin');
    // Rutas CRUD para platillos
    Route::get('/admin/platillos', [AdminController::class, 'vistaPlatillos'])->name('admin.platillos')->can('admin');
    Route::post('/admin/platillos/crear', [AdminController::class, 'crearPlatillo'])->name('admin.platillos.crear')->can('admin');
    Route::post('/admin/platillos/actualizar', [AdminController::class, 'actualizarPlatillo'])->name('admin.platillos.actualizar')->can('admin');
    Route::post('/admin/platillos/eliminar', [AdminController::class, 'eliminarPlatilloCatalogo'])->name('admin.platillos.eliminar')->can('admin');
    Route::get('/admin/platillos/todosPlatillos', [AdminController::class,'obtenerTodosPlatillos'])->name('admin.platillos.obtener')->can('admin');


});


require __DIR__ . '/auth.php';
