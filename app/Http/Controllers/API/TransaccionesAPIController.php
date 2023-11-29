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

    public function paymentOrderDelivered(Request $request) {
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
            $pedido->comentario=$data["comentario"];
            if($data["archivo"]!=""){
            $pedido->archivo=$data["archivo"];
            }
            $pedido->save();
            DB::commit(); // Confirma la transacción si todas las operaciones tienen éxito
    
            return response()->json([
                "res" => "transaccion exitosa"
            ]);
        } catch (\Exception $e) {
            DB::rollback(); // En caso de error, revierte todos los cambios realizados en la transacción
            // Maneja el error aquí si es necesario
        }
    }


    public function paymentOrderNotDelivered(Request $request) {
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
            $pedido->comentario=$data["comentario"];
              $pedido->archivo=$data["archivo"];
            $pedido->save();
            DB::commit(); // Confirma la transacción si todas las operaciones tienen éxito  
            return response()->json([
                "res" => "transaccion exitosa"
            ]);
        } catch (\Exception $e) {
            DB::rollback(); // En caso de error, revierte todos los cambios realizados en la transacción
            // Maneja el error aquí si es necesario
        }
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
