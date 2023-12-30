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
    public function handleShopRedact(Request $request)
    {
        // Verifica si el webhook es válido (puedes implementar lógica adicional para validar el webhook)
        $shopifySecret = '5dd4d59dc579a8bc4972383c42be5b7b'; // Tu secreto de aplicación Shopify

        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $data = $request->getContent();

        // Calcula el HMAC usando tu secreto
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $shopifySecret, true));

        // Compara el HMAC proporcionado con el calculado
        if (hash_equals($hmacHeader, $calculatedHmac)) {
            // La firma es válida, el webhook es auténtico
            // ... tu lógica de manejo del webhook ...
            return response()->json(['message' => 'Webhook recibido'], 200);
        } else {
            // La firma no coincide, el webhook podría no ser auténtico
            return response()->json(['error' => 'No autorizado'], 401);
        }
    }

}
