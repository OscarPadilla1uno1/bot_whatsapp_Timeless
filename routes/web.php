<?php


use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckPermission;


Route::get('/vroom', [App\Http\Controllers\VroomController::class, 'index']);




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

Route::middleware('auth')->group(function () {
    Route::get('/admin/menu-diario', [AdminController::class, 'index'])->name('admin.menu')->can('admin');
    Route::post('/menu/agregar', [AdminController::class, 'agregarPlatillo'])->name('admin.menu.agregar')->can('admin');
});


require __DIR__.'/auth.php';
