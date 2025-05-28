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

// En routes/api.php

Route::post('/vehicle/distance', [VroomController::class, 'calculateDistanceFromVehicle'])->name('vehicle.distance');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/factura/{id}', [AdminController::class, 'obtenerFacturaPDF']);

Route::post('/bot-pedido', [AdminController::class, 'storePedido']);
Route::post('/procesar-comprobante', [AdminController::class, 'procesarComprobante']);
Route::get('/test', function () {
    return response()->json(['message' => 'La API funciona!']);
});

Route::post('/seguir', [VroomController::class, 'seguirRuta']);

