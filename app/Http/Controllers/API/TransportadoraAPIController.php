<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transportadora;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransportadoraAPIController extends Controller
{
    public function index()
    {
        $transportadoras = Transportadora::all();
        return response()->json($transportadoras);
    }

    public function show($id)
    {
        $transportadora = Transportadora::findOrFail($id);
        return response()->json($transportadora);
    }

    public function store(Request $request)
    {
        $transportadora = Transportadora::create($request->all());
        return response()->json($transportadora, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $transportadora = Transportadora::findOrFail($id);
        $transportadora->update($request->all());
        return response()->json($transportadora, Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $transportadora = Transportadora::findOrFail($id);
        $transportadora->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
