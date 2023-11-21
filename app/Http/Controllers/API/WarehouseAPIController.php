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
        try {
            $validatedData = $request->validate([
                'branch_name' => 'nullable|string|max:70',
                'address' => 'nullable|string|max:70',
                'reference' => 'nullable|string|max:70',
                'description' => 'nullable|string|max:65535',
                'url_image' => 'nullable|string|max:150',
                'city' => 'nullable|string|max:80',
                'collection' => 'nullable|json',
                'provider_id' => 'nullable|integer',
            ]);

            $warehouse = Warehouse::create($validatedData);
            return response()->json($warehouse, 201); // 201: Recurso creado
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500); // 500: Error interno del servidor
        }
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
            'url_image' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:80',
            'collection' => 'nullable|json',
            'provider_id' => 'nullable|integer',
        ]);

        $warehouse = Warehouse::where('warehouse_id', $warehouse_id)->first();
        if (!$warehouse) {
            return response()->json(['message' => 'Not Found!'], 404);
        }
        $warehouse->update($validatedData);
        return response()->json($warehouse);
    }

    public function deactivate(string $warehouse_id)
    {
        $warehouse = Warehouse::where('warehouse_id', $warehouse_id)->first();
        if (!$warehouse) {
            return response()->json(['message' => 'Not Found!'], 404);
        }

        $warehouse->update(['active' => 0]);

        return response()->json(['message' => 'Deactivated Successfully'], 200);
    }

    public function activate(string $warehouse_id)
    {
        $warehouse = Warehouse::where('warehouse_id', $warehouse_id)->first();
        if (!$warehouse) {
            return response()->json(['message' => 'Not Found!'], 404);
        }

        $warehouse->update(['active' => 1]);

        return response()->json(['message' => 'Deactivated Successfully'], 200);
    }



    public function filterByProvider($provider_id)
    {
        $warehouses = Warehouse::where('provider_id', $provider_id)
            // ->where('active', 1) // Agrega esta condición
            ->get();

        if ($warehouses->isEmpty()) {
            return response()->json(['message' => 'No warehouses found for the given provider ID'], 404);
        }

        return response()->json(['warehouses' => $warehouses]);
    }



}

