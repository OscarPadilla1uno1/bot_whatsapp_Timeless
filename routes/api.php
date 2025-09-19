<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VroomController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\PlacetoPayController;
use App\Http\Controllers\HoraController;

// API para obtener la configuración
Route::get('/bot/configuracion', [HoraController::class, 'getConfiguracion'])
    ->name('bot.configuracion');

Route::get('/bot/activo', [HoraController::class, 'verificarActivo'])
    ->name('bot.activo');
/*
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

// API Routes adicionales (si usas api.php)

// En routes/api.php

Route::post('/vehicle/distance', [VroomController::class, 'calculateDistanceFromVehicle'])->name('vehicle.distance');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/cotizar', [AdminController::class, 'cotizarPedido']);

Route::get('/factura/{id}', [AdminController::class, 'obtenerFacturaPDF']);

Route::post('/bot-pedido', [AdminController::class, 'storePedido']);
Route::post('/procesar-comprobante', [AdminController::class, 'procesarComprobante']);
Route::get('/test', function () {
    return response()->json(['message' => 'La API funciona!']);
});

Route::get('/envio-gratis', [AdminController::class, 'verificar']);

Route::get('/verificar-cliente', [AdminController::class, 'verificarNumero']);


Route::post('/placetopay/session', [PlacetoPayController::class, 'createSession']);

Route::post('/pedido/{id}/preparar', [AdminController::class, 'prepararPedido']);
Route::post('/pedido/{id}/cancelar', [AdminController::class, 'cancelarPedidoBot']);
Route::get('/placetopay/status/{requestId}', [PlacetoPayController::class, 'checkPaymentStatus']);


Route::post('/placetopay/webhook/{token}', [PlacetoPayController::class, 'handleWebhook']);
Route::put('/pagos/actualizar/{pedidoId}', [AdminController::class, 'actualizarDatosPago']);
Route::post('/verificar-pago', [AdminController::class, 'verificarPagoPendiente']);
Route::get('/fecha-hoy', [AdminController::class, 'todayDate']);
Route::post('/pagos/{requestId}/reverse', [PlacetoPayController::class, 'reversePayment']);