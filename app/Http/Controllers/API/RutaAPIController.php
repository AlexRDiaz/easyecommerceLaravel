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
}
