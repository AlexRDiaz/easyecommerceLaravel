<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use App\Models\pedidos_shopifies;
use App\Models\Operadore;
use App\Models\PedidosShopify;
use App\Models\Ruta;
use App\Models\Transportadora;
use App\Models\TransportadorasShippingCost;
// use App\Models\Ruta;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use LengthException;

class TransportadorasAPIController extends Controller
{

    public function index()
    {
        $transportadoras = Transportadora::all();
        return response()->json($transportadoras);
    }
    public function show($id)
    {
        $trasnportadora = Transportadora::with(['admin_user', 'operadores', 'rutas'])
            ->findOrFail($id);

        return response()->json($trasnportadora);
    }

    public function getTransportadoras(Request $request)
    {
        // No necesitas $data en este caso, ya que no lo estás utilizando

        $transportadoras = Transportadora::select(DB::raw('CONCAT(nombre, "-", id ) as id_nombre'))->distinct()->get()->pluck('id_nombre');

        return response()->json(['transportadoras' => $transportadoras]);
    }
    public function getRutasOfTransport($transportadoraId)
    {
        // Obtener la transportadora con sus rutas relacionadas
        $transportadora = Transportadora::with(['rutas'])->find($transportadoraId);

        // Verificar si la transportadora existe
        if (!$transportadora) {
            return response()->json(['error' => 'Transportadora no encontrada'], 404);
        }

        // Obtener las rutas de la transportadora y formatear los resultados
        $rutas = $transportadora->rutas->map(function ($ruta) {
            return $ruta->titulo . ' - ' . $ruta->id;
        });

        // Hacer algo con las rutas formateadas (por ejemplo, devolverlas en una respuesta JSON)
        return response()->json(['rutas' => $rutas]);
    }

    public function getTransportadorasOfRuta($rutaId)
    {
        // Obtener la ruta con sus transportadoras relacionadas
        $ruta = Ruta::with(['transportadoras'])->find($rutaId);

        // Verificar si la ruta existe
        if (!$ruta) {
            return response()->json(['error' => 'Ruta no encontrada'], 404);
        }

        // Obtener las transportadoras de la ruta y formatear los resultados
        $transportadoras = $ruta->transportadoras->map(function ($transportadora) {
            return $transportadora->nombre . ' - ' . $transportadora->id;
        });

        // Hacer algo con las transportadoras formateadas (por ejemplo, devolverlas en una respuesta JSON)
        return response()->json(['transportadoras' => $transportadoras]);
    }

    public function getOperatoresbyTransport(Request $request, $id)
    {
        $result = Transportadora::with(['operadores.up_users'])
            ->where('id', $id)
            ->get();
        if ($result->isEmpty()) {
            return response()->json(['message' => 'No se encontraron transportadoras con el ID especificado'], 404);
        }

        // Obtener todos los IDs y usernames de up_users
        $usersData = [];
        $datoparcil = "";
        foreach ($result as $transportadora) {
            foreach ($transportadora->operadores as $operador) {
                $datoparcil = $operador->id;
                foreach ($operador->up_users as $user) {
                    $usersData[] = $user->username . '-' . $datoparcil;
                }
            }
        }

        if (empty($usersData)) {
            $resultData = 'No se encontraron usuarios en up_users.';
        } else {
            $resultData = $usersData;
        }

        return response()->json(['operadores' => $resultData]);
        //      // Extraer y formatear los usuarios (operadores)
        // $usersData = $result->flatMap(function ($transportadora) {
        //     return $transportadora->operadores->flatMap(function ($operador) {
        //         return $operador->up_users->map(function ($user) {
        //             return $user->username. '-' . $user->id ;
        //         });
        //     });
        // });

        // return response()->json(['operadores' => $usersData]);
    }

    public function getTransportsByRoute($idRoute)
    {
        $transportadoras = Transportadora::whereHas('rutas', function ($query) use ($idRoute) {
            $query->where('rutas.id', '=', $idRoute);
        })->get();

        if ($transportadoras->isEmpty()) {
            return response()->json([], 200);
        }

        return response()->json($transportadoras);
    }



    public function getShippingCostPerDay()
    {

        $transportadoras = Transportadora::all();
        // $transportadoraId = 19;
        // $transportadora = Transportadora::find($transportadoraId);

        $shipping_total = 0;
        $count_orders = 0;
        $total_proceeds = 0;
        $total_day = 0;

        $currentDate = now()->format('j/n/Y');
        // $currentDate = '5/9/2023';
        // $currentDate =  Carbon::createFromFormat('j/n/Y', $currentDate)->format('j/n/Y');

        $currentDateTime = date('Y-m-d H:i:s');
        // $desiredTime = '01:13:13';
        // $currentDateTime = Carbon::createFromFormat('d/n/Y H:i:s', $currentDate . ' ' . $desiredTime)->format('Y-m-d H:i:s');


        foreach ($transportadoras as $transportadora) {

            $transportadoraId = $transportadora->id;
            $costo_transportadora = $transportadora->costo_transportadora;

            $transportadoraPedidos = Transportadora::with(['pedidos' => function ($query) use ($currentDate) {
                $query->whereIn('status', ['ENTREGADO', 'NO ENTREGADO'])
                    ->where('fecha_entrega', $currentDate);
            }])
                ->where('id', $transportadoraId)
                ->get();

            $pedidos = $transportadoraPedidos->pluck('pedidos');
            $total_proceeds = 0;
            foreach ($pedidos as $pedido) {
                foreach ($pedido as $detallePedido) {
                    if ($detallePedido["status"] == "ENTREGADO") {
                        $precioTotal = floatval($detallePedido["precio_total"]);
                        $total_proceeds += $precioTotal;
                    }
                }
            }

            $total_proceeds = round($total_proceeds, 2);
            $count_orders =  $pedidos->flatten()->count();
            $shipping_total = $costo_transportadora * $count_orders;
            $total_day = $total_proceeds - $shipping_total;

            $newTransportadoraShippingCost = new TransportadorasShippingCost();
            $newTransportadoraShippingCost->status = 'PENDIENTE';
            $newTransportadoraShippingCost->time_stamp = $currentDateTime;
            $newTransportadoraShippingCost->daily_proceeds = $total_proceeds;
            $newTransportadoraShippingCost->daily_shipping_cost = $shipping_total;
            $newTransportadoraShippingCost->daily_total = $total_day;
            $newTransportadoraShippingCost->id_transportadora = $transportadoraId;
            $newTransportadoraShippingCost->save();
        }

        return response()->json([], 200);
    }

    public function getWithShippingCost(Request $request, $idTransportadora)
    {
        $data = $request->json()->all();
        $monthToFind = $data['month'];
        $yearToFind = $data['year'];

        $shippingCostsMonthly = TransportadorasShippingCost::where('id_transportadora', $idTransportadora)
            ->selectRaw('id, status, DATE(time_stamp) as fecha, daily_proceeds, daily_shipping_cost, daily_total, rejected_reason, url_proof_payment')
            ->whereYear('time_stamp', $yearToFind)
            ->whereMonth('time_stamp', $monthToFind)
            ->get();

        if ($shippingCostsMonthly->isEmpty()) {
            return response()->json(["message" => "No existen datos en este mes-año"], 200);
        }

        return response()->json(['data' => $shippingCostsMonthly], 200);
    }
}
