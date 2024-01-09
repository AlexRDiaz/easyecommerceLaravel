<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PedidoFecha;
use App\Models\pedidos_shopifies;
use App\Models\PedidosShopifiesPedidoFechaLink;
use App\Models\PedidosShopifiesRutaLink;
use App\Models\PedidosShopifiesSubRutaLink;
use App\Models\PedidosShopifiesOperadoreLink;
use App\Models\PedidosShopifiesTransportadoraLink;
use App\Models\PedidosShopify;
use App\Models\ProductoShopifiesPedidosShopifyLink;
use App\Models\Ruta;
use App\Models\TransaccionPedidoTransportadora;
use App\Models\TransportStats;
use App\Models\UpUser;
use App\Models\UpUsersPedidosShopifiesLink;
use Carbon\Carbon;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidosShopifyAPIController extends Controller
{
    public function index()
    {
        $pedidos = PedidosShopify::all();
        return response()->json($pedidos);
    }



    public function updateCampo(Request $request, $id)
    {
        // Recuperar los datos del formulario
        $data = $request->all();

        // Encuentra el registro en base al ID
        $pedido = PedidosShopify::findOrFail($id);

        // Actualiza los campos específicos en base a los datos del formulario
        $pedido->fill($data);
        $pedido->save();

        // Respuesta de éxito
        return response()->json(['message' => 'Registro actualizado con éxito', "res" => $pedido], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        // Recuperar los datos del formulario

        // Recuperar el nuevo estado del pedido
        $data = $request->all();
        $newStatus = $data['status'];

        // Encuentra el registro en base al ID
        $pedido = PedidosShopify::findOrFail($id);



        // Actualiza el estado del pedido
        $pedido->status = $newStatus;

        $pedido->confirmed_at = new DateTime();
        $pedido->confirmed_by = 999;
        $pedido->save();

        $user = UpUser::findOrFail($pedido->id_comercial);
        $config_autome = $user->config_autome;
        $configs = json_decode($config_autome, true);
        $pedidoRuta = new PedidosShopifiesRutaLink();
        $pedidoRuta->pedidos_shopify_id = $pedido->id;
        $pedidoRuta->ruta_id = $configs["ruta"];
        $pedidoRuta->save();

        $pedidoTransportadora = new PedidosShopifiesTransportadoraLink();
        $pedidoTransportadora->pedidos_shopify_id = $pedido->id;
        $pedidoTransportadora->transportadora_id = $configs["transportadora"];
        $pedidoTransportadora->save();


        // Respuesta de éxito
        return response()->json(['message' => 'Registro actualizado con éxito', 'id' => $pedido->id, 'status' => $pedido->status, "config autome" => $configs], 200);
    }

    public function show($id)
    {
        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta', "statusLastModifiedBy"])
            ->findOrFail($id);

        return response()->json($pedido);
    }


    public function getDevolucionesOperator(Request $request)
    {
        $data = $request->json()->all();
        $Map = $data['and'];
        $not = $data['not'];
        $searchTerm = $data['search'];
        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $orConditions = $data['multifilter'];

        if ($searchTerm != "") {
            $filteFields = $data['or']; // && SOLO QUITO  ((||)&&())
        } else {
            $filteFields = [];
        }

        $pedidos = PedidosShopify::with(['operadore.up_users'])
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->orWhere(function ($pedidos) use ($orConditions) {
                //condiciones multifilter
                foreach ($orConditions as $condition) {
                    $pedidos->orWhere(function ($subquery) use ($condition) {
                        foreach ($condition as $field => $value) {
                            $subquery->orWhere($field, $value);
                        }
                    });
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($pedidos);
    }

    public function getByDateRangeLogistic(Request $request)
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
            $filteFields = $data['or']; // && SOLO QUITO  ((||)&&())
        } else {
            $filteFields = [];
        }

        // ! *************************************
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************
        // ! ordenamiento ↓
        $orderBy = null;
        if (isset($data['sort'])) {
            $sort = $data['sort'];
            $sortParts = explode(':', $sort);
            if (count($sortParts) === 2) {
                $field = $sortParts[0];
                $direction = strtoupper($sortParts[1]) === 'DESC' ? 'DESC' : 'ASC';
                $orderBy = [$field => $direction];
            }
        }

        // ! *************************************

        $pedidos = PedidosShopify::with(['operadore.up_users'])
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->with('statusLastModifiedBy')
            ->whereRaw("STR_TO_DATE(marca_t_i, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
                    foreach ($condition as $key => $valor) {
                        $parts = explode("/", $key);
                        $type = $parts[0];
                        $filter = $parts[1];
                        if (strpos($filter, '.') !== false) {
                            $relacion = substr($filter, 0, strpos($filter, '.'));
                            $propiedad = substr($filter, strpos($filter, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            if ($type == "equals") {
                                $pedidos->where($filter, '=', $valor);
                            } else {
                                $pedidos->where($filter, 'LIKE', '%' . $valor . '%');
                            }
                        }
                    }
                }
            }))->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
        // ! Ordena
        if ($orderBy !== null) {
            $pedidos->orderBy(key($orderBy), reset($orderBy));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($pedidos);
    }

    public function getByDateRangeLogisticNovelties(Request $request)
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
            $filteFields = $data['or']; // && SOLO QUITO  ((||)&&())
        } else {
            $filteFields = [];
        }

        // ! *************************************
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************
        // ! ordenamiento ↓
        $orderBy = null;
        if (isset($data['sort'])) {
            $sort = $data['sort'];
            $sortParts = explode(':', $sort);
            if (count($sortParts) === 2) {
                $field = $sortParts[0];
                $direction = strtoupper($sortParts[1]) === 'DESC' ? 'DESC' : 'ASC';
                $orderBy = [$field => $direction];
            }
        }

        // ! *************************************

        $pedidos = PedidosShopify::with(['operadore.up_users'])
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->with('confirmedBy')
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
                    foreach ($condition as $key => $valor) {
                        $parts = explode("/", $key);
                        $type = $parts[0];
                        $filter = $parts[1];
                        if (strpos($filter, '.') !== false) {
                            $relacion = substr($filter, 0, strpos($filter, '.'));
                            $propiedad = substr($filter, strpos($filter, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            if ($type == "equals") {
                                $pedidos->where($filter, '=', $valor);
                            } else {
                                $pedidos->where($filter, 'LIKE', '%' . $valor . '%');
                            }
                        }
                    }
                }
            }))->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
        // ! Ordena
        if ($orderBy !== null) {
            $pedidos->orderBy(key($orderBy), reset($orderBy));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($pedidos);
    }
    // ! for generate pdfs without pagination 
    public function getByDateRangeOrdersforAudit(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');

        $searchTerm = $data['search'];

        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        $Map = $data['and'];
        $not = $data['not'];

        $orderBy = null;
        if (isset($data['sort'])) {
            $sort = $data['sort'];
            $sortParts = explode(':', $sort);
            if (count($sortParts) === 2) {
                $field = $sortParts[0];
                $direction = strtoupper($sortParts[1]) === 'DESC' ? 'DESC' : 'ASC';
                $orderBy = [$field => $direction];
            }
        }

        $pedidos = PedidosShopify::with(['operadore.up_users'])
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->with('confirmedBy')
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where(function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
                    foreach ($condition as $key => $valor) {
                        $this->applyCondition($pedidos, $key, $valor);
                    }
                }
            })
            ->where(function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        $this->applyCondition($pedidos, $key, $valor, '!=');
                    }
                }
            });

        if ($orderBy !== null) {
            $pedidos->orderBy(key($orderBy), reset($orderBy));
        }

        $pedidos = $pedidos->get();
        // // Antes de devolver la respuesta, carga los nombres de los usuarios correspondientes a 'order_by'
        // $pedidos->each(function ($pedido) {
        //     if ($pedido->confirmedBy) {
        //         $pedido->confirmed_by_user = $pedido->confirmedBy->username; // Ajusta según tu estructura real
        //     }
        // });

        return response()->json([
            'data' => $pedidos,
            'total' => $pedidos->count(),
        ]);
    }

    private function applyCondition($pedidos, $key, $valor, $operator = '=')
    {
        $parts = explode("/", $key);
        $type = $parts[0];
        $filter = $parts[1];

        if (strpos($filter, '.') !== false) {
            $relacion = substr($filter, 0, strpos($filter, '.'));
            $propiedad = substr($filter, strpos($filter, '.') + 1);
            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
        } else {
            if ($type == "equals") {
                $pedidos->where($filter, $operator, $valor);
            } else {
                $pedidos->where($filter, 'LIKE', '%' . $valor . '%');
            }
        }
    }

    // ! *********************************
    public function updateOrderStatusAndComment(Request $req)
    {
        $data = $req->json()->all();
        $iddepedido = $data['iddepedido'];
        $status = $data['status'];
        $comentario = $data['comentario'];

        $pedido = PedidosShopify::where('id', $iddepedido)->first();

        if (!$pedido) {
            return response()->json(['message' => 'No se encontró pedido con el ID especificado'], 404);
        }

        $pedido->status = $status;
        $pedido->comentario = $comentario;
        $pedido->save();

        return response()->json($pedido);
    }



    public function updateOrderInteralStatusLogisticLaravel(Request $req)
    {
        $data = $req->json()->all();
        $id = $data['id'];
        $estado_interno = $data['data'][0]['estado_interno'];
        $estado_logistico = $data['data'][1]['estado_logistico'];
        $pedido = PedidosShopify::with(['transportadora', 'users', 'users.vendedores', 'pedidoFecha', 'ruta'])
            ->where('id', $id)
            ->first();
        if (!$pedido) {
            return response()->json(['message' => 'No se encontraro pedido con el ID especificado'], 404);
        }
        $pedido->estado_interno = $estado_interno;
        $pedido->estado_logistico = $estado_logistico;
        $pedido->save();
        return response()->json($pedido);
    }
    public function updateOrderLogisticStatusPrintLaravel(Request $req)
    {
        $data = $req->json()->all();
        $id = $data['id'];
        $estado_interno = $data['data'][0]['estado_interno'];
        $estado_logistico = $data['data'][1]['estado_logistico'];
        $fecha_entega = $data['data'][2]['fecha_entrega'];
        $marca_tiempo_envio = $data['data'][3]['marca_tiempo_envio'];
        $pedido = PedidosShopify::with(['transportadora', 'users', 'users.vendedores', 'pedidoFecha', 'ruta'])
            ->where('id', $id)
            ->first();
        if (!$pedido) {
            return response()->json(['message' => 'No se encontraro pedido con el ID especificado'], 404);
        }
        $pedido->estado_interno = $estado_interno;
        $pedido->estado_logistico = $estado_logistico;
        $pedido->fecha_entega = $fecha_entega;
        $pedido->marca_tiempo_envio = $marca_tiempo_envio;
        $pedido->save();
        return response()->json($pedido);
    }

    public function getOrdersForPrintedGuidesLaravel(Request $request)
    {
        $data = $request->json()->all();
        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];
        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }
        // ! *************************************
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************

        $pedidos = PedidosShopify::with(['transportadora', 'users', 'users.vendedores', 'pedidoFecha', 'ruta', 'printedBy', 'sentBy', 'product.warehouse.provider'])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))
            ->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
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
            $pedidos->orderBy(key($orderByText), reset($orderByText));
        } else {
            $pedidos->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($pedidos);
    }

    public function getOrderByIDLaravel(Request $req)
    {
        $data = $req->json()->all();
        $id = $data['id'];
        $populate = $data['populate'];
        $pedido = PedidosShopify::with($populate)
            ->where('id', $id)
            ->first();
        if (!$pedido) {
            return response()->json(['message' => 'No se encontraro pedido con el ID especificado'], 404);
        }
        return response()->json($pedido);
    }

    // --------------------------------
    public function getPrincipalOrdersSellersFilterLaravel(Request $request)
    {
        $data = $request->json()->all();
        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];
        if ($searchTerm != "") {
            $filteFields = $data['or']; // && SOLO QUITO  ((||)&&())
        } else {
            $filteFields = [];
        }
        // ! *************************************
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************
        // 'users',

        $pedidos = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            // ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))
            ->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
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
            $pedidos->orderBy(key($orderByText), reset($orderByText));
        } else {
            $pedidos->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($pedidos);
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
            $filteFields = $data['or']; // && SOLO QUITO  ((||)&&())
        } else {
            $filteFields = [];
        }

        // ! *************************************
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************

        $pedidos = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
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
            $pedidos->orderBy(key($orderByText), reset($orderByText));
        } else {
            $pedidos->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($pedidos);
    }

    public function getOrderbyId(Request $req, $id)
    {
        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->where('id', $id)
            ->first();
        if (!$pedido) {
            return response()->json(['message' => 'No se encontraro pedido con el ID especificado'], 404);
        }
        // return response()->json(['data' => $pedido]);
        return response()->json($pedido);
    }
    //  TODO: en desarrollo ↓↓↓↓
    public function createDateOrderLaravel(Request $req)
    {
        $data = $req->json()->all();
        $fechaActual = $data['fecha'];
        $pedidoFecha = PedidoFecha::where('fecha', $fechaActual)->first();
        $newpedidoFecha = "";
        if (!$pedidoFecha) {
            $newpedidoFecha = new PedidoFecha();
            $newpedidoFecha->fecha = $fechaActual;
            $newpedidoFecha->save();
            return response()->json($newpedidoFecha);
        }
        return response()->json($pedidoFecha);
    }
    public function postOrdersPricipalOrders(Request $req)
    {
        // all data
        $data = $req->json()->all();

        $NumeroOrden = $data['NumeroOrden'];
        $NombreShipping = $data['NombreShipping'];
        $DireccionShipping = $data['DireccionShipping'];
        $TelefonoShipping = $data['TelefonoShipping'];

        $PrecioTotal = $data['PrecioTotal'];
        $formattedPrice = str_replace(",", ".", str_replace(["$", " "], "", $PrecioTotal));

        if ($data['Observacion'] != null) {
            $Observacion = $data['Observacion'];
        } else {
            $Observacion = "";
        }
        $CiudadShipping = $data['CiudadShipping'];
        $ProductoP = $data['ProductoP'];

        $users = $data['users'];
        $Estado_Interno = $data['Estado_Interno'];
        $IdComercial = $data['IdComercial'];
        $ProductoExtra = $data['ProductoExtra'];
        $Cantidad_Total = $data['Cantidad_Total'];
        $Name_Comercial = $data['Name_Comercial'];
        $Marca_T_I = $data['Marca_T_I'];
        $Fecha_Confirmacion = $data['Fecha_Confirmacion'];
        $Tienda_Temporal = $data['Tienda_Temporal'];
        $pedido_fecha = $data['pedido_fecha'];

        //  * Crear una instancia del modelo PedidosShopify y asignar los valores
        $pedido = new PedidosShopify();
        $pedido->numero_orden = $NumeroOrden;
        $pedido->direccion_shipping = $DireccionShipping;
        $pedido->nombre_shipping = $NombreShipping;
        $pedido->telefono_shipping = $TelefonoShipping;
        $pedido->precio_total = $formattedPrice;
        $pedido->observacion = $Observacion;
        $pedido->ciudad_shipping = $CiudadShipping;
        $pedido->estado_interno = $Estado_Interno;
        $pedido->id_comercial = $IdComercial;
        $pedido->producto_p = $ProductoP;
        $pedido->producto_extra = $ProductoExtra;
        $pedido->cantidad_total = $Cantidad_Total;
        $pedido->name_comercial = $Name_Comercial;
        $pedido->marca_t_i = $Marca_T_I;
        $pedido->fecha_confirmacion = $Fecha_Confirmacion;
        $pedido->tienda_temporal = $Tienda_Temporal;
        $pedido->save();

        $ultimoPedidoFechaLink = PedidosShopifiesPedidoFechaLink::where('pedido_fecha_id', $pedido_fecha)
            ->orderBy('pedidos_shopify_order', 'desc')
            ->first();

        if ($ultimoPedidoFechaLink) {
            $pedidoShopifyOrder = $ultimoPedidoFechaLink->pedidos_shopify_order + 1;
        } else {
            $pedidoShopifyOrder = 1;
        }

        $pedido->pedidos_shopifies_pedido_fecha_links()->create([
            'pedido_fecha_id' => $pedido_fecha,
            'pedidos_shopify_id' => $pedido->id,
            'pedidos_shopify_order' => $pedidoShopifyOrder
        ]);
        $pedido->up_users_pedidos_shopifies_links()->create([
            "user_id" => $users,
            "pedidos_shopify_id" => $pedido->id,
            'pedidos_shopify_order' => $pedidoShopifyOrder,
            "user_order" => "1"
        ]);
        return response()->json(['message' => 'Pedido creado exitosamente'], 201);
    }
    public function updateOrderInfoSellerLaravel(Request $req)
    {
        $data = $req->json()->all();
        $id = $data['id'];
        $ciudad_shipping = $data["ciudad_shipping"];
        $nombre_shipping = $data["nombre_shipping"];
        $direccion_shipping = $data["direccion_shipping"];
        $telefono_shipping = $data["telefono_shipping"];
        $cantidad_total = $data["cantidad_total"];
        $producto_p = $data["producto_p"];
        $producto_extra = $data["producto_extra"];
        $precio_total = $data["precio_total"];
        $observacion = $data["observacion"];

        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->where('id', $id)
            ->first();

        if (!$pedido) {
            return response()->json(['message' => 'No se pudo actualizar el pedido con el ID especificado'], 404);
        }

        $pedido->ciudad_shipping = $ciudad_shipping;
        $pedido->nombre_shipping = $nombre_shipping;
        $pedido->direccion_shipping = $direccion_shipping;
        $pedido->telefono_shipping = $telefono_shipping;
        $pedido->cantidad_total = $cantidad_total;
        $pedido->producto_p = $producto_p;
        $pedido->producto_extra = $producto_extra;
        $pedido->precio_total = $precio_total;
        $pedido->observacion = $observacion;
        $pedido->save();

        return response()->json($pedido);
    }
    public function updateOrderInternalStatus(Request $req)
    {
        $data = $req->json()->all();
        $id = $data['id'];
        $estadoInterno = $data['estado_interno'];
        $fechaConfirmacion = $data['fecha_confirmacion'];
        $nameComercial = $data['name_comercial'];
        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->where('id', $id)
            ->first();

        if (!$pedido) {
            return response()->json(['message' => 'No se pudo actualizar pedido con el ID especificado'], 404);
        }

        $pedido->fecha_confirmacion = $fechaConfirmacion;
        $pedido->estado_interno = $estadoInterno;
        $pedido->name_comercial = $nameComercial;
        $pedido->save();

        return response()->json($pedido);
    }
    public function updateDateandStatus(Request $req)
    {
        $data = $req->json()->all();
        $id = $data['id'];
        $fecha_entrega = $data['data'][0]['fecha_Entrega']; // Accede al valor de fecha_Entrega
        $status = $data['data'][1]['status']; // Accede al valor de status

        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->where('id', $id)
            ->first();

        if (!$pedido) {
            return response()->json(['message' => 'No se encontraro pedido con el ID especificado'], 404);
        }

        $pedido->fecha_entrega = $fecha_entrega;
        $pedido->status = $status;
        $pedido->save();

        // return response()->json(['data' => $pedido]);
        return response()->json($pedido);
    }

    public function getReturnSellers(Request $request)
    {
        $data = $request->json()->all();
        // $startDate = $data['start'];
        // $endDate = $data['end'];
        // $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        // $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];

        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        // ! *************************************
        $orConditions = $data['ordefault'];
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************
        // ! ordenamiento ↓

        // ! *************************************

        $pedidos = PedidosShopify::with(['operadore.up_users'])
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->with('receivedBy')
            ->orWhere(function ($query) use ($orConditions) {
                foreach ($orConditions as $condition) {
                    $query->orWhere(function ($subquery) use ($condition) {
                        foreach ($condition as $field => $value) {
                            $subquery->orWhere($field, $value);
                        }
                    });
                }
            })
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            //->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
        // ! Ordena
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
            $pedidos->orderBy(key($orderByText), reset($orderByText));
        } else {
            $pedidos->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);
        return response()->json($pedidos);
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
    private function recursiveWhere($query, $key, $property, $valor)
    {
        if ($valor == "null") {
            $valor = null;
        }
        if (strpos($property, '.') !== false) {
            $nestedRelation = substr($property, 0, strpos($property, '.'));
            $nestedProperty = substr($property, strpos($property, '.') + 1);

            $query->whereHas($key, function ($query) use ($nestedRelation, $nestedProperty, $valor) {
                $this->recursiveWhereHas($query, $nestedRelation, $nestedProperty, $valor);
            });
        } else {
            $query->where($key, '=', $valor);
        }
    }


    public function store(Request $request)
    {
        $pedido = PedidosShopify::create($request->all());
        return response()->json($pedido, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $pedido = PedidosShopify::findOrFail($id);
        $pedido->update($request->all());
        return response()->json($pedido, Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $pedido = PedidosShopify::findOrFail($id);
        $pedido->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getCountersLogistic(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');
        $Map = $data['and'];
        $not = $data['not'];

        $result = PedidosShopify::with(['operadore.up_users'])

            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->whereRaw("STR_TO_DATE(marca_t_i, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))->get();


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
        $counter = 0;
        foreach ($result as $row) {
            $counter++;
            $estado = $row->status;
            $stateTotals[$estado] = $row->count;
            $stateTotals['TOTAL'] += $row->count;
        }

        return response()->json([
            'data' => $stateTotals,
        ]);
    }

    public function getCounters(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');
        $Map = $data['and'];
        $not = $data['not'];

        $result = PedidosShopify::with(['operadore.up_users'])
            ->with('transportadora')
            ->with('users.vendedores')
            ->with('novedades')
            ->with('pedidoFecha')
            ->with('ruta')
            ->with('subRuta')
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))->where((function ($pedidos) use ($not) {

                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }
            ))->get();





        $stateTotals = [
            'ENTREGADO' => 0,
            'NO ENTREGADO' => 0,
            'NOVEDAD' => 0,
            'NOVEDAD RESUELTA' => 0,
            'REAGENDADO' => 0,
            'EN RUTA' => 0,
            'EN OFICINA' => 0,
            'PEDIDO PROGRAMADO' => 0,
            'TOTAL' => 0
        ];
        $counter = 0;
        foreach ($result as $row) {
            $counter++;
            $estado = $row->status;
            $stateTotals[$estado] = $row->count;
            $stateTotals['TOTAL'] += $row->count;
        }

        return response()->json([
            'data' => $stateTotals,
        ]);
    }

    public function getProductsDashboardRoutesCount(Request $request)
    {
        $data = $request->json()->all();
        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');
        $Map = $data['and'];

        $searchTerm = $data['search'];
        if ($searchTerm != "") {
            $filteFields = $data['or'];
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        $routeId = $data['route_id'];
        $pedidos = PedidosShopify::with([
            'operadore.up_users:id',
            'transportadora',
            'pedidoFecha',
            'ruta',
            'subRuta'
        ])

            ->whereRaw("STR_TO_DATE(marca_t_i, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
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
            }))
            ->whereHas('ruta', function ($query) use ($routeId) {
                $query->where('rutas.id', $routeId); // Califica 'id' con 'rutas'
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();


        return response()->json([
            'data' => $pedidos
        ]);
    }
    public function CalculateValuesTransport(Request $request)
    {
        $data = $request->json()->all();
        $startDate = Carbon::createFromFormat('j/n/Y', $data['start'])->format('Y-m-d');
        $endDate = Carbon::createFromFormat('j/n/Y', $data['end'])->format('Y-m-d');
        $Map = $data['and'];
        $not = $data['not'];

        $query = PedidosShopify::query()
            ->with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDate, $endDate]);

        $this->applyConditions($query, $Map);
        $this->applyConditions($query, $not, true);


        $query1 = clone $query;
        $query2 = clone $query;
        $summary = [
            'totalValoresRecibidos' => $query1->whereIn('status', ['ENTREGADO'])->sum(DB::raw('REPLACE(precio_total, ",", "")')),

            //  este sirve para costo envio
            // 'totalShippingCost' => $query
            // ->whereIn('status', ['ENTREGADO', 'NO ENTREGADO'])
            // ->join('up_users_pedidos_shopifies_links', 'pedidos_shopifies.id', '=', 'up_users_pedidos_shopifies_links.pedidos_shopify_id')
            // ->join('up_users', 'up_users_pedidos_shopifies_links.user_id', '=', 'up_users.id')
            // ->join('up_users_vendedores_links', 'up_users.id', '=', 'up_users_vendedores_links.user_id')
            // ->join('vendedores', 'up_users_vendedores_links.vendedor_id', '=', 'vendedores.id')->get()
            //  ->sum(DB::raw('REPLACE(vendedores.costo_envio, ",", "")'))
            'totalShippingCost' => $query2
                ->whereIn('status', ['ENTREGADO', 'NO ENTREGADO'])
                ->join('pedidos_shopifies_transportadora_links', 'pedidos_shopifies.id', '=', 'pedidos_shopifies_transportadora_links.pedidos_shopify_id')
                ->join('transportadoras', 'pedidos_shopifies_transportadora_links.transportadora_id', '=', 'transportadoras.id')
                ->sum(DB::raw('REPLACE(transportadoras.costo_transportadora, ",", "")'))
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function CalculateValuesSeller(Request $request)
    {
        $data = $request->json()->all();
        $startDate = Carbon::createFromFormat('j/n/Y', $data['start'])->format('Y-m-d');
        $endDate = Carbon::createFromFormat('j/n/Y', $data['end'])->format('Y-m-d');
        $Map = $data['and'];
        $not = $data['not'];

        $query = PedidosShopify::query()
            ->with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDate, $endDate]);

        $this->applyConditions($query, $Map);
        $this->applyConditions($query, $not, true);
        $query1 = clone $query;
        $query2 = clone $query;
        $query3 = clone $query;
        $summary = [
            'totalValoresRecibidos' => $query1->whereIn('status', ['ENTREGADO'])->sum(DB::raw('REPLACE(precio_total, ",", "")')),

            'totalShippingCost' => $query2
                ->whereIn('status', ['ENTREGADO', 'NO ENTREGADO'])
                ->join('up_users_pedidos_shopifies_links', 'pedidos_shopifies.id', '=', 'up_users_pedidos_shopifies_links.pedidos_shopify_id')
                ->join('up_users', 'up_users_pedidos_shopifies_links.user_id', '=', 'up_users.id')
                ->join('up_users_vendedores_links', 'up_users.id', '=', 'up_users_vendedores_links.user_id')
                ->join('vendedores', 'up_users_vendedores_links.vendedor_id', '=', 'vendedores.id')
                ->sum(DB::raw('REPLACE(vendedores.costo_envio, ",", "")')),

            'totalCostoDevolucion' => $query3
                ->whereIn('status', ['NOVEDAD'])
                ->whereNotIn('estado_devolucion', ['PENDIENTE'])
                ->join('up_users_pedidos_shopifies_links', 'pedidos_shopifies.id', '=', 'up_users_pedidos_shopifies_links.pedidos_shopify_id')
                ->join('up_users', 'up_users_pedidos_shopifies_links.user_id', '=', 'up_users.id')
                ->join('up_users_vendedores_links', 'up_users.id', '=', 'up_users_vendedores_links.user_id')
                ->join('vendedores', 'up_users_vendedores_links.vendedor_id', '=', 'vendedores.id')
                ->sum(DB::raw('REPLACE(vendedores.costo_devolucion, ",", "")')),

        ];

        return response()->json([
            'data' => $summary,
        ]);
    }




    private function applyConditions($query, $conditions, $not = false)
    {
        $operator = $not ? '!=' : '=';

        foreach ($conditions as $condition) {
            foreach ($condition as $key => $value) {
                if (strpos($key, '.') !== false) {
                    [$relation, $property] = explode('.', $key);
                    $query->whereHas($relation, function ($subQuery) use ($property, $value, $operator) {
                        $subQuery->where($property, $operator, $value);
                    });
                } else {
                    $query->where($key, $operator, $value);
                }
            }
        }
    }


    public function shopifyPedidos(Request $request, $id)
    {
        //GENERATE DATE
        $currentDate = now();
        $fechaActual = $currentDate->format('d/m/Y');

        // ID DATE ORDER FOR RELATION
        $dateOrder = "";

        //VARIABLES FOR ENTITY
        $listOfProducts = [];
        $order_number = $request->input('order_number');

        $name = $request->input('shipping_address.name');
        $address1 = $request->input('shipping_address.address1');
        $phone = $request->input('shipping_address.phone');
        $total_price = $request->input('total_price');
        $customer_note = $request->input('customer_note');
        $city = $request->input('shipping_address.city');
        $productos = $request->input('line_items');

        //ADD PRODUCT TO LIST FOR NEW OBJECT

        error_log("******************proceso 1 terminado************************\n");
        foreach ($productos as $element) {

            $listOfProducts[] = [
                'id' => $element['id'],
                'name' => $element['product_id'],
                'quantity' => $element['quantity'],
                'price' => $element['price'],
                'title' => $element['title'],
                'variant_title' => $element['variant_title'],
                'sku' => $element['sku']

            ];
        }

        $search = PedidosShopify::where([
            'numero_orden' => $order_number,
            // 'tienda_temporal' => $productos[0]['vendor'],
            'id_comercial' => $id,
        ])->get();

        //
        // IF ORDER NOT EXIST CREATE ORDER
        if ($search->isEmpty()) {
            $dateOrder;
            // SEARCH DATE ORDER FOR RELLATION
            $searchDate = PedidoFecha::where('fecha', $fechaActual)->get();

            // IF DATE ORDER NOT EXIST CREATE ORDER AND ADD ID ELSE IF ONLY ADD DATE ORDER ID VALUE
            if ($searchDate->isEmpty()) {
                // Crea un nuevo registro de fecha
                $newDate = new PedidoFecha();
                $newDate->fecha = $fechaActual;
                $newDate->save();

                // Obtén el ID del nuevo registro
                $dateOrder = $newDate->id;
            } else {
                // Si la fecha existe, obtén el ID del primer resultado
                $dateOrder = $searchDate[0]->id;
            }


            // Obtener la fecha y hora actual
            $ahora = now();
            $dia = $ahora->day;
            $mes = $ahora->month;
            $anio = $ahora->year;
            $hora = $ahora->hour;
            $minuto = $ahora->minute;

            // Formatear la fecha y hora actual
            $fechaHoraActual = "$dia/$mes/$anio $hora:$minuto";

            // Crear una nueva orden
            $formattedPrice = str_replace(["$", ",", " "], "", $total_price);


            $sku = $productos[0]['sku'];
            $lastIdProduct = 0;

            // if ($sku != null) {
            //     $parts = explode('C', $sku);
            //     $id_product = end($parts);
            //     if (is_numeric($id_product)) {
            //         $lastIdProduct = $id_product;
            //     }
            // }
            error_log("******************proceso 2 terminado************************\n");
            error_log("******************numero de orden: . $order_number. ************************\n");
            $createOrder = new PedidosShopify([
                'marca_t_i' => $fechaHoraActual,
                'tienda_temporal' =>$productos[0]['vendor'],
                'numero_orden' => $order_number,
                'direccion_shipping' => $address1,
                'nombre_shipping' => $name,
                'telefono_shipping' => $phone,
                'precio_total' => $formattedPrice,
                'observacion' =>"",
                // "variants: " . implode(', ', array_column(array_slice($listOfProducts, 0), 'variant_title')),
                'ciudad_shipping' => $city,
                'sku' =>"",
                // $sku,
                'id_product' => 0,
                'id_comercial' => $id,
                'producto_p' => "",
                //$listOfProducts[0]['title'],
                'producto_extra' => "",
                //implode(', ', array_column(array_slice($listOfProducts, 1), 'title')),
                'cantidad_total' => $listOfProducts[0]['quantity'],
                'estado_interno' => "PENDIENTE",
                'status' => "PEDIDO PROGRAMADO",
                'estado_logistico' => 'PENDIENTE',
                'estado_pagado' => 'PENDIENTE',
                'estado_pago_logistica' => 'PENDIENTE',
                'estado_devolucion' => 'PENDIENTE',
                'do' => 'PENDIENTE',
                'dt' => 'PENDIENTE',
                'dl' => 'PENDIENTE'

            ]);

            $createOrder->save();
            error_log("******************proceso 3 terminado************************\n");


            $createPedidoFecha = new PedidosShopifiesPedidoFechaLink();
            $createPedidoFecha->pedidos_shopify_id = $createOrder->id;
            $createPedidoFecha->pedido_fecha_id = $dateOrder;
            $createPedidoFecha->save();

            $createUserPedido = new UpUsersPedidosShopifiesLink();
            $createUserPedido->user_id = $id;
            $createUserPedido->pedidos_shopify_id = $createOrder->id;
            $createUserPedido->save();
            $user = UpUser::with([
                'vendedores',
            ])->find($id);
            error_log("******************proceso 4 terminado************************\n");

            if ($user->enable_autome) {
                if ($user->webhook_autome != null) {

                    $client = new Client();

                    $response = $client->post($user->webhook_autome, [
                        'json' => [
                            "id" => $createOrder->id,
                            "line_item_shopify_id" => $listOfProducts[0]['id'],
                            "line_item_shopify_product_id" => $listOfProducts[0]['product_id'],
                            "marca_t_i" => $createOrder->marca_t_i,
                            "tienda_temporal" => $createOrder->tienda_temporal,
                            "numero_orden" => $createOrder->numero_orden,
                            "direccion_shipping" => $createOrder->direccion_shipping,
                            "nombre_shipping" => $createOrder->nombre_shipping,
                            "telefono_shipping" => $createOrder->telefono_shipping,
                            "precio_total" => $createOrder->precio_total,
                            "observacion" => $createOrder->observacion,
                            "ciudad_shipping" => $createOrder->ciudad_shipping,
                            "id_comercial" => $createOrder->id_comercial,
                            "producto_p" => $createOrder->producto_p,
                            "producto_extra" => $createOrder->producto_extra,
                            "cantidad_total" => $createOrder->cantidad_total,
                            "status" => $createOrder->status
                        ]
                    ]);
                }
            }
            error_log("******************proceso 5 terminado************************\n");

            return response()->json([
                'message' => 'La orden se ha registrado con éxito.',
                'orden_ingresada' => $createOrder,
                'search' => 'MANDE',
               // 'and' => [],
              //  'id_product' => $id_product
            ], 200);
        } else {
            return response()->json([
                'error' => 'Esta orden ya existe',
                'orden_a_ingresar' => [
                    'numero_orden' => $order_number,
                    'nombre' => $name,
                    'direccion' => $address1,
                    'telefono' => $phone,
                    'precio_total' => $total_price,
                    'nota_cliente' => $customer_note,
                    'ciudad' => $city,
                    'producto' => $listOfProducts
                ],
                'orden_existente' => $search,
            ], 200);
        }
    }
    public function sendToAutome($url, $data)
    {
        $client = new Client();
        $response = $client->post($url, [
            'data' => [
                "id" => $data->id,
                "marca_t_i" => $data->marca_t_i,
                "tienda_temporal" => $data->tienda_temporal,
                "numero_orden" => $data->numero_orden,
                "direccion_shipping" => $data->direccion_shipping,
                "nombre_shipping" => $data->nombre_shipping,
                "telefono_shipping" => $data->telefono_shipping,
                "precio_total" => $data->precio_total,
                "observacion" => $data->observacion,
                "ciudad_shipping" => $data->ciudad_shipping,
                "id_comercial" => $data->id_comercial,
                "producto_p" => $data->id_comercial,
                "producto_extra" => $data->id_comercial,
                "cantidad_total" => $data->id_comercial,
                "status" => $data->id_comercial,
            ]
        ]);

        return response()->json_decode($response->getBody()->getContents());
    }


    public function testChatby(Request $request)
    {
        $data = $request->json()->all();
        return $data;
    }





    public function getOrdersForPrintGuidesInSendGuidesPrincipalLaravel(Request $request)
    {
        $data = $request->json()->all();
        // $startDate = $data['start'];
        // $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');

        $populate = $data['populate'];

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];

        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        // ! *************************************
        $Map = $data['and'];
        $not = $data['not'];
        // ! *************************************

        $pedidos = PedidosShopify::with($populate)
            // ->whereRaw("STR_TO_DATE(marca_tiempo_envio, '%e/%c/%Y') = ?", [$startDateFormatted])
            ->where(function ($pedidos) use ($searchTerm, $filteFields) {
                foreach ($filteFields as $field) {
                    if (strpos($field, '.') !== false) {
                        $relacion = substr($field, 0, strpos($field, '.'));
                        $propiedad = substr($field, strpos($field, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $searchTerm);
                    } else {
                        $pedidos->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            })
            ->where((function ($pedidos) use ($Map) {
                foreach ($Map as $condition) {
                    foreach ($condition as $key => $valor) {
                        $parts = explode("/", $key);
                        $type = $parts[0];
                        $filter = $parts[1];
                        if ($key === '/marca_tiempo_envio') {
                            $startDateFormatted = Carbon::createFromFormat('j/n/Y', $valor)->format('Y-m-d');
                            $pedidos->whereRaw("STR_TO_DATE(marca_tiempo_envio, '%e/%c/%Y') = ?", [$startDateFormatted]);
                        } elseif (strpos($filter, '.') !== false) {
                            $relacion = substr($filter, 0, strpos($filter, '.'));
                            $propiedad = substr($filter, strpos($filter, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            if ($type == "equals") {
                                $pedidos->where($filter, '=', $valor);
                            } else {
                                $pedidos->where($filter, 'LIKE', '%' . $valor . '%');
                            }
                        }
                    }
                }
            }))->where((function ($pedidos) use ($not) {
                foreach ($not as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                        } else {
                            $pedidos->where($key, '!=', $valor);
                        }
                    }
                }
            }));
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
            $pedidos->orderBy(key($orderByText), reset($orderByText));
        } else {
            $pedidos->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
        }
        // ! **************************************************
        $pedidos = $pedidos->paginate($pageSize, ['*'], 'page', $pageNumber);
        return response()->json($pedidos);
    }

    public function obtenerPedidosPorRuta(Request $request)
    {
        $rutaId = $request->input('ruta_id');
        $transportadoraId = $request->input('transportadora_id');

        // Obtener los pedidos con la ruta y la transportadora específica
        $pedidos = PedidosShopify::whereHas('pedidos_shopifies_ruta_links', function ($query) use ($rutaId) {
            $query->where('ruta_id', $rutaId)->where('estado_interno', 'CONFIRMADO')->where('estado_logistico', 'ENVIADO');
        })
            ->whereHas('pedidos_shopifies_transportadora_links', function ($query) use ($transportadoraId) {
                $query->where('transportadora_id', $transportadoraId);
            })
            ->get();

        // Filtrar los pedidos entregados
        $entregados = $pedidos->filter(function ($pedido) {
            return $pedido->status === 'ENTREGADO';
        });

        // Filtrar los pedidos no entregados
        $noEntregados = $pedidos->filter(function ($pedido) {
            return $pedido->status === 'NO ENTREGADO';
        });

        // Filtrar los pedidos con novedad
        $novedad = $pedidos->filter(function ($pedido) {
            return $pedido->status === 'NOVEDAD';
        });

        // Contar la cantidad de pedidos entregados, no entregados y con novedad
        $cantidadEntregados = $entregados->count();
        $cantidadNoEntregados = $noEntregados->count();
        $cantidadNovedad = $novedad->count();

        // Calcular la suma total de ambos
        $sumaTotal = $cantidadEntregados + $cantidadNoEntregados + $cantidadNovedad;

        return response()->json([
            'entregados' => $cantidadEntregados,
            'no_entregados' => $cantidadNoEntregados,
            'novedad' => $cantidadNovedad,
            'suma_total' => $sumaTotal
        ]);
    }


    // *
    public function updateOrderRouteAndTransport(Request $request, $id)
    {
        $data = $request->json()->all();

        $newrouteId = $data['ruta'];
        $newtransportadoraId = $data['transportadora'];

        $order = PedidosShopify::with(['ruta', 'transportadora'])->find($id);
        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        //44966
        //44939
        // return response()->json($order);

        $resRuta = $order->pedidos_shopifies_ruta_links;
        $resRutaNum = count($resRuta);
        // return response()->json($resRuta);
        // return response()->json(['resRuta' => $resRuta, 'num' => $resRutaNum], 200);


        if ($resRutaNum === 0) {
            $createPedidoRuta = new PedidosShopifiesRutaLink();
            $createPedidoRuta->pedidos_shopify_id = $order->id;
            $createPedidoRuta->ruta_id = $newrouteId;
            $createPedidoRuta->save();

            $createPedidoTransportadora = new PedidosShopifiesTransportadoraLink();
            $createPedidoTransportadora->pedidos_shopify_id = $order->id;
            $createPedidoTransportadora->transportadora_id = $newtransportadoraId;
            $createPedidoTransportadora->save();
            return response()->json(['orden' => 'Ruta&Transportadora asignada exitosamente'], 200);
        } else {
            $pedidoRuta = PedidosShopifiesRutaLink::where('pedidos_shopify_id', $id)->first();
            $pedidoRuta->ruta_id = $newrouteId;
            $pedidoRuta->save();

            $pedidoTrasportadora = PedidosShopifiesTransportadoraLink::where('pedidos_shopify_id', $id)->first();
            $pedidoTrasportadora->transportadora_id = $newtransportadoraId;
            $pedidoTrasportadora->save();
            return response()->json(['orden' => 'Ruta&Transportadora actualizada exitosamente'], 200);
        }
    }

    public function updateOrderSubRouteAndOperator(Request $request, $id)
    {
        $data = $request->json()->all();

        $newSubRouteId = $data['subruta'];
        $newOperatorId = $data['operador'];

        $order = PedidosShopify::with(['subRuta', 'operadore'])->find($id);
        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        // Actualizar o crear la relación con SubRuta
        $resSubRuta = $order->pedidos_shopifies_sub_ruta_links;
        if (count($resSubRuta) === 0) {
            $createPedidoSubRuta = new PedidosShopifiesSubRutaLink();
            $createPedidoSubRuta->pedidos_shopify_id = $order->id;
            $createPedidoSubRuta->sub_ruta_id = $newSubRouteId;
            $createPedidoSubRuta->save();
        } else {
            $pedidoSubRuta = PedidosShopifiesSubRutaLink::where('pedidos_shopify_id', $id)->first();
            $pedidoSubRuta->sub_ruta_id = $newSubRouteId;
            $pedidoSubRuta->save();
        }

        // Actualizar o crear la relación con Operador
        $resOperador = $order->pedidos_shopifies_operadore_links;
        if (count($resOperador) === 0) {
            $createPedidoOperador = new PedidosShopifiesOperadoreLink();
            $createPedidoOperador->pedidos_shopify_id = $order->id;
            $createPedidoOperador->operadore_id = $newOperatorId;
            $createPedidoOperador->save();
        } else {
            $pedidoOperador = PedidosShopifiesOperadoreLink::where('pedidos_shopify_id', $id)->first();
            $pedidoOperador->operadore_id = $newOperatorId;
            $pedidoOperador->save();
        }

        return response()->json(['orden' => 'SubRuta&Operador actualizados exitosamente'], 200);
    }



    //  *
    public function getByDateRangeAll(Request $request)
    {
        $data = $request->json()->all();

        $startDate = $data['start'];
        $endDate = $data['end'];
        $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');
        $and = $data['and'];

        $status = $data['status'];
        $internal = $data['internal'];

        $pedidos = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            //select('marca_t_i', 'fecha_entrega', DB::raw('concat(tienda_temporal, "-", numero_orden) as codigo'), 'nombre_shipping', 'ciudad_shipping', 'direccion_shipping', 'telefono_shipping', 'cantidad_total', 'producto_p', 'producto_extra', 'precio_total', 'comentario', 'estado_interno', 'status', 'estado_logistico', 'estado_devolucion', 'costo_envio', 'costo_devolucion')
            ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])->where((function ($pedidos) use ($and) {
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
        if (!empty($status)) {
            $pedidos->whereIn('status', $status);
        }
        if (!empty($internal)) {
            $pedidos->whereIn('estado_interno', $internal);
        }
        $pedidos->orderBy('marca_t_i', 'asc');
        $response = $pedidos->get();

        return response()->json($response);
    }
    public function generateTransportCosts()
    {

        $id = 76;
        $pedido = PedidosShopify::where('id', $id)
            ->first();

        $numero = $pedido->numero_orden;


        DB::table('test')->insert([
            'counter' => $numero,
        ]); // Supongamos que estás filtrando por una columna "id" específica
        return response()->json($pedido);
    }


    // ! Transport_Stats

    // public function generateTransportStatsTR(Request $request)
    public function generateTransportStatsTR()
    {
        // $data = $request->json()->all();

        // Obtener el mes y año actual
        $fechaMes = Carbon::now()->format('n/Y');
        // $fechaMes = "10/2023";
        // dd($fechaMes);
        // $fechaMes = $data['fecha_mes']; // Por ejemplo, '2/2023'
        $startDate = Carbon::createFromFormat('j/n/Y', '1/' . $fechaMes)->firstOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $pedidos = PedidosShopify::select('id', 'fecha_entrega', 'status', 'estado_logistico', 'estado_interno')
            ->with(['ruta', 'transportadora'])
            ->whereBetween(DB::raw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y')"), [$startDate, $endDate])
            ->where('estado_interno', 'CONFIRMADO')
            ->where('estado_logistico', 'ENVIADO')
            ->whereIn('status', ['ENTREGADO', 'NO ENTREGADO', 'NOVEDAD'])
            ->get();

        // ! DESDE AQUI EMPIEZA LO PEGADO    

        $allTransportadorasRutas = collect();
        $pedidosInfo = [];
        $entregadosCount = 0;
        $noEntregadosCount = 0;
        $novedad = 0;

        foreach ($pedidos as $pedido) {
            if ($pedido->pedidos_shopifies_transportadora_links->contains('transportadora.nombre', "")) {
                continue; // Saltar a la siguiente iteración del bucle
            }
            if ($pedido->pedidos_shopifies_ruta_links->contains('ruta.id', 1)) {
                continue; // Saltar a la siguiente iteración del bucle
            }

            $transportadorasInfo = $pedido->pedidos_shopifies_transportadora_links->map(function ($link) {
                return $link->transportadora->nombre . '-' . $link->transportadora->id;
            })->implode(', ');
            $rutasInfo = $pedido->pedidos_shopifies_ruta_links->map(function ($link) {
                return $link->ruta->titulo . '-' . $link->ruta->id;
            })->implode(', ');

            $allTransportadorasRutas->push($transportadorasInfo . '|' . $rutasInfo);


            $status = $pedido->status;

            if ($status === 'ENTREGADO') {
                $entregadosCount++;
            } else if ($status === 'NO ENTREGADO') {
                $noEntregadosCount++;
            } else if ($status === 'NOVEDAD') {
                $novedad++;
            }

            $pedidosInfo[] = [
                'pedido_id' => $pedido->id,
                'fecha_entrega' => $pedido->fecha_entrega,
                'rutas' => $rutasInfo,
                'transportadoras' => $transportadorasInfo,
                'status' => $status,
            ];
        }

        // Obtener listas únicas sin repeticiones
        $uniqueTransportadorasRutas = $allTransportadorasRutas->unique()->values();

        $transportadoraRutaCount = collect();

        foreach ($uniqueTransportadorasRutas as $uniqueInfo) {
            list($transportadora, $rutas) = explode('|', $uniqueInfo);

            $counts = collect($pedidosInfo)->where('rutas', $rutas)->where('transportadoras', $transportadora)->countBy('status')->toArray();

            $transportadoraRutaCount->push([
                'pedidos_info' => $pedidosInfo,
                'transportadoras' => $transportadora,
                'rutas' => $rutas,
                'entregados_count' => $counts['ENTREGADO'] ?? 0,
                'no_entregados_count' => $counts['NO ENTREGADO'] ?? 0,
                'novedad_count' => $counts['NOVEDAD'] ?? 0,
                'total_pedidos' => ($counts['ENTREGADO'] ?? 0) + ($counts['NO ENTREGADO'] ?? 0) + ($counts['NOVEDAD'] ?? 0),
            ]);
        }

        // Agrupar internamente por la propiedad "rutas"
        $groupedRutasTransportadoras = $transportadoraRutaCount->groupBy('transportadoras')->map(function ($group) {
            return $group->map(function ($item) {
                return [
                    'rutas' => $item['rutas'],
                    'entregados_count' => $item['entregados_count'],
                    'no_entregados_count' => $item['no_entregados_count'],
                    'novedad_count' => $item['novedad_count'],
                    'total_pedidos' => $item['total_pedidos'],
                ];
            });
        });

        $groupedRutasTransportadoras = $groupedRutasTransportadoras->map(function ($group) {
            $totalGeneralEntregados = $group->sum('entregados_count');
            $totalGeneralNoEntregados = $group->sum('no_entregados_count');
            $totalGeneralNovedades = $group->sum('novedad_count');

            return [
                'items' => $group,
                'totalgeneralentregados' => $totalGeneralEntregados,
                'totalgeneralnoentregados' => $totalGeneralNoEntregados,
                'totalgeneralnovedades' => $totalGeneralNovedades,
            ];
        });

        $final = [
            'pedidos' => $pedidosInfo,
            'listarutas_transportadoras' => $groupedRutasTransportadoras,
            'entregados_count' => $entregadosCount,
            'no_entregados_count' => $noEntregadosCount,
            'novedad_count' => $novedad,
            'total_pedidos' => $entregadosCount + $noEntregadosCount + $novedad,
            'efectividad' => number_format(($entregadosCount / ($entregadosCount + $noEntregadosCount + $novedad)) * 100, 2),
        ];

        $this->updateTransportStats($final, $endDate);

        // return response()->json($final);
    }

    public function updateTransportStats($apiData, $endDate)
    {
        $initialcountervalue = 1;
        // 1. Verificar si la tabla está vacía
        if (!TransportStats::count()) {
            foreach ($apiData['listarutas_transportadoras'] as $transportadoraName => $transportadoraData) {
                $transportId = explode("-", $transportadoraName)[1];
                $entregadosDay = 0;
                $totalPedidosDay = 0;

                foreach ($transportadoraData['items'] as $item) {
                    $entregadosDay = $item['entregados_count'];
                    $totalPedidosDay = $item['total_pedidos'];

                    TransportStats::create([
                        'transport_id' => $transportId,
                        'transport_name' => explode("-", $transportadoraName)[0],
                        // Nombre de la transportadora
                        'route_name' => $item['rutas'],
                        // Nombre de la ruta

                        'monthly_counter' => $initialcountervalue,
                        'daily_counter' => $initialcountervalue,

                        'efficiency_month_date' => $endDate,
                        'efficiency_day_date' => $endDate,

                        'transport_stats_day' => $entregadosDay / $totalPedidosDay,
                        'transport_stats_month' => ($transportadoraData['totalgeneralentregados'] / ($transportadoraData['totalgeneralentregados'] + $transportadoraData['totalgeneralnoentregados'] + $transportadoraData['totalgeneralnovedades']))
                        // array_sum(
                        //     [$transportadoraData['totalgeneralentregados'], $transportadoraData['totalgeneralnoentregados'], $transportadoraData['totalgeneralnovedades']]
                        //     )
                    ]);
                }
            }
        } else {
            foreach ($apiData['listarutas_transportadoras'] as $transportadoraName => $transportadoraData) {
                $transportadoraMonthStat = ($transportadoraData['totalgeneralentregados'] / ($transportadoraData['totalgeneralentregados'] + $transportadoraData['totalgeneralnoentregados'] + $transportadoraData['totalgeneralnovedades']));
                $transportId = explode("-", $transportadoraName)[1];
                $isNewTransportadora = true;

                foreach ($transportadoraData['items'] as $item) {
                    $entregadosDay = $item['entregados_count'];
                    $totalPedidosDay = $item['total_pedidos'];
                    $transportadoraNamePart = explode("-", $transportadoraName)[0];
                    $routeName = $item['rutas'];

                    $existingStat = TransportStats::where('transport_name', $transportadoraNamePart)->first();

                    // Usar el valor de monthly_counter del registro existente si existe, de lo contrario usar 1.
                    $monthlyValue = $existingStat ? $existingStat->monthly_counter : 1;

                    $stat = TransportStats::firstOrCreate(
                        [
                            'transport_name' => $transportadoraNamePart,
                            'route_name' => $routeName,
                        ],
                        [
                            'transport_id' => $transportId,
                            'monthly_counter' => $monthlyValue,
                            'daily_counter' => 1,
                            // Corregido a 1
                            'efficiency_month_date' => $endDate,
                            'efficiency_day_date' => $endDate,
                            'transport_stats_day' => $entregadosDay / $totalPedidosDay,
                        ]
                    );

                    if (!$stat->wasRecentlyCreated) {
                        $stat->daily_counter++;
                        $isNewTransportadora = false;

                        // Consultar el valor actual de transport_stats_day
                        $currentDayStat = $stat->transport_stats_day;

                        // Sumar el nuevo valor calculado
                        $newDayStat = $currentDayStat + ($entregadosDay / $totalPedidosDay);
                        $stat->transport_stats_day = $newDayStat;
                    }

                    $stat->efficiency_day_date = $endDate;
                    $stat->save();
                }

                if (!$isNewTransportadora) {
                    TransportStats::where('transport_name', $transportadoraNamePart)
                        ->increment('monthly_counter');
                }

                // Consultar el valor actual de transport_stats_month
                $currentMonthStat = TransportStats::where('transport_name', $transportadoraNamePart)->value('transport_stats_month');

                // Sumar el nuevo valor calculado
                $newTransportadoraMonthStat = $currentMonthStat + $transportadoraMonthStat;

                // Actualizar el registro con el nuevo total
                TransportStats::where('transport_name', $transportadoraNamePart)
                    ->update([
                        'efficiency_month_date' => $endDate,
                        'transport_stats_month' => $newTransportadoraMonthStat,
                    ]);
            }
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
                if ($value == "PENDIENTE") { //restart
                    $pedido->estado_devolucion = $value;
                    $pedido->do = $value;
                    $pedido->dt = $value;
                    $pedido->marca_t_d = null;
                    $pedido->marca_t_d_t = null;
                    $pedido->received_by = $idUser;
                }
            } elseif ($from == "operator") {
                if ($value == "ENTREGADO EN OFICINA") { //from operator, logistica
                    $pedido->estado_devolucion = $value;
                    $pedido->do = $value;
                    $pedido->marca_t_d = $currentDateTimeText;
                    $pedido->received_by = $idUser;
                }
            } elseif ($from == "seller") {
                if ($value == "EN BODEGA PROVEEDOR") { //from seller return scanner
                    $pedido->estado_devolucion = $value;
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

            // // * if it exists, delete from transaccion_pedidos_transportadora
            // error_log("delete from tpt");

            $idTransportadora = $pedido['transportadora'][0]['id'];
            $fechaEntrega = now()->format('j/n/Y');

            $transaccion = TransaccionPedidoTransportadora::where('id_pedido', $id)
                ->where('id_transportadora', $idTransportadora)
                ->where('fecha_entrega', $fechaEntrega)
                ->get();

            $transaccionFound = $transaccion->first();

            if ($transaccionFound !== null) {
                // error_log($transaccionFound->id);
                $transaccionFound->delete();
                // error_log("deleted");
            }
        }

        //v0
        if ($key == "estado_interno") {
            $pedido->confirmed_by = $idUser;
            $pedido->confirmed_at = $currentDateTime;
        }

        $pedido->save();
        return response()->json([$pedido], 200);
    }
}
