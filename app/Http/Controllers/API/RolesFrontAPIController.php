<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\RolesFront;

use Illuminate\Http\Request;



class RolesFrontAPIController extends Controller
{
    public function index()
    {
        $roles = RolesFront::all();

        $formattedData = [];

        foreach ($roles as $rol) {
            $formattedData[] = [
                'id' => $rol->id,
                'titulo' => $rol->titulo,
                'accesos' => $rol->accesos,
            ];
        }

        return response()->json($formattedData);
    }


    // !esta no sirve
    public function updateRoles(Request $request)
    {

        // Obtiene la lista de IDs y nuevos valores desde la solicitud
        $listaIds = $request->input('lista_ids');
        $listaNuevosValores = $request->input('lista_vistas');

        // Itera sobre las listas y actualiza los registros correspondientes
        foreach ($listaIds as $key => $id) {
            $nuevoValor = $listaNuevosValores[$key];

            $rol = RolesFront::find($id);

            // Verifica si el rol existe
            if ($rol) {
                // Actualiza el campo accessos en la base de datos
                $rol->accessos = $nuevoValor;
                $rol->save();
            }
        }

        // Puedes devolver un mensaje de éxito o cualquier otra cosa que necesites
        return response()->json(['message' => 'Registros actualizados con éxito']);
    }
    public function getAccesofEspecificRol(Request $request, $rol) {
        
        $rol = RolesFront::where("titulo", "=", $rol)->first();
        
        $activeViewsNames = [];
    
        // Verifica si el rol existe
        if ($rol) {
            $accesos = json_decode($rol->accesos, true);
    
            // Filtrar los que tienen active = true
            $activeAccesos = array_filter($accesos, function ($item) {
                return isset($item['active']) && $item['active'] === true;
            });
    
            // Mapear para obtener solo los view_name
            $activeViewsNames = array_map(function ($item) {
                return $item['view_name'];
            }, $activeAccesos);
        }
    
        return response()->json($activeViewsNames);
    }
}

?>