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

    public function getVendedores()
    {
        $vendedores = Vendedore::whereNotNull('id_master')
            ->where('id_master', '<>', '') // Agrega esta condición para excluir registros con id_master en blanco
            ->select(DB::raw('CONCAT(nombre_comercial, "-", id_master) as id_nombre'))
            ->distinct()
            ->get()
            ->pluck('id_nombre');
    
        return response()->json(['vendedores' => $vendedores]);
    }
    

    public function mybalanceVF() {
        $values = [];
    
        // Obtén todos los vendedores
        $searchGeneralSellers = Vendedore::take(10)->get();
    
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
    


   
    public function credit(Request $req){
        $vendedorId=$req['vendedor_id'];
        $tipo=$req['tipo']; //credito por defecto
        $monto=$req['monto'];
        $idOrigen=$req['id_origen'];//id de pedido o retiro
        $origen=$req['origen'];//tipo de pedido o retiro

        $vendedor = Vendedore::findOrFail($vendedorId);
        $vendedor->saldo+$monto;

        $trans=new Transaction();
        $trans->tipo=$tipo;
        $trans->monto=$monto;
        $trans->$saldo;
        $trans->new Date();
        $trans->idOrigen;
        $trans->origen;
        $trans->vendedorId;
                
        $insetedData =$this->TransactionRepository->create($trans);
        $updatedData= $this->VendedorRepository->update($vendedor, $id);

    }

}