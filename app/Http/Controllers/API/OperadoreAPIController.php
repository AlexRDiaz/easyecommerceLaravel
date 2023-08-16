<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Operadore;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OperadoreAPIController extends Controller
{
    public function index()
    {
        $operadores = Operadore::all();
        return response()->json($operadores);
    }

    public function show($id)
    {
        $operadore = Operadore::findOrFail($id);
        return response()->json($operadore);
    }

    public function store(Request $request)
    {
        $operadore = Operadore::create($request->all());
        return response()->json($operadore, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $operadore = Operadore::findOrFail($id);
        $operadore->update($request->all());
        return response()->json($operadore, Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $operadore = Operadore::findOrFail($id);
        $operadore->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
