<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PedidoFecha;
use App\Models\pedidos_shopifies;
use App\Models\PedidosShopifiesPedidoFechaLink;
use App\Models\PedidosShopify;
use App\Models\Ruta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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
    public function createDateOrderLaravel(Request $req)  {
        $data = $req->json()->all();
        $fechaActual = $data['fecha'];
        $pedidoFecha = PedidoFecha::where('fecha', $fechaActual)->first();
        $newpedidoFecha="";
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
        
        if($data['Observacion']!= null ){
            $Observacion = $data['Observacion'];
        }else{
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
            'pedidos_shopify_order'=> $pedidoShopifyOrder
        ]);
        $pedido->up_users_pedidos_shopifies_links()->create([
            "user_id" => $users,
            "pedidos_shopify_id" => $pedido->id,
            'pedidos_shopify_order'=> $pedidoShopifyOrder,
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
        $pedido = PedidosShopify::with(['operadore.up_users', 'transportadora', 'users.vendedores', 'novedades', 'pedidoFecha', 'ruta', 'subRuta'])
            ->where('id', $id)
            ->first();

        if (!$pedido) {
            return response()->json(['message' => 'No se pudo actualizar pedido con el ID especificado'], 404);
        }

        $pedido->fecha_confirmacion = $fechaConfirmacion;
        $pedido->estado_interno = $estadoInterno;
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

        $result = PedidosShopify::
            with(['operadore.up_users'])
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



}