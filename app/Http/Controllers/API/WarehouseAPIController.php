<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseAPIController extends Controller
{
    public function index()
    {
        //
        $warehouses = Warehouse::all();
        return response()->json(['warehouses' => $warehouses]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branch_name' => 'nullable|string|max:70',
            'address' => 'nullable|string|max:70',
            'reference' => 'nullable|string|max:70',
            'description' => 'nullable|string|max:65535',
            'provider_id' => 'nullable|integer'
        ]);

        $warehouse = Warehouse::create($validatedData);
        return response()->json($warehouse, 201); // 201: Recurso creado
    }

    public function show(string $warehouse_id)
    {
        $warehouse = Warehouse::where('warehouse_id', $warehouse_id)->first();
        if (!$warehouse) {
            return response()->json(['message' => 'Not Found!'], 404);
        }
        return response()->json($warehouse);
    }
    



    public function update(Request $request, string $warehouse_id)
    {
        $validatedData = $request->validate([
            'branch_name' => 'nullable|string|max:70',
            'address' => 'nullable|string|max:70',
            'reference' => 'nullable|string|max:70',
            'description' => 'nullable|string|max:65535',
            'provider_id' => 'nullable|integer'
        ]);

        $warehouse = Warehouse::where('warehouse_id', $warehouse_id)->first();
    if (!$warehouse) {
        return response()->json(['message' => 'Not Found!'], 404);
    }
    $warehouse->update($validatedData);
    return response()->json($warehouse);
    }

    public function destroy(string $warehouse_id)
    {
        $warehouse = Warehouse::where('warehouse_id', $warehouse_id)->first();
        if (!$warehouse) {
            return response()->json(['message' => 'Not Found!'], 404);
        }
        $warehouse->delete();
        return response()->json(['message' => 'Deleted Successfully'], 200);
    }
    

}