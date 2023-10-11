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
        $tipo= $data['tipo'];
        $idOrigen= $data['id_origen'];
        $origen= $data['origen'];
        $idVendedor= $data['id_vendedor'];



        $pedido = Transaccion::where('tipo',$tipo)->where('id_origen',$idOrigen)->where('origen',$origen)->where('id_vendedor',$idVendedor)
            ->get();

        return response()->json($pedido);
    }

      public function getTransactionsByDate(Request $request)
    {
        $data = $request->json()->all();
        $search = $data['search'];
        if($data['start']==null){
            $data['start']= "2023-01-10 00:00:00";
       }
       if($data['end']==null){
        $data['end']= "2223-01-10 00:00:00";
      }
        $startDate = Carbon::parse($data['start']);
        $endDate = Carbon::parse($data['end']);
        
      
       
        
        $filteredData = Transaccion::whereBetween('marca_de_tiempo', [$startDate, $endDate]);
        if($search!=""){
        $filteredData->where("id_origen",'like', '%'.$search.'%');
        }
        
        
        
        return response()->json($filteredData->get());


    }



    public function last30rows()
    {
        $ultimosRegistros = Transaccion::orderBy('id', 'desc')
            ->limit(30)
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
        $startDate = $data['act_date'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y H:i', $startDate)->format('Y-m-d H:i');
        $vendedorId = $data['id'];
        $tipo = "credit";
        $monto = $data['monto'];
        $idOrigen = $data['id_origen'];
        $origen = $data['origen'];
        $comentario = $data['comentario'];

        $user=UpUser::where("id",$vendedorId)->with('vendedores')->first();
        $vendedor =$user['vendedores'][0];
        $saldo=$vendedor->saldo;
        $nuevoSaldo = $saldo + $monto;
        $vendedor->saldo = $nuevoSaldo;


        $newTrans = new Transaccion();

        $newTrans->tipo = $tipo;
        $newTrans->monto = $monto;
        $newTrans->valor_anterior = $saldo;

        $newTrans->valor_actual = $nuevoSaldo;
        $newTrans->marca_de_tiempo = $startDateFormatted;
        $newTrans->id_origen = $idOrigen;
        $newTrans->origen = $origen;
        $newTrans->comentario=$comentario;
        $newTrans->id_vendedor = $vendedorId;
        $insertedData = $this->transaccionesRepository->create($newTrans);
        $updatedData = $this->vendedorRepository->update($nuevoSaldo, $user['vendedores'][0]['id']);

        return response()->json("Monto acreditado");

    }
    public function Debit(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['act_date'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y H:i', $startDate)->format('Y-m-d H:i');
        $vendedorId = $data['id'];
        $vendedorId = $data['id'];
        $tipo = "debit";
        $monto = $data['monto'];
        $idOrigen = $data['id_origen'];
        $origen = $data['origen'];
        $comentario = $data['comentario'];

        $user=UpUser::where("id",$vendedorId)->with('vendedores')->first();
        $vendedor =$user['vendedores'][0];
        $saldo=$vendedor->saldo;
        $nuevoSaldo = $saldo - $monto;
        $vendedor->saldo = $nuevoSaldo;


        $newTrans = new Transaccion();

        $newTrans->tipo = $tipo;
        $newTrans->monto = $monto;
        $newTrans->valor_actual = $nuevoSaldo;
        $newTrans->valor_anterior = $saldo;
        $newTrans->marca_de_tiempo = $startDateFormatted;
        $newTrans->id_origen = $idOrigen;
        $newTrans->origen = $origen;
        $newTrans->comentario=$comentario;

        $newTrans->id_vendedor = $vendedorId;
        $insertedData = $this->transaccionesRepository->create($newTrans);
        $updatedData = $this->vendedorRepository->update($nuevoSaldo, $user['vendedores'][0]['id']);

        return response()->json("Monto debitado");

    }

   
    public function getTransactionsById($id)
    {
        $transaccions = Transaccion::where("id_vendedor",$id)->orderBy('id', 'desc')->get();
        
        return response()->json($transaccions);


    }

    public function rollbackTransaction($id){
        $transaction = Transaccion::findOrFail($id);
        $vendedor = UpUser::find($transaction->id_vendedor)->vendedores;
          if($transaction->tipo=="credit"){
         
        
               
            $vendedor[0]->saldo=$vendedor[0]->saldo-$transaction->monto;

          
        }
        if($transaction->tipo=="debit"){
            $vendedor[0]->saldo=$vendedor[0]->saldo+$transaction->monto;

        }
        $this->vendedorRepository->update($vendedor[0]->saldo, $vendedor[0]->id);
        $transaction->delete();
       
        return response()->json(["vendedor"=>$vendedor[0],"transaccion"=>$transaction]);

    }
}



?>