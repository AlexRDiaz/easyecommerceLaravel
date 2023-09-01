<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrdenesRetiro;
use App\Models\PedidosShopify;
use App\Models\Vendedore;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class VendedoreAPIController extends Controller
{
    public function index()
    {
        $pedidos = Vendedore::all();
        return response()->json($pedidos);
    }

    public function show($id)
    {
        $pedido = Vendedore::findOrFail($id);

        return response()->json($pedido);
    }

 
    public function mybalanceVF() {
        $values = [];
    
        // Obtén todos los vendedores
        $searchGeneralSellers = Vendedore::take(20)->get();
    
        foreach ($searchGeneralSellers as $seller) {
            $sumaEntregados = PedidosShopify::where('id_comercial', $seller->id)
                ->where('status', 'ENTREGADO')
                ->sum('precio_total');
    
            $sumaCostoInicial = floatval($seller->costo_envio);
    
            $sumaCosto = DB::table('pedidos_shopifies')
                ->where('id_comercial', $seller->id)
                ->whereIn('status', ['ENTREGADO', 'NO ENTREGADO'])
                ->count() * $sumaCostoInicial;
    
            $sumaDevolucionInicial = floatval($seller->costo_devolucion);

            $pedidos = DB::table('pedidos_shopifies')
                ->where('id_comercial', $seller->id)
                ->where(function ($query) {
                    $query->where('estado_devolucion', 'ENTREGADO EN OFICINA')
                        ->orWhere('estado_devolucion', 'DEVOLUCION EN RUTA')
                        ->orWhere('estado_devolucion', 'EN BODEGA');
                })
                ->where('status', 'NOVEDAD')->get();

             $sumaDevolucion =$pedidos->count() * $sumaDevolucionInicial;
                 
 
    

             $sumaRetiros= OrdenesRetiro::with('users_permissions_user')
             ->whereHas('users_permissions_user', function ($query) use ($seller) {
                 $query->where('user_id', $seller->id);
             }) 
             ->where('estado', 'REALIZADO')
             ->sum('monto');
            $values[] = [
                'seller' => $seller,
                'sumaEntregados' => $sumaEntregados,
                'sumaCosto' => $sumaCosto,
                'sumaDevolucion' => $sumaDevolucion,

             //  "tedt"=> $searchWithDrawal

               'sumaRetiros' => $sumaRetiros,
            ];
        }
    
        return [
            'code' => 200,
            'value' => $values
        ];
    }
    
}