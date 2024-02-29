<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\ValidationCode;
use App\Models\OrdenesRetiro;
use App\Models\OrdenesRetirosUsersPermissionsUserLink;
use App\Models\Transaccion;
use App\Models\UpUser;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\API\TransaccionesAPIController;
use Carbon\Carbon;


use App\Repositories\transaccionesRepository;
use App\Repositories\vendedorRepository;

class OrdenesRetiroAPIController extends Controller
{

    protected $transaccionesRepository;
    protected $vendedorRepository;

    public function show($id)
    {
        $trasnportadora = OrdenesRetiro::findOrFail($id);

        return response()->json($trasnportadora);
    }

    public function withdrawal(Request $request, $id)
    {
        //     // Obtiene los datos del cuerpo de la solicitud
        $data = $request->validate([
            'monto' => 'required',
            'email' => 'required|email',
            'id_vendedor' => 'required'
        ]);

        // //     // Obtener datos del request
        $monto = $request->input('monto');
        // $fecha = $request->input('fecha');
        $fecha = date("d/m/Y H:i:s");
        $email  = $request->input('email');
        $idVendedor  = $request->input('id_vendedor');

        // //     // Generar código único
        $numerosUtilizados = [];
        while (count($numerosUtilizados) < 10000000) {
            $numeroAleatorio = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            if (!in_array($numeroAleatorio, $numerosUtilizados)) {
                $numerosUtilizados[] = $numeroAleatorio;
                break;
            }
        }
        $resultCode = $numeroAleatorio;
        //  $resultCode = implode('', array_slice($numerosUnicos, 0, 8));


        Mail::to($email)->send(new ValidationCode($resultCode, $monto));

        //     // Crea un registro de retiro
        $withdrawal = new OrdenesRetiro();
        $withdrawal->monto = $monto;
        $withdrawal->fecha = $fecha;
        $withdrawal->codigo_generado = $resultCode;
        $withdrawal->estado = 'PENDIENTE';
        $withdrawal->id_vendedor = $idVendedor;
        $withdrawal->save();

        $ordenUser = new OrdenesRetirosUsersPermissionsUserLink();
        $ordenUser->ordenes_retiro_id = $withdrawal->id;
        $ordenUser->user_id = $id;
        $ordenUser->save();



        return response()->json(['code' => 200]);
    }

    public function postWithdrawalProvider(Request $request)
    {
        try {
            //code...
            $user = UpUser::where("id", $request->input('user_id'))->with('vendedores')->first();


            $data = $request->validate([
                'monto' => 'required',
                'email' => 'required|email',

            ]);

            
            $monto = $request->input('monto');
            $email = $request->input('email');
             $user_id=$request->input('user_id');
            $user = UpUser::where("id",$user_id)->with('vendedores')->first();
            

        if($user->vendedores[0]->saldo >= $monto){


            //     // Generar código único
            $numerosUtilizados = [];
            while (count($numerosUtilizados) < 10000000) {
                $numeroAleatorio = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
                if (!in_array($numeroAleatorio, $numerosUtilizados)) {
                    $numerosUtilizados[] = $numeroAleatorio;
                    break;
                }
            }
            $resultCode = $numeroAleatorio;


            Mail::to($email)->send(new ValidationCode($resultCode, $monto));


            return response()->json(["response" => "code generated succesfully", "code" => $resultCode], Response::HTTP_OK);
           }else{
            return response()->json(["response" => "saldo insuficiente"], Response::HTTP_BAD_REQUEST);

           }
        } catch (\Exception $e) {
            return response()->json(["response" => "error al generar el codigo", "error" => $e], Response::HTTP_BAD_REQUEST);
        }
    }

    public function putRealizado(Request $request,$id){
         try {
            $data = $request->json()->all();

            $withdrawal = OrdenesRetiro::findOrFail($id);
            $withdrawal->estado = "REALIZADO";
            $withdrawal->comprobante = $data["comprobante"];
            $withdrawal->comentario = $data["comentario"];
            $withdrawal->fecha_transferencia =  Carbon::now()->format('j/n/Y H:i:s');
            $withdrawal->save();

            return response()->json(["response" => "edited succesfully"], Response::HTTP_OK);

         } catch (\Exception $e) {
            return response()->json(["response" => "edidted failed", "error" => $e], Response::HTTP_BAD_REQUEST);

         }
    }


    public function putIntern(Request $request, $id)
    {
        try {
            $data = $request->json()->all();

            $withdrawal = OrdenesRetiro::findOrFail($id);
            $withdrawal->comprobante = $data["comprobante"];
            $withdrawal->comentario = $data["comentario"];
            // $withdrawal->fecha_transferencia =  Carbon::now()->format('j/n/Y H:i:s');
            $withdrawal->save();

            return response()->json(["response" => "edited succesfully"], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(["response" => "edidted failed", "error" => $e], Response::HTTP_BAD_REQUEST);

        }
    }

    public function putRechazado(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $transaccionesRepository = app()->make('App\Repositories\transaccionesRepository');
            $vendedorRepository = app()->make('App\Repositories\vendedorRepository');

            $transactionsController = new TransaccionesAPIController($transaccionesRepository, $vendedorRepository);


            $data = $request->json()->all();
            $monto = $data["monto"];
            $idOrdenRetiro = $id;
            $userSesion = $data["idSesion"];

            $withdrawal = OrdenesRetiro::findOrFail($id);
            $withdrawal->estado = "RECHAZADO";
            $withdrawal->save();

            if ($withdrawal) {
                $transactionsController->CreditLocal(
                    $userSesion,
                    $monto,
                    $idOrdenRetiro,
                    "0000",
                    "reembolso",
                    "reembolso orden retiro cancelada",
                    $userSesion
                );

            }

            DB::commit(); // Confirma la transacción si todas las operaciones tienen éxito

            return response()->json(["response" => "update succesfully"], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollback(); // En caso de error, revierte todos los cambios realizados en la transacción

            return response()->json(["response" => "edidted failed", "error" => $e], Response::HTTP_BAD_REQUEST);

        }
    }






    public function getOrdenesRetiroNew($id, Request $request)
    {

        $retiros = OrdenesRetiro::with('users_permissions_user')->whereHas('users_permissions_user', function ($query) use ($id) {
            $query->where('up_users.id', $id);
        })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($retiros);
    }

    public function getOrdenesRetiro($id, Request $request)
    {

        $data = $request->json()->all();
        // $startDate = $data['start'];
        // $endDate = $data['end'];
        // $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        // $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];

        $upuser = UpUser::find($id);
        if ($upuser) {

            $upuser = UpUser::find($id);
            if ($upuser) {
                $retiros = DB::table('ordenes_retiros as o')
                    ->whereExists(function ($query) use ($id) {
                        $query->select(DB::raw(1))
                            ->from('ordenes_retiros_users_permissions_user_links as orul')
                            ->whereRaw('o.id = orul.ordenes_retiro_id')
                            ->where('orul.user_id', '=', $id);
                    })
                    ->select('o.*');

                // ! Ordenamiento ********************************** 
                $orderByText = null;
                $orderByDate = null;
                $sort = $data['sort'];
                $sortParts = explode(':', $sort);

                $pt1 = $sortParts[0];

                $type = (stripos($pt1, 'fecha') !== false || stripos($pt1, 'marca') !== false) ? 'date' : 'text';

                $dataSort = [
                    [
                        'field' => $sortParts[0],
                        'type' => $type,
                        'direction' => $sortParts[1],
                    ],
                ];

                foreach ($dataSort as $value) {
                    $field = $value['field'];
                    $direction = $value['direction'];
                    $type = $value['type'];

                    if ($type === "text") {
                        $orderByText = [$field => $direction];
                    } else {
                        $orderByDate = [$field => $direction];
                    }
                }

                if ($orderByText !== null) {
                    $retiros->orderBy(key($orderByText), reset($orderByText));
                } else {
                    $retiros->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
                }
                // ! **************************************************
                $pedidos = $retiros->paginate($pageSize, ['*'], 'page', $pageNumber);

                return response()->json($pedidos);
            } else {
                return response()->json(['message' => 'No se encontro el user'], 404);
            }
        }
    }
    public function getOrdenesRetiroCount($id)
    {
        if ($id == 0) {
            $pedidos['total_retiros'] = "0.00";
        }

        $ordenes = DB::table('ordenes_retiros as o')
            ->join('ordenes_retiros_users_permissions_user_links as oul', 'o.id', '=', 'oul.ordenes_retiro_id')
            ->where('oul.user_id', $id)
            ->where('o.estado', 'REALIZADO')
            ->select('o.*');

        $total_retiros = $ordenes->sum('o.monto');

        $pedidos['total_retiros'] = number_format($total_retiros, 2, '.', '');

        return response()->json($pedidos);
    }
}
