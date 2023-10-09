<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PedidosShopify;
use App\Models\TransaccionPedidoTransportadora;
use App\Models\Transportadora;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransaccionPedidoTransportadoraAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function createFromOrder(Request $request)
    {
        //
        //status,fecha_entrega,precio_total,costo_transportadora,id_pedido,id_transportadora,id_operador        
        $status = $request->input('status');
        $fecha_entrega = $request->input('fecha_entrega');
        $precio_total = $request->input('precio_total');
        $costo_transportadora = $request->input('costo_transportadora');

        $id_pedido = $request->input('id_pedido');
        $id_transportadora = $request->input('id_transportadora');
        $id_operador = $request->input('id_operador');

        $transaccion = new TransaccionPedidoTransportadora();
        $transaccion->status = $status;
        $transaccion->fecha_entrega = $fecha_entrega;
        $transaccion->precio_total = $precio_total;
        $transaccion->costo_transportadora = $costo_transportadora;
        $transaccion->id_pedido = $id_pedido;
        $transaccion->id_transportadora = $id_transportadora;
        $transaccion->id_operador = $id_operador;

        $transaccion->save();
        
        return response()->json(['data' => $transaccion], 200);

    }

    public function getByTransportadoraRangeDate(Request $request)
    {
        //for excel
        $data = $request->json()->all();
        $idTransportadora = $data['id_transportadora'];
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');

        $transportadora = Transportadora::find($idTransportadora);
        $nombre_transportadora = $transportadora->nombre;

        $transacciones = TransaccionPedidoTransportadora::with('pedidos_shopify', 'transportadora', 'operadore')
            ->where('id_transportadora', $idTransportadora)
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->get();

        //for para ir sacando dato del pedido: producto, cantidad, 

        if ($transacciones->isEmpty()) {
            return response()->json(["message" => "No existen datos en este mes-año"], 200);
        }

        return response()->json(['data' => $transacciones], 200);
    }
}
