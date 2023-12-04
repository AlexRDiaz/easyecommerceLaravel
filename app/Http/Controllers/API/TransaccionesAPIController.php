<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PedidosShopify;
use App\Models\Transaccion;
use App\Models\UpUser;
use App\Models\Vendedore;

use App\Repositories\transaccionesRepository;
use App\Repositories\vendedorRepository;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class TransaccionesAPIController extends Controller
{
    protected $transaccionesRepository;
    protected $vendedorRepository;

    public function __construct(transaccionesRepository $transaccionesRepository, vendedorRepository $vendedorRepository)
    {
        $this->transaccionesRepository = $transaccionesRepository;
        $this->vendedorRepository = $vendedorRepository;
    }



    public function getExistTransaction(Request $request)
    {
        $data = $request->json()->all();
        $tipo = $data['tipo'];
        $idOrigen = $data['id_origen'];
        $origen = $data['origen'];
        $idVendedor = $data['id_vendedor'];



        $pedido = Transaccion::where('tipo', $tipo)
            ->where('id_origen', $idOrigen)
            ->where('origen', $origen)->where('id_vendedor', $idVendedor)
            ->get();

        return response()->json($pedido);
    }

    public function getTransactionsByDate(Request $request)
    {
        $data = $request->json()->all();
        $search = $data['search'];
        $and = $data['and'];
        if ($data['start'] == null) {
            $data['start'] = "2023-01-10 00:00:00";
        }
        if ($data['end'] == null) {
            $data['end'] = "2223-01-10 00:00:00";
        }
        $startDate = Carbon::parse($data['start'])->startOfDay();
        $endDate = Carbon::parse($data['end'])->endOfDay();




        $filteredData = Transaccion::whereBetween('marca_de_tiempo', [$startDate, $endDate]);
        if ($search != "") {
            $filteredData->where("codigo", 'like', '%' . $search . '%');
        }
        if ($and != []) {
            $filteredData->where((function ($pedidos) use ($and) {
                foreach ($and as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '=', $valor);
                        }
                    }
                }
            }));
        }



        return response()->json($filteredData->get());
    }

    private function recursiveWhereHas($query, $relation, $property, $searchTerm)
    {
        if ($searchTerm == "null") {
            $searchTerm = null;
        }
        if (strpos($property, '.') !== false) {

            $nestedRelation = substr($property, 0, strpos($property, '.'));
            $nestedProperty = substr($property, strpos($property, '.') + 1);

            $query->whereHas($relation, function ($q) use ($nestedRelation, $nestedProperty, $searchTerm) {
                $this->recursiveWhereHas($q, $nestedRelation, $nestedProperty, $searchTerm);
            });
        } else {
            $query->whereHas($relation, function ($q) use ($property, $searchTerm) {
                $q->where($property, '=', $searchTerm);
            });
        }
    }

    public function last30rows()
    {
        $ultimosRegistros = Transaccion::orderBy('id', 'desc')
            ->limit(300)
            ->get();

        return response()->json($ultimosRegistros);
    }
    public function index()
    {
        $transacciones = Transaccion::all();
        return response()->json($transacciones);
    }

    public function show($id)
    {
        $transaccion = Transaccion::findOrFail($id);
        return response()->json($transaccion);
    }

    public function Credit(Request $request)
    {
        $data = $request->json()->all();
        $startDateFormatted = new DateTime();
        // $startDateFormatted = Carbon::createFromFormat('j/n/Y H:i', $startDate)->format('Y-m-d H:i');
        $vendedorId = $data['id'];
        $tipo = "credit";
        $monto = $data['monto'];
        $idOrigen = $data['id_origen'];
        $codigo = $data['codigo'];
        $origen = $data['origen'];
        $comentario = $data['comentario'];
        $comentario = $data['comentario'];
        $generated_by = $data['generated_by'];


        $user = UpUser::where("id", $vendedorId)->with('vendedores')->first();
        $vendedor = $user['vendedores'][0];
        $saldo = $vendedor->saldo;
        $nuevoSaldo = $saldo + $monto;
        $vendedor->saldo = $nuevoSaldo;


        $newTrans = new Transaccion();

        $newTrans->tipo = $tipo;
        $newTrans->monto = $monto;
        $newTrans->valor_anterior = $saldo;

        $newTrans->valor_actual = $nuevoSaldo;
        $newTrans->marca_de_tiempo = $startDateFormatted;
        $newTrans->id_origen = $idOrigen;
        $newTrans->codigo = $codigo;

        $newTrans->origen = $origen;
        $newTrans->comentario = $comentario;
        $newTrans->id_vendedor = $vendedorId;
        $newTrans->state = 1;
        $newTrans->generated_by = $generated_by;
        $this->transaccionesRepository->create($newTrans);
        $this->vendedorRepository->update($nuevoSaldo, $user['vendedores'][0]['id']);

        return response()->json("Monto acreditado");
    }
    public function Debit(Request $request)
    {
        $data = $request->json()->all();
        $startDateFormatted = new DateTime();
        //  $startDateFormatted = Carbon::createFromFormat('j/n/Y H:i', $startDate)->format('Y-m-d H:i');
        $vendedorId = $data['id'];
        $tipo = "debit";
        $monto = $data['monto'];
        $idOrigen = $data['id_origen'];
        $codigo = $data['codigo'];

        $origen = $data['origen'];
        $comentario = $data['comentario'];
        $generated_by = $data['generated_by'];


        $user = UpUser::where("id", $vendedorId)->with('vendedores')->first();
        $vendedor = $user['vendedores'][0];
        $saldo = $vendedor->saldo;
        $nuevoSaldo = $saldo - $monto;
        $vendedor->saldo = $nuevoSaldo;


        $newTrans = new Transaccion();

        $newTrans->tipo = $tipo;
        $newTrans->monto = $monto;
        $newTrans->valor_actual = $nuevoSaldo;
        $newTrans->valor_anterior = $saldo;
        $newTrans->marca_de_tiempo = $startDateFormatted;
        $newTrans->id_origen = $idOrigen;
        $newTrans->codigo = $codigo;

        $newTrans->origen = $origen;
        $newTrans->comentario = $comentario;

        $newTrans->id_vendedor = $vendedorId;
        $newTrans->state = 1;
        $newTrans->generated_by = $generated_by;

        $insertedData = $this->transaccionesRepository->create($newTrans);
        $updatedData = $this->vendedorRepository->update($nuevoSaldo, $user['vendedores'][0]['id']);

        return response()->json("Monto debitado");
    }

    public function paymentOrderDelivered(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->json()->all();
            $startDateFormatted = new DateTime();

            $request->merge(['comentario' => 'recaudo  de valor de producto por pedido entregado']);
            $request->merge(['origen' => 'recaudo']);

            $this->Credit($request);

            $request->merge(['comentario' => 'costo de envio por pedido  entregado']);
            $request->merge(['origen' => 'envio']);
            $request->merge(['monto' => $data['monto_debit']]);

            $this->Debit($request);

            $vendedor = Vendedore::where("id_master", $data['id'])->get();

            if ($vendedor[0]->referer != null) {
                $refered = Vendedore::where('id_master', $vendedor[0]->referer)->get();
                $vendedorId = $vendedor[0]->referer;
                $generated_by = $data['generated_by'];
                $user = UpUser::where("id", $vendedorId)->with('vendedores')->first();
                $vendedor = $user['vendedores'][0];
                $saldo = $vendedor->saldo;
                $nuevoSaldo = $saldo + $refered[0]->referer_cost;
                $vendedor->saldo = $nuevoSaldo;

                $newTrans = new Transaccion();

                $newTrans->tipo = "credit";
                $newTrans->monto = $refered[0]->referer_cost;
                $newTrans->valor_actual = $nuevoSaldo;
                $newTrans->valor_anterior = $saldo;
                $newTrans->marca_de_tiempo = $startDateFormatted;
                $newTrans->id_origen = $data['id_origen'];
                $newTrans->codigo = $data['codigo'];

                $newTrans->origen = "referido";
                $newTrans->comentario = "comision por referido";

                $newTrans->id_vendedor = $vendedorId;
                $newTrans->state = 1;
                $newTrans->generated_by = $generated_by;

                $this->transaccionesRepository->create($newTrans);
                $this->vendedorRepository->update($nuevoSaldo, $user['vendedores'][0]['id']);
            }

            $pedido = PedidosShopify::findOrFail($data['id_origen']);
            $pedido->status = "ENTREGADO";
            $pedido->fecha_entrega = now()->format('j/n/Y');
            $pedido->status_last_modified_at = date('Y-m-d H:i:s');
            $pedido->status_last_modified_by = $data['generated_by'];
            $pedido->comentario = $data["comentario"];
            if ($data["archivo"] != "") {
                $pedido->archivo = $data["archivo"];
            }
            $pedido->save();
            DB::commit(); // Confirma la transacción si todas las operaciones tienen éxito
            return response()->json([
                "res" => "transaccion exitosa"
            ]);
        } catch (\Exception $e) {
            DB::rollback(); // En caso de error, revierte todos los cambios realizados en la transacción
            // Maneja el error aquí si es necesario
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()
            ], 500); 
        }
    }


    public function paymentOrderNotDelivered(Request $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->json()->all();
            $request->merge(['comentario' => 'costo de envio por pedido no entregado']);
            $request->merge(['origen' => 'envio']);
            $request->merge(['monto' => $data['monto_debit']]);

            $this->Debit($request);

            $pedido = PedidosShopify::findOrFail($data['id_origen']);
            $pedido->status = "NO ENTREGADO";
            $pedido->fecha_entrega = now()->format('j/n/Y');
            $pedido->status_last_modified_at = date('Y-m-d H:i:s');
            $pedido->status_last_modified_by = $data['generated_by'];
            $pedido->comentario = $data["comentario"];
            $pedido->archivo = $data["archivo"];
            $pedido->save();
            DB::commit(); // Confirma la transacción si todas las operaciones tienen éxito  
            return response()->json([
                "res" => "transaccion exitosa"
            ]);
        } catch (\Exception $e) {
            DB::rollback(); // En caso de error, revierte todos los cambios realizados en la transacción
            // Maneja el error aquí si es necesario
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()
            ], 500); 
        }
    }

    public function paymentOrderWithNovelty(Request $request, $id)
    {
        DB::beginTransaction();
        $message="";


        try {
            $data = $request->json()->all();


            $order = PedidosShopify::with(['users.vendedores', 'transportadora', 'novedades'])->find($id);
           

            if (
                $order->estado_devolucion ==
                "ENTREGADO EN OFICINA" ||
                $order->estado_devolucion ==
                "DEVOLUCION EN RUTA" ||
                $order->estado_devolucion == "EN BODEGA"
            ) {

                $transactionOld = Transaccion::where('tipo', 'debit')
                    ->where('id_origen', $order->id)
                    ->where('origen',  "devolucion")->where('id_vendedor', $order->users[0]->vendedores[0]->id_master)
                    ->get();
                if ($transactionOld == []) {

                    $newSaldo = $order->users[0]->vendedores[0]->saldo - $order->users[0]->vendedores[0]->costo_devolucion;

                    $newTrans = new Transaccion();
                    $newTrans->tipo = "debit";
                    $newTrans->monto =  $order->users[0]->vendedores[0]->costo_devolucion;
                    $newTrans->valor_actual = $newSaldo;
                    $newTrans->valor_anterior = $order->users[0]->vendedores[0]->saldo;
                    $newTrans->marca_de_tiempo = new DateTime();
                    $newTrans->id_origen = $order->id;
                    $newTrans->codigo = $order->users[0]->vendedores[0]->nombre_comercial . "-" . $order->numero_orden;

                    $newTrans->origen = "devolucion";
                    $newTrans->comentario = "Costo de devolucion por pedido en NOVEDAD y".$order->estado_devolucion;

                    $newTrans->id_vendedor = $order->users[0]->vendedores[0]->id_master;
                    $newTrans->state = 1;
                    $newTrans->generated_by = $data['generated_by'];

                    $this->transaccionesRepository->create($newTrans);
                    $this->vendedorRepository->update($newSaldo, $order->users[0]->vendedores[0]->id);
                }
                $message="transacción con debito por devolucion";
            }else{ $message="transacción sin debito por devolucion";}

            $order->status = "NOVEDAD";
            $order->status_last_modified_at = date('Y-m-d H:i:s');
            $order->status_last_modified_by = $data['generated_by'];
            $order->comentario = $data['comentario'];
            if ($order->novedades == []) {
                $order->fecha_entrega = now()->format('j/n/Y');
            }
            $order->save();
            DB::commit();

            return response()->json([
                "res" => $message,
               // "pedido" => $order
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()
            ], 500); 
        }
    }

    public function paymentOrderOperatorInOffice(Request $request, $id)
    {
        DB::beginTransaction();
        $message = "";
        $repetida = null;

        try {
            $data = $request->json()->all();
            $order = PedidosShopify::with(['users.vendedores', 'transportadora', 'novedades'])->find($id);
            $order->estado_devolucion = "ENTREGADO EN OFICINA";
            $order->do = "ENTREGADO EN OFICINA";
            $order->marca_t_d = date("d/m/Y H:i");
            $order->received_by = $data['generated_by'];
            $order->save();
    
            if ($order->status == "NOVEDAD") {
                $transactionOld = Transaccion::where('tipo', 'debit')
                    ->where('id_origen', $order->id)
                    ->where('origen', "devolucion")
                    ->where('id_vendedor', $order->users[0]->vendedores[0]->id_master)
                    ->get();
    
                $repetida = $transactionOld;
    
                if (empty($transactionOld->toArray())) { // Verifica si está vacío convirtiendo a un array
                    $newSaldo = $order->users[0]->vendedores[0]->saldo - $order->users[0]->vendedores[0]->costo_devolucion;
    
                    $newTrans = new Transaccion();
                    $newTrans->tipo = "debit";
                    $newTrans->monto = $order->users[0]->vendedores[0]->costo_devolucion;
                    $newTrans->valor_actual = $newSaldo;
                    $newTrans->valor_anterior = $order->users[0]->vendedores[0]->saldo;
                    $newTrans->marca_de_tiempo = new DateTime();
                    $newTrans->id_origen = $order->id;
                    $newTrans->codigo = $order->users[0]->vendedores[0]->nombre_comercial . "-" . $order->numero_orden;
                    $newTrans->origen = "devolucion";
                    $newTrans->comentario = "Costo de devolución desde operador por pedido en " . $order->status . " y " . $order->estado_devolucion;
                    $newTrans->id_vendedor = $order->users[0]->vendedores[0]->id_master;
                    $newTrans->state = 1;
                    $newTrans->generated_by = $data['generated_by'];
    
                    $this->transaccionesRepository->create($newTrans);
                    $this->vendedorRepository->update($newSaldo, $order->users[0]->vendedores[0]->id);
    
                    $message = "Transacción con débito por estado ".$order->status . " y " . $order->estado_devolucion;
                }else{
                    $message = "Transacción ya cobrada";
                }
            }else{
                $message = "Transacción sin débito por estado".$order->status . " y " . $order->estado_devolucion;
            }

            DB::commit();
    
            return response()->json([
                "res" => $message,
                "transaccion_repetida" => $repetida
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    public function paymentLogisticInWarehouse(Request $request, $id)
    {
        DB::beginTransaction();
        $message = "";
        $repetida = null;

        try {
            $data = $request->json()->all();
            $order = PedidosShopify::with(['users.vendedores', 'transportadora', 'novedades'])->find($id);
            $order->estado_devolucion = "EN BODEGA";
            $order->dl = "EN BODEGA";
            $order->marca_t_d_l =  date("d/m/Y H:i");
            $order->received_by = $data['generated_by'];
            $order->save();
    
            if ($order->status == "NOVEDAD") {
                $transactionOld = Transaccion::where('tipo', 'debit')
                    ->where('id_origen', $order->id)
                    ->where('origen', "devolucion")
                    ->where('id_vendedor', $order->users[0]->vendedores[0]->id_master)
                    ->get();
    
                $repetida = $transactionOld;
    
                if (empty($transactionOld->toArray())) { // Verifica si está vacío convirtiendo a un array
                    $newSaldo = $order->users[0]->vendedores[0]->saldo - $order->users[0]->vendedores[0]->costo_devolucion;
    
                    $newTrans = new Transaccion();
                    $newTrans->tipo = "debit";
                    $newTrans->monto = $order->users[0]->vendedores[0]->costo_devolucion;;
                    $newTrans->valor_actual = $newSaldo;
                    $newTrans->valor_anterior = $order->users[0]->vendedores[0]->saldo;
                    $newTrans->marca_de_tiempo = new DateTime();
                    $newTrans->id_origen = $order->id;
                    $newTrans->codigo = $order->users[0]->vendedores[0]->nombre_comercial . "-" . $order->numero_orden;
                    $newTrans->origen = "devolucion";
                    $newTrans->comentario = "Costo de devolución desde logística por pedido en " . $order->status . " y " . $order->estado_devolucion;
                    $newTrans->id_vendedor = $order->users[0]->vendedores[0]->id_master;
                    $newTrans->state = 1;
                    $newTrans->generated_by = $data['generated_by'];
                    $this->transaccionesRepository->create($newTrans);
                    $this->vendedorRepository->update($newSaldo, $order->users[0]->vendedores[0]->id);

                    $message = "Transacción con débito por estado ".$order->status . " y " . $order->estado_devolucion;
                }else{
                    $message = "Transacción ya cobrada";
                }
            }else{
                $message = "Transacción sin débito por estado".$order->status . " y " . $order->estado_devolucion;
            }

            DB::commit();
    
            return response()->json([
                "res" => $message,
                "transaccion_repetida" => $repetida
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }



    public function paymentTransportByReturnStatus(Request $request, $id)
    {
        DB::beginTransaction();
        $message = "";
        $repetida = null;

        try {
            $data = $request->json()->all();
            $order = PedidosShopify::with(['users.vendedores', 'transportadora', 'novedades'])->find($id);
            if($data["return_status"]=="ENTREGADO EN OFICINA"){
                $order->estado_devolucion = $data["return_status"];
                $order->dt = $data["return_status"];
                $order->marca_t_d = date("d/m/Y H:i");
                $order->received_by =$data['generated_by'];
            }
            if($data["return_status"]=="DEVOLUCION EN RUTA"){
                $order->estado_devolucion = $data["return_status"];
                $order->dt =  $data["return_status"];
                $order->marca_t_d_t = date("d/m/Y H:i");
                $order->received_by = $data['generated_by'];
            }
        
            $order->save();
                 
            if ($order->status == "NOVEDAD") {
                $transactionOld = Transaccion::where('tipo', 'debit')
                    ->where('id_origen', $order->id)
                    ->where('origen', "devolucion")
                    ->where('id_vendedor', $order->users[0]->vendedores[0]->id_master)
                    ->get();
    
                $repetida = $transactionOld;
              
                if (empty($transactionOld->toArray())) { // Verifica si está vacío convirtiendo a un array
                    $newSaldo = $order->users[0]->vendedores[0]->saldo - $order->users[0]->vendedores[0]->costo_devolucion;
    
                    $newTrans = new Transaccion();
                    $newTrans->tipo = "debit";
                    $newTrans->monto = $order->users[0]->vendedores[0]->costo_devolucion;
                    $newTrans->valor_actual = $newSaldo;
                    $newTrans->valor_anterior = $order->users[0]->vendedores[0]->saldo;
                    $newTrans->marca_de_tiempo = new DateTime();
                    $newTrans->id_origen = $order->id;
                    $newTrans->codigo = $order->users[0]->vendedores[0]->nombre_comercial . "-" . $order->numero_orden;
                    $newTrans->origen = "devolucion";
                    $newTrans->comentario = "Costo de devolución desde transportadora por pedido en " . $order->status . " y " . $order->estado_devolucion;
                    $newTrans->id_vendedor = $order->users[0]->vendedores[0]->id_master;
                    $newTrans->state = 1;
                    $newTrans->generated_by = $data['generated_by'];
    
                    $this->transaccionesRepository->create($newTrans);
                    $this->vendedorRepository->update($newSaldo, $order->users[0]->vendedores[0]->id);
    
                    $message = "Transacción con débito por estado ".$order->status . " y " . $order->estado_devolucion;
                 
                }else{
                    $message = "Transacción sin débito, ya ha sido cobrada";
                }
            }else{
                $message = "Transacción sin débito por estado ".$order->status . " y " . $order->estado_devolucion;
            }

            DB::commit();
    
            return response()->json([
                "res" => $message,
                "transaccion_repetida" => $repetida,
                "pedido"=>$order
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function updateFieldTime(Request $request, $id)
    {
        $data = $request->all();

        $keyvalue = $data['keyvalue'];

        // $key = $data['key'];
        // $value = $data['value'];
        $idUser = $data['iduser'];
        $from = $data['from'];
        $datarequest = $data['datarequest'];

        $parts = explode(":", $keyvalue);
        if (count($parts) === 2) {
            $key = $parts[0];
            $value = $parts[1];
        }

        $currentDateTime = date('Y-m-d H:i:s');
        // "${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}"
        $date = now()->format('j/n/Y');
        //"${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year} ${DateTime.now().hour}:${DateTime.now().minute} ";
        $currentDateTimeText = date("d/m/Y H:i");

        $pedido = PedidosShopify::findOrFail($id);
        if ($key == "estado_logistico") {
            if ($value == "IMPRESO") {  //from log,sell
                $pedido->estado_logistico = $value;
                $pedido->printed_at = $currentDateTime;
                $pedido->printed_by = $idUser;
            }
            if ($value == "ENVIADO") {  //from log,sell
                $pedido->estado_logistico = $value;
                $pedido->sent_at = $currentDateTime;
                $pedido->sent_by = $idUser;
                $pedido->marca_tiempo_envio = $date;
                $pedido->estado_interno = "CONFIRMADO";
                $pedido->fecha_entrega = $date;
            }
        }
        if ($key == "estado_devolucion") {
            if ($value == "EN BODEGA") { //from logistic
                $pedido->estado_devolucion = $value;
                $pedido->dl = $value;
                $pedido->marca_t_d_l = $currentDateTimeText;
                $pedido->received_by = $idUser;
            }
            if ($from == "carrier") {
                if ($value == "ENTREGADO EN OFICINA") {
                    $pedido->estado_devolucion = $value;
                    $pedido->dt = $value;
                    $pedido->marca_t_d = $currentDateTimeText;
                    $pedido->received_by = $idUser;
                }
                if ($value == "DEVOLUCION EN RUTA") {
                    $pedido->estado_devolucion = $value;
                    $pedido->dt = $value;
                    $pedido->marca_t_d_t = $currentDateTimeText;
                    $pedido->received_by = $idUser;
                }
              
            } elseif ($from == "operator") {
                if ($value == "ENTREGADO EN OFICINA") { //from operator, logistica
                    $pedido->estado_devolucion = $value;
                    $pedido->do = $value;
                    $pedido->marca_t_d = $currentDateTimeText;
                    $pedido->received_by = $idUser;
                }
            }
        }


        if ($key == "status") {
            if ($value != "NOVEDAD_date") {
                $pedido->status = $value;
            }
            $pedido->fill($datarequest);
            if ($value == "ENTREGADO" || $value == "NO ENTREGADO") {
                $pedido->fecha_entrega = $date;
            }
            if ($value == "NOVEDAD_date") {
                $pedido->status = "NOVEDAD";
                $pedido->fecha_entrega = $date;
            }
            $pedido->status_last_modified_at = $currentDateTime;
            $pedido->status_last_modified_by = $idUser;
        }

        //v0
        if ($key == "estado_interno") {
            $pedido->confirmed_by = $idUser;
            $pedido->confirmed_at = $currentDateTime;
        }

        $pedido->save();
        return response()->json([$pedido], 200);
    }
    public function cleanTransactionsFailed($id)
    {
        $transaccions = Transaccion::where("id_origen", $id)->where('state', '1')->whereNot("origen", "reembolso")->get();
        foreach ($transaccions as $transaction) {
            if ($transaction->state == 1) {
                $vendedor = UpUser::find($transaction->id_vendedor)->vendedores;
                if ($transaction->tipo == "credit") {
                    $vendedor[0]->saldo = $vendedor[0]->saldo - $transaction->monto;
                }
                if ($transaction->tipo == "debit") {
                    $vendedor[0]->saldo = $vendedor[0]->saldo + $transaction->monto;
                }

                $this->vendedorRepository->update($vendedor[0]->saldo, $vendedor[0]->id);
                $transaction->delete();
            }
        }
        return response()->json("ok");
    }
    public function getTransactionsById($id)
    {
        $transaccions = Transaccion::where("id_vendedor", $id)->orderBy('id', 'desc')->get();

        return response()->json($transaccions);
    }
    public function getTransactionToRollback($id)
    {
        $transaccion = Transaccion::where("id_origen", $id)->where('state', '1')->whereNot("origen", "reembolso")->get();



        return response()->json($transaccion);
    }

    public function rollbackTransaction(Request $request)
    {
        $data = $request->json()->all();
        $generated_by = $data['generated_by'];

        $ids = $data['ids'];
        $reqTrans = [];
        $reqPedidos = [];

        foreach ($ids as $id) {

            $transaction = Transaccion::find($id);
            array_push($reqTrans, $transaction);

            if ($transaction->state == 1) {

                $pedido = PedidosShopify::where("id", $transaction->id_origen)->first();
                array_push($reqPedidos, $pedido);

                $vendedor = UpUser::find($transaction->id_vendedor)->vendedores;
                if ($transaction->tipo == "credit") {
                    $transactionResetValues = new Transaccion();
                    $transactionResetValues->tipo = "debit";
                    $transactionResetValues->monto = $transaction->monto;
                    $transactionResetValues->valor_anterior = $vendedor[0]->saldo;
                    $transactionResetValues->valor_actual = $vendedor[0]->saldo - $transaction->monto;
                    $transactionResetValues->marca_de_tiempo = new DateTime();
                    $transactionResetValues->id_origen = $transaction->id_origen;
                    $transactionResetValues->codigo = $transaction->codigo;
                    $transactionResetValues->origen = "reembolso";
                    $transactionResetValues->id_vendedor = $transaction->id_vendedor;
                    $transactionResetValues->comentario = "error de transaccion";
                    $transactionResetValues->state = 0;
                    $transactionResetValues->generated_by = $generated_by;


                    $transactionResetValues->save();
                    $vendedor[0]->saldo = $vendedor[0]->saldo - $transaction->monto;
                }
                if ($transaction->tipo == "debit") {

                    $transactionResetValues = new Transaccion();
                    $transactionResetValues->tipo = "credit";
                    $transactionResetValues->monto = $transaction->monto;
                    $transactionResetValues->valor_anterior = $vendedor[0]->saldo;
                    $transactionResetValues->valor_actual = $vendedor[0]->saldo + $transaction->monto;
                    $transactionResetValues->marca_de_tiempo = new DateTime();
                    $transactionResetValues->id_origen = $transaction->id_origen;
                    $transactionResetValues->codigo = $transaction->codigo;
                    $transactionResetValues->origen = "reembolso";
                    $transactionResetValues->id_vendedor = $transaction->id_vendedor;
                    $transactionResetValues->comentario = "error de transaccion";
                    $transactionResetValues->state = 0;
                    $transactionResetValues->generated_by = $generated_by;

                    $transactionResetValues->save();




                    $vendedor[0]->saldo = $vendedor[0]->saldo + $transaction->monto;
                }
                $transaction->state = 0;
                $transaction->save();
                $this->vendedorRepository->update($vendedor[0]->saldo, $vendedor[0]->id);
            }
        }

        return response()->json([
            "transacciones" => $reqTrans,
            "pedidps" => $reqPedidos

        ]);
    }
}
