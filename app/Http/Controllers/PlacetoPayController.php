<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Dnetix\Redirection\PlacetoPay;
use Exception;

class PlacetoPayController extends Controller
{
    public function createSession(Request $request)
    {
        // Validar los datos recibidos del JSON
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|numeric',
            'description' => 'required|string|max:255',
            'total' => 'required|numeric|min:0.01',
            'currency' => 'sometimes|string|in:HNL,USD',
            'returnUrl' => 'sometimes|url'
        ]);

        // Configurar PlacetoPay - La librería maneja la autenticación internamente
        $placetopay = new PlacetoPay([
            'login' => env('PLACETOPAY_LOGIN'),
            'tranKey' => env('SecretKey'),
            'baseUrl' => env('PLACETOPAY_BASE_URL'),
            'timeout' => env('PLACETOPAY_TIMEOUT', 10),
        ]);

        $reference = 'TEST_' . uniqid();

        // Datos de la sesión de pago usando los datos del JSON
        $requestData = [
            "buyer" => [
                "name" => $validatedData['name'],

                "mobile" => $validatedData['mobile']
            ],
            'payment' => [
                'reference' => $reference,
                'description' => $validatedData['description'],
                'amount' => [
                    'currency' => $validatedData['currency'] ?? 'HNL',
                    'total' => $validatedData['total'],
                ],
            ],
            'expiration' => date('c', strtotime('+30 minutes')),
            'returnUrl' => route('pago.exito') . '?reference=' . $reference,
            'cancelUrl' => route('pago.cancelado') . '?reference=' . $reference,
            'ipAddress' => request()->ip(), // Usar la IP real del cliente
            'userAgent' => request()->userAgent(), // Usar el user agent real
        ];

        try {
            $response = $placetopay->request($requestData);

            if ($response->isSuccessful()) {
                // Aquí deberías guardar en tu base de datos:
                // - $response->requestId()
                // - $response->processUrl()
                // - $reference
                // - otros datos necesarios

                return response()->json([
                    'success' => true,
                    'processUrl' => $response->processUrl(),
                    'requestId' => $response->requestId(),
                    'reference' => $reference
                ]);
            } else {
                Log::error('PlacetoPay Error: ' . $response->status()->message());

                return response()->json([
                    'success' => false,
                    'message' => $response->status()->message()
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('PlacetoPay Exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago'
            ], 500);
        }
    }

    public function checkPaymentStatus($requestId)
    {
        $placetopay = new PlacetoPay([
            'login' => env('PLACETOPAY_LOGIN'),
            'tranKey' => env('SecretKey'),
            'baseUrl' => env('PLACETOPAY_BASE_URL'),
            'timeout' => env('PLACETOPAY_TIMEOUT', 10),
        ]);

        try {
            $response = $placetopay->query($requestId);

            if ($response->isSuccessful()) {
                return response()->json([
                    'success' => true,
                    'status' => $response->status()->status(),
                    'isApproved' => $response->status()->isApproved(),
                    'message' => $response->status()->message()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->status()->message()
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el estado del pago'
            ], 500);
        }
    }

    public function reversePayment(Request $request, $requestId)
    {
        try {
            $placetopay = new PlacetoPay([
                'login' => env('PLACETOPAY_LOGIN'),
                'tranKey' => env('SecretKey'),
                'baseUrl' => env('PLACETOPAY_BASE_URL'),
                'timeout' => env('PLACETOPAY_TIMEOUT', 10),
            ]);

            // 1. Consultar el estado del pago
            $response = $placetopay->query((int) $requestId);

            if (!$response->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo consultar el pago: ' . $response->status()->message(),
                ], 400);
            }

            // 2. Extraer el internalReference si existe
            $payments = $response->toArray()['payment'] ?? [];
            if (empty($payments) || empty($payments[0]['internalReference'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay internalReference disponible (pago no aprobado o pendiente).',
                ], 400);
            }

            $internalReference = (string) $payments[0]['internalReference'];

            // 3. Hacer el reverso
            $reverseResponse = $placetopay->reverse($internalReference);

            \Log::info('Reverse Response: ' . json_encode($reverseResponse->toArray()));

            if ($reverseResponse->isSuccessful()) {
                $status = $reverseResponse->status()->status();
                $message = $reverseResponse->status()->message();

                return response()->json([
                    'success' => true,
                    'status' => $status,
                    'message' => $message,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'PlacetoPay rechazó el reembolso: ' . $reverseResponse->status()->message(),
            ], 400);

        } catch (Exception $e) {
            \Log::error('Error en reversePayment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar el reembolso',
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        try {
            $placetopay = new PlacetoPay([
                'login' => env('PLACETOPAY_LOGIN'),
                'tranKey' => env('SecretKey'),
                'baseUrl' => env('PLACETOPAY_BASE_URL'),
            ]);

            $data = $request->all();

            $notification = $placetopay->readNotification($data);

            $requestId = $data['requestId'] ?? null;
            $reference = $data['reference'] ?? null;

            if (!$requestId || !$reference) {
                return response()->json(['error' => 'Datos incompletos'], 400);
            }

            $response2 = $placetopay->query($requestId);

            if (!$response2->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo verificar el estado con PlacetoPay: ' . $response2->status()->message()
                ], 400);
            }

            $estadoWebhook = $notification->status()->status();
            $estadoReal = $response2->status()->status();

            if ($estadoWebhook !== $estadoReal) {
                Log::warning("Estado del webhook ($estadoWebhook) no coincide con el estado real ($estadoReal)");
                return response()->json([
                    'success' => false,
                    'message' => 'Estado de pago no coincide con PlacetoPay'
                ], 400);
            }

            $pago = Pago::where('request_id', $requestId)
                ->where('referencia_transaccion', $reference)
                ->first();

            if (!$pago) {
                return response()->json(['error' => 'Pago no encontrado'], 404);
            }

            $pedido = $pago->pedido;

            if (!$pedido) {
                return response()->json(['error' => 'Pedido no encontrado'], 404);
            }

            $estado = $notification->status()->status();

            if ($notification->isApproved()) {
                $pago->estado_pago = 'confirmado';
                $payments = $response2->toArray()['payment'] ?? [];
                if (!empty($payments) && !empty($payments[0]['internalReference'])) {
                    $pago->internal_reference = $payments[0]['internalReference'];
                }
            } elseif ($notification->isRejected() || $estado === 'FAILED') {
                $pago->estado_pago = 'fallido';
            } else {
                $pago->estado_pago = 'pendiente';
            }

            $pago->save();

            $cliente = $pedido->cliente;

            $telefono = $cliente ? $cliente->telefono : null;
            $nombre = $cliente ? $cliente->nombre : 'Usuario';

            $payload = [
                'requestId' => $pago->request_id,
                'reference' => $pago->referencia_transaccion,
                'number' => $telefono,
                'status' => $pago->estado_pago === 'confirmado' ? 'approved' : 'rejected',
                'name' => $nombre,
                'pedido_id' => $pedido->id,
            ];

            $botResponse = Http::post(env('BUILDERBOT_WEBHOOK_URL', 'http://localhost:3008/v1/process-payment'), $payload);

            if (!$botResponse->successful()) {
                Log::warning("Error notificando al bot: " . $botResponse->body());
            }

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::error('Error en webhook PlacetoPay: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook error'], 500);
        }
    }
}