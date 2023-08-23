<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\pedidos_shopifies;
use App\Models\PedidosShopify;
use App\Models\Ruta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PedidosShopifyAPIController extends Controller
{
    public function index()
    {
        $pedidos = PedidosShopify::all();
        return response()->json($pedidos);
    }

    public function show($id)
    {
        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->findOrFail($id);

        return response()->json($pedido);
    }


    public function getByDateRange(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];
        if ($searchTerm != "") {
            $filteFields = $data['or'];
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        $pedidos = PedidosShopify::with('operadore.up_users')
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->whereRaw("STR_TO_DATE(marca_t_i, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {

                foreach ($filteFields as $field) {
                    $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            })
            ->paginate($pageSize, ['*'], 'page', $pageNumber);





        return response()->json($pedidos);
    }


    public function getProductsDashboardLogistic(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');

        $pedidos = PedidosShopify::with('operadore.up_users')
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->get();



        $stateTotals = [
            'ENTREGADO' => 0,
            'NO ENTREGADO' => 0,
            'NOVEDAD' => 0,
            'REAGENDADO' => 0,
            'EN RUTA' => 0,
            'EN OFICINA' => 0,
            'PEDIDO PROGRAMADO' => 0,
            'TOTAL' => 0
        ];
        foreach ($pedidos as $objeto) {

            $estado = $objeto->status;
            $stateTotals[$estado]++;
        }





        return response()->json([
            'data' => $stateTotals,
        ]);
    }

    public function getProductsDashboardRoutesCount(Request $request)
{
    $data = $request->json()->all();
    $startDate = Carbon::createFromFormat('j/n/Y', $data['start'])->format('Y-m-d');
    $endDate = Carbon::createFromFormat('j/n/Y', $data['end'])->format('Y-m-d');

    $pedidos = PedidosShopify::with([
            'operadore.up_users:id', 
            'transportadora', 
            'pedidoFecha', 
            'ruta', 
            'subRuta'
        ])
        ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDate, $endDate])
        ->get();

    $rutas = Ruta::with(['transportadoras', 'sub_rutas'])->get();

    $routeStateTotals = [];

    foreach ($rutas as $route) {
        $routeStateTotals[$route->id] = [
            'ENTREGADO' => 0,
            'NO ENTREGADO' => 0,
            'NOVEDAD' => 0,
            'REAGENDADO' => 0,
            'EN RUTA' => 0,
            'EN OFICINA' => 0,
            'PEDIDO PROGRAMADO' => 0
        ];
    }

        foreach ($pedidos as $pedido) {
            $newPedido = json_decode($pedido);

            if ($newPedido->ruta !== null) {
                $estado = $newPedido->status;
                $rutaId = $newPedido->ruta[0]->id;
                if (isset($routeStateTotals[$rutaId])) {
                    $routeStateTotals[$rutaId][$estado]++;
                }
            }
        }


    $routesValues = array_map(function ($route) use ($routeStateTotals) {
        return array_merge(['rutaId' => $route->id], $routeStateTotals[$route->id]);
    }, $rutas->all());

    return response()->json([
        'data' => $routesValues
    ]);
}



    // public function store(Request $request)
    // {
    //     $pedido = PedidosShopify::create($request->all());
    //     return response()->json($pedido, Response::HTTP_CREATED);
    // }

    // public function update(Request $request, $id)
    // {
    //     $pedido = PedidosShopify::findOrFail($id);
    //     $pedido->update($request->all());
    //     return response()->json($pedido, Response::HTTP_OK);
    // }

    // public function destroy($id)
    // {
    //     $pedido = PedidosShopify::findOrFail($id);
    //     $pedido->delete();
    //     return response()->json(null, Response::HTTP_NO_CONTENT);
    // }
}
