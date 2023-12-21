<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use Illuminate\Http\Request;

class RutaAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $rutas = Ruta::all();
        //$rutas = Ruta::with('transportadoras', 'sub_rutas')->get();
        return response()->json($rutas);
    }

    public function activeRoutes()
    {
        $rutas = Ruta::where('active', 1)->get();
        
        $rutaStrings = [];
    
        foreach ($rutas as $ruta) {
            // Concatena el título y el ID de la ruta
            $rutaString = $ruta->titulo. '-' .$ruta->id  ;
            $rutaStrings[] = $rutaString;
        }
    
        return $rutaStrings;
    }
    
    public function show(string $id)
    {
        //
        $ruta = Ruta::with('transportadoras', 'sub_rutas')->findOrFail($id);
        return response()->json($ruta);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function getSubRutasByRuta($rutaId)
    {
        // Encuentra la ruta por su ID y carga las subrutas relacionadas
        $ruta = Ruta::with('sub_rutas_ruta_links.sub_ruta')->findOrFail($rutaId);
    
        // Extrae las subrutas y sus detalles, omitiendo aquellas sin nombre
        $subRutas = $ruta->sub_rutas_ruta_links->map(function ($subRutasRutaLink) {
            $subRuta = $subRutasRutaLink->sub_ruta;
            // Verifica si la subruta tiene un título
            if (!empty($subRuta->titulo)) {
                // Concatena el nombre de la subruta y el ID con un guión
                return $subRuta->titulo . '-' . $subRuta->id;
            }
            return null; // Retorna null si no hay título
        })->filter(); // Utiliza filter para eliminar los valores null
    
        return response()->json($subRutas->values()); // values() para reindexar las claves del array
    }
    
}
