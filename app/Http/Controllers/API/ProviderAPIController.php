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

    public function getProviders($search = null) {
        $providers = Provider::with('user');
    
        if (!empty($search)) {
            $providers->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhereHas('user', function ($query) use ($search) {
                          $query->where('username', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                      });
            });
        }
    
        return response()->json(['providers' => $providers->get()]);
    }
    
    public function index()
    {
        //
        $providers = Provider::with('warehouses')->get();
        return response()->json(['providers' => $providers]);
    }

  
}
