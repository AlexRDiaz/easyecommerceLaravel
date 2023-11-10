<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\CreateProviderAPIRequest;
use App\Http\Requests\API\UpdateProviderAPIRequest;
use App\Models\Provider;
use App\Models\UpUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ProviderAPIController
 */
class ProviderAPIController extends Controller
{
   
    public function show($id)
    {
        $pedido = Provider::findOrFail($id);

        return response()->json($pedido);
    }

    public function getProviders(){
        $providers = Provider::with('user')->get();
        return response()->json(['providers' => $providers]);
    }
}
