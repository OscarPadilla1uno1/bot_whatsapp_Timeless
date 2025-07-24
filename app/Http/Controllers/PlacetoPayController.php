<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dnetix\Redirection\PlacetoPay;
use Illuminate\Support\Facades\Log;
use Exception;

class PlacetoPayController extends Controller
{
    public function createSession(Request $request)
    {
        // Validar los datos recibidos del JSON
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
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
        
        $reference = 'TEST_' . time();
        
        // Datos de la sesión de pago usando los datos del JSON
        $requestData = [
            "buyer" => [
                "name" => $validatedData['name'],
                "email" => $validatedData['email'],
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
            'expiration' => date('c', strtotime('+2 days')),
            'returnUrl' => $validatedData['returnUrl'] ?? 'http://example.com/response?reference=' . $reference,
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
}