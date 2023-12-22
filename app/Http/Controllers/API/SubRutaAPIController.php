<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SubRuta;
use App\Models\Transportadora;
use App\Models\Ruta;
use Illuminate\Http\Request;

class SubRutaAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $subrutas = SubRuta::all();
        //$rutas = Ruta::with('transportadoras', 'sub_rutas')->get();
        return response()->json($subrutas);
    }

    
    public function getOperatorsbySubrouteAndTransportadora(Request $request, $subRutaId)
    {
        // Extraer los datos del JSON
        $data = $request->json()->all();
        $transportadoraId = $data['transportadora_id'];
    
        $subRuta = SubRuta::find($subRutaId);
        
        if (!$subRuta) {
            return response()->json(['mensaje' => 'SubRuta no encontrada'], 404);
        }
    
        // Busca la transportadora para asegurarse de que exista
        $transportadora = Transportadora::find($transportadoraId);
        if (!$transportadora) {
            return response()->json(['mensaje' => 'Transportadora no encontrada'], 404);
        }
    
        // Obtiene los operadores que están en la subruta y asociados con la transportadora especificada
        $operadores = $subRuta->operadores()
                              ->whereHas('transportadoras', function ($query) use ($transportadoraId) {
                                  $query->where('transportadoras.id', $transportadoraId);
                              })
                              ->with('up_users')
                              ->get()
                              ->filter(function ($operador) {
                                  // Verifica si el operador tiene un username asociado
                                  return !empty($operador->up_users->first()) && !empty($operador->up_users->first()->username);
                              })
                              ->map(function ($operador) {
                                  // Construye el string con el formato "username-id"
                                  $username = $operador->up_users->first()->username;
                                  return $username . '-' . $operador->id;
                              })
                              ->values(); // Reindexa el arreglo para asegurar que es un arreglo de strings
    
        return response()->json($operadores);
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
}
