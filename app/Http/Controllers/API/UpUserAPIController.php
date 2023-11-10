<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\UserValidation;
use App\Models\PedidosShopify;
use App\Models\Provider;
use App\Models\RolesFront;
use App\Models\Ruta;
use App\Models\Transportadora;
use App\Models\UpRole;
use App\Models\UpUser;
use App\Models\UpUsersRoleLink;
use App\Models\UpUsersRolesFrontLink;
use App\Models\Vendedore;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpUserAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $upUsers = UpUser::all();
        return response()->json(['data' => $upUsers], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $upUser = UpUser::find($id);
        if (!$upUser) {
            return response()->json(['message' => 'UpUser not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['data' => $upUser], Response::HTTP_OK);
    }

    // Puedes agregar métodos adicionales según tus necesidades, como create, update y delete.

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Valida los datos de entrada (puedes agregar reglas de validación aquí)
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:up_users',
        ]);

        $numerosUtilizados = [];
        while (count($numerosUtilizados) < 10000000) {
            $numeroAleatorio = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            if (!in_array($numeroAleatorio, $numerosUtilizados)) {
                $numerosUtilizados[] = $numeroAleatorio;
                break;
            }
        }
        $resultCode = $numeroAleatorio;


        $user = new UpUser();
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->codigo_generado = $resultCode;
        $user->password = bcrypt('123456789'); // Puedes utilizar bcrypt para encriptar la contraseña
        $user->fecha_alta = $request->input('FechaAlta'); // Fecha actual
        $user->confirmed = $request->input('confirmed');
        $user->estado = $request->input('estado');
        $permisosCadena = json_encode($request->input('PERMISOS'));
        $user->permisos = $permisosCadena;
        $user->blocked = false;
        $user->save();
        $user->vendedores()->attach($request->input('vendedores'), [
        ]);

        $newUpUsersRoleLink = new UpUsersRoleLink();
        $newUpUsersRoleLink->user_id = $user->id; // Asigna el ID del usuario existente
        $newUpUsersRoleLink->role_id = $request->input('role'); // Asigna el ID del rol existente
        $newUpUsersRoleLink->save();


        $userRoleFront = new UpUsersRolesFrontLink();
        $userRoleFront->user_id = $user->id;
        $userRoleFront->roles_front_id = $request->input('roles_front');
        $userRoleFront->save();



        Mail::to($user->email)->send(new UserValidation($resultCode));


        return response()->json(['message' => 'Usuario interno creado con éxito', 'user_id' => $user->id, 'user_id'], 201);

    }

    public function storeProvider(Request $request)
    {
    // // Valida los datos de entrada (puedes agregar reglas de validación aquí)
    $request->validate([
        'username' => 'required|string|max:255',
        'email' => 'required|email|unique:up_users',
    ]);

    $numerosUtilizados = [];
    while (count($numerosUtilizados) < 10000000) {
        $numeroAleatorio = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        if (!in_array($numeroAleatorio, $numerosUtilizados)) {
            $numerosUtilizados[] = $numeroAleatorio;
            break;
        }
    }
    $resultCode = $numeroAleatorio;


    $user = new UpUser();
    $user->username = $request->input('username');
    $user->email = $request->input('email');
    $user->codigo_generado = $resultCode;
    $user->password = bcrypt($request->input('password')); // Puedes utilizar bcrypt para encriptar la contraseña
    $user->fecha_alta = $request->input('FechaAlta'); // Fecha actual
    $user->confirmed = $request->input('confirmed');
    $user->estado = "NO VALIDADO";
    $user->provider = "local";
    $user->confirmed = 1;
    $user->fecha_alta = $request->input('fecha_alta');
    $permisosCadena = json_encode([]);
    $user->permisos = $permisosCadena;
    $user->blocked = false;
    $user->save();
    // $user->providers()->attach($user->id, [
    // ]);


    // $provider= new Provider();
    // $provider->user_id = $user->id;
    // $provider->name = $request->input('porvider_name');
    // $provider->description= $request->input('description');
    // $provider->phone = $request->input('provider_phone');
    // $provider->createdAt= new DateTime();


    $newUpUsersRoleLink = new UpUsersRoleLink();
    $newUpUsersRoleLink->user_id = $user->id; // Asigna el ID del usuario existente
    $newUpUsersRoleLink->role_id = $request->input('role'); // Asigna el ID del rol existente
    $newUpUsersRoleLink->save();


    $userRoleFront = new UpUsersRolesFrontLink();
    $userRoleFront->user_id = $user->id;
    $userRoleFront->roles_front_id = 5;
    $userRoleFront->save();

    $provider = new Provider();
    $provider->name = $request->input('provider_name');
    $provider->phone = $request->input('provider_phone');
    $provider->description= $request->input('description');
    $provider->created_at= new DateTime();
    $provider->user_id = $user->id;
   
    $provider->save();
    $user->providers()->attach($provider->id, [
    ]);
  
   // Mail::to($user->email)->send(new UserValidation($resultCode));


    return response()->json(['message' => 'Vendedor creado con éxito'], 200);

    }


    public function storeGeneral(Request $request)
    {
        // // Valida los datos de entrada (puedes agregar reglas de validación aquí)
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:up_users',
        ]);

        $numerosUtilizados = [];
        while (count($numerosUtilizados) < 10000000) {
            $numeroAleatorio = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            if (!in_array($numeroAleatorio, $numerosUtilizados)) {
                $numerosUtilizados[] = $numeroAleatorio;
                break;
            }
        }
        $resultCode = $numeroAleatorio;


        $user = new UpUser();
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->codigo_generado = $resultCode;
        $user->password = bcrypt($request->input('password')); // Puedes utilizar bcrypt para encriptar la contraseña
        $user->fecha_alta = $request->input('FechaAlta'); // Fecha actual
        $user->confirmed = $request->input('confirmed');
        $user->estado = "NO VALIDADO";
        $user->provider = "local";
        $user->confirmed = 1;
        $user->fecha_alta = $request->input('fecha_alta');
        $permisosCadena = json_encode(["DashBoard", "Reporte de Ventas", "Agregar Usuarios Vendedores", "Ingreso de Pedidos", "Estado Entregas Pedidos", "Pedidos No Deseados", "Billetera", "Devoluciones", "Retiros en Efectivo", "Conoce a tu Transporte"]);
        $user->permisos = $permisosCadena;
        $user->blocked = false;
        $user->save();
        $user->vendedores()->attach($request->input('vendedores'), [
        ]);



        $newUpUsersRoleLink = new UpUsersRoleLink();
        $newUpUsersRoleLink->user_id = $user->id; // Asigna el ID del usuario existente
        $newUpUsersRoleLink->role_id = $request->input('role'); // Asigna el ID del rol existente
        $newUpUsersRoleLink->save();


        $userRoleFront = new UpUsersRolesFrontLink();
        $userRoleFront->user_id = $user->id;
        $userRoleFront->roles_front_id = 2;
        $userRoleFront->save();

        $seller = new Vendedore();
        $seller->nombre_comercial = $request->input('nombre_comercial');
        $seller->telefono_1 = $request->input('telefono1');
        $seller->telefono_2 = $request->input('telefono2');
        $seller->nombre_comercial = $request->input('nombre_comercial');
        $seller->fecha_alta = $request->input('fecha_alta');
        $seller->id_master = $user->id;
        $seller->url_tienda = $request->input('url_tienda');
        $seller->costo_envio = $request->input('costo_envio');
        $seller->costo_devolucion = $request->input('costo_devolucion');
        $seller->referer = $request->input('referer');
        $seller->save();

        $user->vendedores()->attach($seller->id, [
        ]);


        Mail::to($user->email)->send(new UserValidation($resultCode));


        return response()->json(['message' => 'Vendedor creado con éxito'], 200);

    }

    public function getSellerMaster($id)
    {
        $vendedores = UpUser::find($id)->vendedores;

        if (!$vendedores) {
            return response()->json(['message' => 'Vendedores not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($vendedores[0], Response::HTTP_OK);

    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $upUser = UpUser::find($id);
        $newPassword = $request->input('password');

        if (!$upUser) {
            return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($newPassword) {
            $upUser->password = bcrypt($newPassword);
            $upUser->save();
            return response()->json(['message' => 'Contraseña actualizada con éxito', 'user' => $upUser], Response::HTTP_OK);

        } else {
            $upUser->fill($request->all());
            $upUser->save();
            return response()->json(['message' => 'Usuario actualizado con éxito', 'user' => $upUser], Response::HTTP_OK);
        }

        // Agrega tu lógica para actualizar un UpUser existente aquí.
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Agrega tu lógica para eliminar un UpUser aquí.
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Buscar al usuario por su correo electrónico en la tabla UpUser
        $user = UpUser::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Validar la contraseña proporcionada por el usuario con el hash almacenado en la base de datos
        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Intentar generar un token JWT
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo crear el token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Guardar algunos datos del usuario en la sesión
        // $request->session()->put('user_id', $user->id);
        // $request->session()->put('user_email', $user->email);
        $mensaje = "usuario logueado";
        error_log("usuario logueado");
        return response()->json([
            'jwt' => $token,
            'user' => $user
        ], Response::HTTP_OK);
    }


    public function users($id)
    {
        $upUser = UpUser::with([
            'roles_fronts',
            'vendedores',
            'transportadora',
            'operadores',
            'providers',
        ])->find($id);

        if (!$upUser) {
            return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['user' => $upUser], Response::HTTP_OK);
    }



    public function managePermission(Request $request)
    {
        $viewName = $request->input('view_name');
        $userId = $request->input('user_id');

        $upUser = UpUser::find($userId);

        // Si el usuario no existe, devuelve un error
        if (!$upUser) {
            return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Decodificar el JSON de la columna permisos a un array
        $permissions = json_decode($upUser->permisos, true);

        if (in_array($viewName, $permissions)) {
            // Si el nombre de la vista ya existe, lo eliminamos
            $permissions = array_filter($permissions, function ($value) use ($viewName) {
                return $value !== $viewName;
            });
            $permissions = array_values($permissions); // Reindexa las claves
        } else {
            // Si el nombre de la vista no existe, lo agregamos
            $permissions[] = $viewName;
        }

        // Actualizamos el usuario con los permisos modificados
        $upUser->permisos = json_encode($permissions);
        $upUser->save();

        return response()->json(['message' => 'Permisos actualizados correctamente'], Response::HTTP_OK);
    }

    public function getPermissionsSellerPrincipalforNewSeller(Request $request,$userId)
    {
        $upUser = UpUser::find($userId);
    
        // Si el usuario no existe, devuelve un error
        if (!$upUser) {
            return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }
    
        // Decodificar el JSON de la columna permisos a un array
        $permissions = json_decode($upUser->permisos, true);
        $formattedPermissions = [];
    
        foreach ($permissions as $permission) {
            $formattedPermissions[] = [
                'view_name' => $permission,
                'active' => true
            ];
        }
    
        // Codifica el array resultante a JSON y luego agrégalo a un array con la clave "accesos"
        $result = [
            'accesos' => json_encode($formattedPermissions)
        ];
    
        // Retorna la lista de permisos en el formato deseado
        return response()->json($result, Response::HTTP_OK);
    }
    

    public function getSellers($id, $search = null)
    {
        $upUser = UpUser::with([
            'roles_fronts',
            'vendedores',
            'transportadora',
            'operadores',

        ])
            ->whereHas('vendedores', function ($query) use ($id) {
                $query->where('id_master', $id);
            });


        if (!empty($search)) {
            $upUser->where(function ($query) use ($search) {
                $query->where('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $resp = $upUser->get();
        return response()->json(['consulta' => $search, 'users' => $resp], Response::HTTP_OK);

    }

    public function verifyTerms($id)
    {
        $upuser = Upuser::find($id);
        if ($upuser) {
            $acceptedTerms = $upuser->accepted_terms_conditions;
            if ($acceptedTerms == null) {
                $acceptedTerms = false;
            }
            return response()->json($acceptedTerms);
        } else {
            return response()->json(['message' => 'No se encontro el user'], 404);
        }
    }

    public function updateAcceptedTerms($id, Request $request)
    {
        $user_found = UpUser::findOrFail($id);
        $accepted_terms = $request->input('accepted_terms_conditions');

        // Update 'accepted_terms_conditions'
        $user_found->accepted_terms_conditions = $accepted_terms;
        $user_found->save();

        return response()->json(['message' => 'Estado de Términos y condiciones actualizados con éxito'], 200);
    }


    // ! FUNCION DE VENDEDORES QUE NECESITA NERFEO :) 
    public function getUserPedidos($id, Request $request)
    {
        $user = UpUser::with('upUsersPedidos.pedidos_shopifies_ruta_links.ruta', 'upUsersPedidos.pedidos_shopifies_transportadora_links.transportadora')->find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $pedidos = $user->upUsersPedidos->where('estado_interno', 'CONFIRMADO')->where('estado_logistico', 'ENVIADO');


        $allRutasTransportadoras = collect();
        $pedidosInfo = [];
        $entregadosCount = 0;
        $noEntregadosCount = 0;
        $novedad = 0;

        foreach ($pedidos as $pedido) {
            $rutasInfo = $pedido->pedidos_shopifies_ruta_links->map(function ($link) {
                return $link->ruta->titulo . '-' . $link->ruta->id;
            })->implode(', ');

            $transportadorasInfo = $pedido->pedidos_shopifies_transportadora_links->map(function ($link) {
                return $link->transportadora->nombre . '-' . $link->transportadora->id;
            })->implode(', ');

            $allRutasTransportadoras->push($rutasInfo . '|' . $transportadorasInfo);

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
                'rutas' => $rutasInfo,
                'transportadoras' => $transportadorasInfo,
                'status' => $status,
            ];
        }

        // Obtener listas únicas sin repeticiones
        $uniqueRutasTransportadoras = $allRutasTransportadoras->unique()->values();

        $rutaTransportadoraCount = collect();

        foreach ($uniqueRutasTransportadoras as $uniqueInfo) {
            list($rutas, $transportadora) = explode('|', $uniqueInfo);

            $counts = collect($pedidosInfo)->where('rutas', $rutas)->where('transportadoras', $transportadora)->countBy('status')->toArray();

            $rutaTransportadoraCount->push([
                'pedidos_info' => $pedidosInfo,
                'rutas' => $rutas,
                'transportadoras' => $transportadora,
                'entregados_count' => $counts['ENTREGADO'] ?? 0,
                'no_entregados_count' => $counts['NO ENTREGADO'] ?? 0,
                'novedad_count' => $counts['NOVEDAD'] ?? 0,
                'total_pedidos' => ($counts['ENTREGADO'] ?? 0) + ($counts['NO ENTREGADO'] ?? 0) + ($counts['NOVEDAD'] ?? 0),

            ]);
        }

        // Agrupar internamente por la propiedad "rutas"
        $groupedRutasTransportadoras = $rutaTransportadoraCount->groupBy('rutas')->map(function ($group) {
            return $group->map(function ($item) {
                return [
                    'transportadoras' => $item['transportadoras'],
                    'entregados_count' => $item['entregados_count'],
                    'no_entregados_count' => $item['no_entregados_count'],
                    'novedad_count' => $item['novedad_count'],
                    'total_pedidos' => $item['total_pedidos'],
                ];
            });
        });

        return response()->json([
            'pedidos' => $pedidosInfo,
            'listarutas_transportadoras' => $groupedRutasTransportadoras,
            'entregados_count' => $entregadosCount,
            'no_entregados_count' => $noEntregadosCount,
            'novedad_count' => $novedad,
            'total_pedidos' => $entregadosCount + $noEntregadosCount + $novedad,
        ]);
    }

    // ! FUNCION PARECIDA A NERFEO TRANS:) 
    public function getUserPedidosByTransportadora($idTransportadora, Request $request)
    {
        $transportadora = Transportadora::find($idTransportadora);

        if (!$transportadora) {
            return response()->json(['error' => 'Transportadora no encontrada'], 404);
        }

        $pedidos = PedidosShopify::whereHas('pedidos_shopifies_transportadora_links', function ($query) use ($idTransportadora) {
            $query->where('transportadora_id', $idTransportadora)
                ->where('estado_interno', 'CONFIRMADO')
                ->where('estado_logistico', 'ENVIADO');
        })->get();


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
            'identification' => $transportadora->nombre . '-' . $transportadora->id,
            'entregados_count' => $cantidadEntregados,
            'no_entregados_count' => $cantidadNoEntregados,
            'novedad_count' => $cantidadNovedad,
            'total_pedidos' => $sumaTotal,
        ]);
    }

    // ! FUNCION PARECIDA A NERFEO ROUTES:) 

    public function getUserPedidosByRuta($idRuta, Request $request)
    {
        $ruta = Ruta::find($idRuta);

        if (!$ruta) {
            return response()->json(['error' => 'Ruta no encontrada'], 404);
        }

        $pedidos = PedidosShopify::whereHas('pedidos_shopifies_ruta_links', function ($query) use ($idRuta) {
            $query->where('ruta_id', $idRuta);
        })
            ->where('estado_interno', 'CONFIRMADO')
            ->where('estado_logistico', 'ENVIADO')
            ->get();

        $entregadosCount = 0;
        $noEntregadosCount = 0;
        $novedad = 0;

        foreach ($pedidos as $pedido) {
            $status = $pedido->status;
            if ($status === 'ENTREGADO') {
                $entregadosCount++;
            } else if ($status === 'NO ENTREGADO') {
                $noEntregadosCount++;
            } else if ($status === 'NOVEDAD') {
                $novedad++;
            }
        }

        return response()->json([
            'identification' => $ruta->titulo . '-' . $ruta->id,
            'entregados_count' => $entregadosCount,
            'no_entregados_count' => $noEntregadosCount,
            'novedad_count' => $novedad,
            'total_pedidos' => $entregadosCount + $noEntregadosCount + $novedad,
        ]);
    }

    public function getPermisos()
    {
        // Obtén todos los registros de la tabla up_users_roles_front_links
        $registros = UpUser::all();

        // Si deseas devolver solo los campos 'id' y 'permisos', puedes hacer un mapeo
        $permisos = $registros->map(function ($registro) {
            return [
                'id' => $registro->id,
                'permisos' => $registro->permisos,
                // Asegúrate de que 'permisos' sea el nombre correcto del campo
            ];
        });

        return $permisos;
    }

    // UserController.php

    // 


    public function updatePermissions(Request $request)
    {
        $datosVista = $request->input('datos_vista');

        $active = $datosVista['active'];
        $viewName = $datosVista['view_name'];
        $idRol = $datosVista['id_rol'];

        // Obtener todos los usuarios que tienen el rol específico
        $users = UpUser::whereHas('roles_fronts', function ($query) use ($idRol) {
            $query->where('roles_fronts.id', $idRol);
        })->get();

        // Actualizar los permisos de los usuarios y roles_fronts
        foreach ($users as $user) {
            $permissions = json_decode($user->permisos, true) ?? [];

            // Agregar o quitar el valor de 'view_name' según 'active'
            if ($active) {
                // Agregar el valor solo si 'active' es true y no existe
                if (!in_array($viewName, $permissions)) {
                    $permissions[] = $viewName;
                }
            } else {
                // Quitar el valor solo si 'active' es false y existe
                $permissions = array_diff($permissions, [$viewName]);
            }

            // Actualizar los permisos en la tabla up_users
            $user->update(['permisos' => json_encode(array_values($permissions))]);

            // Actualizar la columna accesos en la tabla roles_fronts
            $role = $user->roles_fronts->where('id', $idRol)->first();
            if ($role) {
                $accessos = json_decode($role->accesos, true);

                // Buscar la opción con 'view_name' igual a $viewName y actualizar 'active'
                foreach ($accessos as &$option) {
                    if ($option['view_name'] === $viewName) {
                        $option['active'] = $active;
                    }
                }

                $role->update(['accesos' => json_encode($accessos)]);
            }
        }

        return response()->json(['message' => 'Permisos actualizados con éxito'], 200);
    }

    public function newPermission(Request $request)
    {
        $datosVista = $request->input('datos_vista');
        $active = $datosVista['active'];
        $viewName = $datosVista['view_name'];
        $idRol = $datosVista['id_rol'];

        // Paso 1: Obtener el modelo RolesFront
        $role = RolesFront::find($idRol);

        if ($role) {
            // Paso 2: Obtener el valor actual del campo accesos y convertirlo a un array
            $accesosArray = json_decode($role->accesos, true) ?: [];

            // Paso 3: Actualizar el array con los nuevos valores proporcionados
            $nuevoAcceso = ['active' => $active, 'view_name' => $viewName];
            $accesosArray[] = $nuevoAcceso; // Añadir el nuevo acceso al final del array

            // Paso 4: Convertir el array actualizado a formato JSON
            $nuevoValorAccesos = json_encode($accesosArray);

            // Paso 5: Actualizar el campo accesos en la base de datos
            $role->update(['accesos' => $nuevoValorAccesos]);

            // Puedes devolver una respuesta exitosa si es necesario
            return response()->json(['message' => 'Acceso actualizado correctamente']);
        } else {
            // Devolver una respuesta en caso de que no se encuentre el modelo
            return response()->json(['error' => 'Rol no encontrado'], 404);
        }
    }

    public function deletePermissions(Request $request)
    {
        $datosVista = $request->input('datos_vista');

        $viewName = $datosVista['view_name'];
        $idRol = $datosVista['id_rol'];

        // Obtener todos los usuarios que tienen el rol específico
        $users = UpUser::whereHas('roles_fronts', function ($query) use ($idRol) {
            $query->where('roles_fronts.id', $idRol);
        })->get();

        foreach ($users as $user) {
            $permissions = json_decode($user->permisos, true) ?? [];

            // Eliminar el valor de 'view_name'
            $permissions = array_diff($permissions, [$viewName]);

            // Actualizar los permisos en la tabla up_users
            $user->update(['permisos' => json_encode(array_values($permissions))]);
        }

        foreach ($users as $user) {

            // Actualizar la columna accesos en la tabla roles_fronts
            $role = $user->roles_fronts->where('id', $idRol)->first();
            if ($role) {
                $accessos = json_decode($role->accesos, true);

                // Eliminar la opción con 'view_name'
                $accessos = array_filter($accessos, function ($option) use ($viewName) {
                    return $option['view_name'] !== $viewName;
                });

                $role->update(['accesos' => json_encode(array_values($accessos))]);
            }
        }
        return response()->json(['message' => 'Permisos eliminados en roles y en cada usuario con éxito'], 200);

    }


}