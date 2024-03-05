<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrdenesRetiro;
use App\Models\PedidosShopify;
use App\Models\Vendedore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiSaldoAPIController extends Controller
{
    //
    public function getSaldo($id)
    {
        $sumaEntregados = 0.0;
        $sumaCostoInicial = 0.0;
        $sumaCosto = 0.0;

        $sumaDevolucionInicial = 0.0;
        $sumaDevolucion = 0.0;
        $sumaRetiros = 0.0;

        $upuser = $id;

        // $searchGeneralProduct = PedidosShopify::with(['pedido_fechas', 'up_users']);
        // $searchGeneralSellers = Vendedore::all();
        // $searchWithDrawal = OrdenesRetiro::with('ordenes_retiros_users_permissions_user_links');

        //SUMA ENTREGADOS
        // foreach ($searchGeneralProduct as $producto) {
        //     if ($producto->id_comercial == $upuser && $producto->status == "ENTREGADO") {
        //         $sumaEntregados += floatval($producto->precio_total);
        //     }
        // }

        $sumaEntregados = PedidosShopify::where('id_comercial', $upuser)
            ->where('estado_interno', 'CONFIRMADO')
            ->where('estado_logistico', 'ENVIADO')
            ->where('status', 'ENTREGADO')
            ->sum('precio_total');

        //OBTENER COSTO SUMA SELLER
        // foreach ($searchGeneralSellers as $seller) {
        //     if ($seller->id_master == $upuser) {
        //         $sumaCostoInicial += floatval($seller->costo_envio);
        //     }
        // }
        $sumaCostoInicial = Vendedore::where('id_master', $upuser)
            ->sum('costo_envio');

        //SUMA COSTO
        // foreach ($searchGeneralProduct as $producto) {
        //     if ($producto->id_comercial == $upuser && ($producto->status == "ENTREGADO" || $producto->status == "NO ENTREGADO")) {
        //         $sumaCosto += $sumaCostoInicial;
        //     }
        // }

        $sumaCostodb = DB::table('pedidos_shopifies')
            ->selectRaw('SUM(' . $sumaCostoInicial . ') as sumaCosto')
            ->where('estado_interno', 'CONFIRMADO')
            ->where('estado_logistico', 'ENVIADO')
            ->where('id_comercial', $upuser)
            ->where(function ($query) {
                $query->where('status', 'ENTREGADO')
                    ->orWhere('status', 'NO ENTREGADO');
            })
            ->first();
        $sumaCosto = $sumaCostodb->sumaCosto;


        //OBTENER DEVOLUCION SUMA SELLER
        // foreach ($searchGeneralSellers as $seller) {
        //     if ($seller->id_master == $upuser) {
        //         $sumaDevolucionInicial += floatval($seller->costo_devolucion);
        //     }
        // }

        $sumaDevolucionInicial = DB::table('vendedores')
            ->where('id_master', $upuser)
            ->sum('costo_devolucion');

        //SUMA DEVOLUCION
        // foreach ($searchGeneralProduct as $producto) {
        //     if (
        //         $producto->id_comercial == $upuser &&
        //         ($producto->estado_devolucion == "ENTREGADO EN OFICINA" ||
        //             $producto->estado_devolucion == "DEVOLUCION EN RUTA" ||
        //             $producto->estado_devolucion == "EN BODEGA"
        //         ) &&
        //         $producto->status == "NOVEDAD"
        //     ) {
        //         $sumaDevolucion += $sumaDevolucionInicial;
        //     }
        // }

        // $sumaDevolucion = DB::table('pedidos_shopifies')
        //     ->where('id_comercial', $upuser)
        //     ->where('estado_interno', 'CONFIRMADO')
        //     ->where('estado_logistico', 'ENVIADO')
        //     ->where('status', 'NOVEDAD')
        //     ->where(function ($query) {
        //         $query->orWhere('estado_devolucion', 'ENTREGADO EN OFICINA')
        //             ->orWhere('estado_devolucion', 'DEVOLUCION EN RUTA')
        //             ->orWhere('estado_devolucion', 'EN BODEGA')
        //             ->orWhere('estado_devolucion', 'EN BODEGA PROVEEDOR');
        //     })
        //     ->sum(DB::raw($sumaDevolucionInicial));
        $sumaDevolucion = DB::table('pedidos_shopifies')
            ->where('id_comercial', $upuser)
            ->where('estado_interno', 'CONFIRMADO')
            ->where('estado_logistico', 'ENVIADO')
            ->where('status', 'NOVEDAD')
            ->where(function ($query) {
                $query->where('estado_devolucion', '<>', 'PENDIENTE')
                    ->where(function ($query) {
                        $query->orWhere('estado_devolucion', 'ENTREGADO EN OFICINA')
                            ->orWhere('estado_devolucion', 'DEVOLUCION EN RUTA')
                            ->orWhere('estado_devolucion', 'EN BODEGA')
                            ->orWhere('estado_devolucion', 'EN BODEGA PROVEEDOR');
                    });
            })
            ->sum(DB::raw($sumaDevolucionInicial));


        // foreach ($searchWithDrawal as $retiro) {
        //     if (
        //         $retiro->ordenes_retiros_users_permissions_user_links->upuser == $upuser &&
        //         $retiro->estado == "REALIZADO"
        //     ) {
        //         $sumaRetiros += floatval($retiro->monto);    
        //     }
        // }

        // ! ----------------------------------------------------------------
        // $sumaRetiros = OrdenesRetiro::join('ordenes_retiros_users_permissions_user_links as l', 'ordenes_retiros.id', '=', 'l.ordenes_retiro_id')
        //     ->where('l.user_id', $upuser)
        //     ->where('ordenes_retiros.fecha', '>=', '2024-02-29 00:00:00') // Fecha posterior al 29-02-2024
        //     ->where(function ($query) {
        //         $query->where('ordenes_retiros.estado', 'APROBADO')
        //             ->orWhere('ordenes_retiros.estado', 'REALIZADO');
        //     })
        //     ->sum('ordenes_retiros.monto');

        // $sumaRetirosRealizados = OrdenesRetiro::join('ordenes_retiros_users_permissions_user_links as l', 'ordenes_retiros.id', '=', 'l.ordenes_retiro_id')
        //     ->where('l.user_id', $upuser)
        //     ->where('ordenes_retiros.estado', 'REALIZADO')
        //     ->where('ordenes_retiros.fecha', '<=', '2024-02-28 00:00:00') // Fecha anterior o igual al 28-02-2024
        //     // ->where(function ($query) {
        //     //     $query->where('ordenes_retiros.estado', 'APROBADO')
        //     //         ->orWhere('ordenes_retiros.estado', 'REALIZADO');
        //     // })
        //     ->sum('ordenes_retiros.monto');

        // $totalSumaRetiros = $sumaRetiros + $sumaRetirosRealizados;
        // ! ----------------------------------------------------------------


        $sumaRetiros = OrdenesRetiro::join('ordenes_retiros_users_permissions_user_links as l', 'ordenes_retiros.id', '=', 'l.ordenes_retiro_id')
            ->where('l.user_id', $upuser)
                // ->where('ordenes_retiros.estado', 'REALIZADO')
            ->where(function ($query) {
                $query->where('ordenes_retiros.estado', 'APROBADO')
                    ->orWhere('ordenes_retiros.estado', 'REALIZADO');
            })
        ->sum('ordenes_retiros.monto');

        $responseFinal = ($sumaEntregados - ($sumaCosto + $sumaDevolucion + $sumaRetiros));
        // $responseFinal = ($sumaEntregados - ($sumaCosto + $sumaDevolucion + $totalSumaRetiros));

        return [
            'code' => 200,
            'value' => $responseFinal,
            'sumaEntregados' => $sumaEntregados ,
            'sumaCosto' => $sumaCosto , 
            'sumaDevolucion' => $sumaDevolucion ,
            'sumaRetiros' => $sumaRetiros ,
        ];
    }
}
