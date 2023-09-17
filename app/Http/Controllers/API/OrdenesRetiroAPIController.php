<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UpUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenesRetiroAPIController extends Controller
{

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

}
