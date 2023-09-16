<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RolesFront;
use App\Models\UpRole;
use App\Models\UpUser;
use App\Models\UpUsersRoleLink;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
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

        $user = new UpUser();
        $user->username = $request->input('username');
        $user->email = $request->input('email');
    
        $user->password = bcrypt('123456789'); // Puedes utilizar bcrypt para encriptar la contraseña
        $user->fecha_alta =$request->input('FechaAlta'); // Fecha actual
        $user->confirmed =$request->input('confirmed');
        $user->estado = $request->input('VALIDADO');
        $permisosCadena = json_encode($request->input('PERMISOS'));

        $user->permisos = $permisosCadena;
        $user->blocked = false;

        $user->save();
       $user->vendedores()->attach($request->input('vendedores'), [
     
       ]);
       $newUpUsersRoleLink = new UpUsersRoleLink();
       $newUpUsersRoleLink->user_id = $user->id; // Asigna el ID del usuario existente
       $newUpUsersRoleLink->role_id = $request->input('role');   // Asigna el ID del rol existente
       $newUpUsersRoleLink->save();

    
       $user->roles_fronts()->attach($request->input('role'));

      return response()->json(['message' => 'Usuario interno creado con éxito', 'user_id' => $user->id], 201);

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

    if (!$upUser) {
        return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
    }

    $upUser->fill($request->all());

    $upUser->save();

    return response()->json(['message' => 'Usuario actualizado con éxito', 'user' => $upUser], Response::HTTP_OK);

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

        return response()->json([
            'jwt' => $token, 
            'user' => $user], Response::HTTP_OK);
    }


    public function users($id){
        $upUser = UpUser::with([
            'roles_fronts',
            'vendedores',
            'transportadora',
            'operadores',
        ])->find($id);
    
        if (!$upUser) {
            return response()->json(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }
    
        return response()->json(['user' => $upUser], Response::HTTP_OK);
    }


    public function getSellers($id,$search=null){
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
            return response()->json(['consulta'=>$search,'users' => $resp], Response::HTTP_OK);

        }  
  
    
}
