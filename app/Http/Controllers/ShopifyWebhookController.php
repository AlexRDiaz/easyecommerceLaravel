<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function handleCustomerDataRequest(Request $request)
    {
        // Verifica si el webhook es válido (puedes implementar lógica adicional para validar el webhook)
        if ($request->header('X-Shopify-Topic') === 'customers/data_request') {
            // Obtiene la carga útil del webhook
            $webhookPayload = $request->all();

            // Aquí puedes manejar la solicitud de datos del cliente
            // Por ejemplo, puedes procesar la solicitud, obtener los datos del cliente y responder al webhook

            // Responde al webhook con un código de estado 200 para confirmar la recepción
            return response()->json(['message' => 'Webhook recibido'], 200);
        }

        // Si el webhook no es el esperado, responde con un código de estado 404 (no encontrado)
        return response()->json(['error' => 'Webhook no válido'], 404);
    }

    public function handleCustomerRedact(Request $request)
    {
        // Verifica si el webhook es válido (puedes implementar lógica adicional para validar el webhook)
        if ($request->header('X-Shopify-Topic') === 'customers/redact') {
            // Obtiene la carga útil del webhook
            $webhookPayload = $request->all();

            // Aquí puedes manejar la solicitud de eliminación de datos del cliente
            // Por ejemplo, eliminar los datos personales del cliente de tu sistema

            // Responde al webhook con un código de estado 200 para confirmar la recepción
            return response()->json(['message' => 'Webhook recibido'], 200);
        }

        // Si el webhook no es el esperado, responde con un código de estado 404 (no encontrado)
        return response()->json(['error' => 'Webhook no válido'], 404);
    }
    function verifyWebhook($data, $hmacHeader)
{
    $calculatedHmac = base64_encode(hash_hmac('sha256', $data, "5dd4d59dc579a8bc4972383c42be5b7b", true));
    return hash_equals($calculatedHmac, $hmacHeader);
}
    public function handleShopRedact(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $data = file_get_contents('php://input');

        $verified = $this->verifyWebhook($data, $hmacHeader);

        if ($verified) {
            // El webhook es auténtico, procesar el payload
            // ...
            return response()->json(['message' => 'Webhook verificado'], 200);
        } else {
            // El webhook no es auténtico, responder con un código de estado 401
            return response()->json(['error' => 'No autorizado'], 401);
        }
    }

    public function handleShopRedact2(Request $request)
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $data = file_get_contents('php://input');

        $verified = $this->verifyWebhook($data, $hmacHeader);

        if ($verified) {
            // El webhook es auténtico, procesar el payload
            // ...
            return response()->json(['message' => 'Webhook verificado'], 200);
        } else {
            // El webhook no es auténtico, responder con un código de estado 401
            return response()->json(['error' => 'No autorizado'], 401);
        }
    }
}
