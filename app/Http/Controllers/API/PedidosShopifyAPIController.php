<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PedidosShopify;
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
        $pedido = PedidosShopify::findOrFail($id);
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
        $searchTerm=$data['search'];
        if($searchTerm!=""){
        $filteFields=$data['or'];
                $filteFields=$data['or'];
        }else{
            $filteFields=[];
        }

        $pedidos = PedidosShopify::with('operadore.up_users')
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')->with('subRuta')
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {

                foreach ($filteFields as $field) {
                    $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            })
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
    

          


        return response()->json($pedidos);
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
