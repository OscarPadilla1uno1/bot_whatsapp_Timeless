<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\Admin\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rutas existentes (mantener)
Route::get('/vehicle', [VroomController::class, 'index'])->name('vehicle.show');
Route::post('/vehicle/seguir-ruta', [VroomController::class, 'seguirRuta'])->name('vehicle.seguir-ruta');
Route::post('/vehicle/recalculate', [VroomController::class, 'recalculateRoutes'])->name('vehicle.recalculate');
Route::post('/vehicle/calculate-distance', [VroomController::class, 'calculateDistanceFromVehicle'])->name('vehicle.calculate-distance');

// NUEVAS RUTAS para el sistema de asignaciones específicas
Route::group(['prefix' => 'vehicle', 'middleware' => ['auth']], function () {
    
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
});

// API Routes adicionales (si usas api.php)
Route::group(['prefix' => 'api/vehicle', 'middleware' => ['auth:sanctum']], function () {
    
    // Versión API de las rutas principales
    Route::post('/follow-route', [VroomController::class, 'seguirRuta']);
    Route::post('/complete-job', [VroomController::class, 'markJobCompleted']);
    Route::get('/assignment-status', [VroomController::class, 'getVehicleAssignmentStatus']);
    Route::post('/capture-locations', [VroomController::class, 'captureVehicleLocations']);
});

// En routes/api.php
Route::post('/vehicle/distance', [VroomController::class, 'calculateDistanceFromVehicle']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/bot-pedido', [AdminController::class, 'storePedido']);

Route::get('/test', function () {
    return response()->json(['message' => 'La API funciona!']);
});

Route::post('/seguir', [VroomController::class, 'seguirRuta']);

