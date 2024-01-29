<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transportadora;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
                'customer_service_phone' => 'nullable|string|max:70',
                'reference' => 'nullable|string|max:70',
                'description' => 'nullable|string|max:65535',
                'url_image' => 'nullable|string|max:150',
                'city' => 'nullable|string|max:80',
                'collection' => 'nullable|json',
                'provider_id' => 'nullable|integer',
            ]);

            $warehouse = Warehouse::create($validatedData);
            if ($warehouse) {
                $to = 'easyecommercetest@gmail.com';
                $subject = 'Aprobación de una bodega nueva';
                $message = 'Se ha creado la bodega "' . $request->branch_name . '" a la espera de la aprobación de funcionamiento.';
                Mail::raw($message, function ($mail) use ($to, $subject) {
                    $mail->to($to)->subject($subject);
                });

                return response()->json($warehouse, 201); // 201: Recurso creado

            }
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
            'customer_service_phone' => 'nullable|string|max:70',
            'reference' => 'nullable|string|max:70',
            'description' => 'nullable|string|max:65535',
            'url_image' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:80',
            'collection' => 'nullable|json',
            'provider_id' => 'nullable|integer',
            'active' => 'nullable|integer',
            'approved' => 'nullable|integer',
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
        // Usamos el método where para filtrar por 'provider_id'
        $warehouses = Warehouse::where('provider_id', $provider_id)->get();

        // Verificamos si la colección está vacía
        if ($warehouses->isEmpty()) {
            return response()->json(['message' => 'No warehouses found for the given provider ID'], 404);
        }

        // Usamos el método with para cargar la relación 'provider'
        $warehouses = Warehouse::with('provider')->where('provider_id', $provider_id)->get();

        return response()->json(['warehouses' => $warehouses]);
    }

    public function approvedWarehouses(Request $request)
    {
        $data = $request->json()->all();
        $idTransportadora = $data['idTransportadora'];

        // Obtener el nombre de la transportadora
        $transportadoraNombre = Transportadora::where('id', $idTransportadora)->pluck('nombre')->first();

        // Obtener almacenes aprobados
        $warehouses = Warehouse::where('approved', 1)->get();

        // Días de la semana mapeados
        $daysOfWeek = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

        // Transformaciones en las propiedades
        $warehouses = $warehouses->filter(function ($warehouse) use ($daysOfWeek, $transportadoraNombre) {
            // Decodificar la cadena JSON en 'collection'
            $collection = json_decode($warehouse['collection'], true);

            // Convertir 'collectionDays' a nombres de días
            $collection['collectionDays'] = array_map(function ($day) use ($daysOfWeek) {
                return $daysOfWeek[$day]; // No restamos porque los días comienzan desde 1
            }, $collection['collectionDays']);

            // Filtrar almacenes con 'collectionTransport' igual al nombre de la transportadora
            return isset($collection['collectionTransport']) && $collection['collectionTransport'] == $transportadoraNombre;
        })->map(function ($warehouse) use ($daysOfWeek) {
            // Decodificar la cadena JSON en 'collection'
            $collection = json_decode($warehouse['collection'], true);

            // Convertir 'collectionDays' a nombres de días
            $collection['collectionDays'] = array_map(function ($day) use ($daysOfWeek) {
                return $daysOfWeek[$day]; // No restamos porque los días comienzan desde 1
            }, $collection['collectionDays']);

            // Asignar la colección modificada de nuevo a 'collection'
            $warehouse['collection'] = $collection;

            return $warehouse;
        });
        // Reindexar el array numéricamente
        $warehouses = $warehouses->values();

        return response()->json(['warehouses' => $warehouses]);
    }


    public function warehousesPed(Request $request)
    {
        // $data = $request->json()->all();
        // $idTransportadora = $data['idTransportadora'];

        // // Obtener el nombre de la transportadora
        // $transportadoraNombre = Transportadora::where('id', $idTransportadora)->pluck('nombre')->first();

        // // Obtener almacenes aprobados
        // $warehouses = Warehouse::where('approved', 1)->get();

        // // Transformaciones en las propiedades
        // $warehouses = $warehouses->filter(function ($warehouse) use ($transportadoraNombre) {
        //     // Decodificar la cadena JSON en 'collection'
        //     $collection = json_decode($warehouse['collection'], true);

        //     // Filtrar almacenes con 'collectionTransport' igual al nombre de la transportadora
        //     return isset($collection['collectionTransport']) && $collection['collectionTransport'] == $transportadoraNombre;
        // })->map(function ($warehouse) {
        //     // Decodificar la cadena JSON en 'collection'
        //     $collection = json_decode($warehouse['collection'], true);

        //     // Asignar la colección modificada de nuevo a 'collection'
        //     $warehouse['collection'] = $collection;

        //     return $warehouse;
        // });
        // // Reindexar el array numéricamente
        // $warehouses = $warehouses->values();

        // return response()->json(['warehouses' => $warehouses]);
    }




}
