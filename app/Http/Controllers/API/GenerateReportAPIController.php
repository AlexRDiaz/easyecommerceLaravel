<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GenerateReport;
use App\Models\PedidosShopify;
use Carbon\Carbon;

class GenerateReportAPIController extends Controller 
{
    public function index()
    {
        $generateReports = GenerateReport::all();
        return response()->json(['data' => $generateReports], 200);
    }

    public function show($id)
    {
        $generateReport = GenerateReport::find($id);

        if (!$generateReport) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        return response()->json(['data' => $generateReport], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required',
            'archivo' => 'required',
            'id_master' => 'required',
        ]);

        $generateReport = GenerateReport::create($request->all());

        return response()->json(['data' => $generateReport], 201);
    }

    public function update(Request $request, $id)
    {
        $generateReport = GenerateReport::find($id);

        if (!$generateReport) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $request->validate([
            'fecha' => 'required',
            'archivo' => 'required',
            'id_master' => 'required',
        ]);

        $generateReport->update($request->all());

        return response()->json(['data' => $generateReport], 200);
    }

    public function destroy($id)
    {
        $generateReport = GenerateReport::find($id);

        if (!$generateReport) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $generateReport->delete();

        return response()->json(['message' => 'Report deleted'], 204);
    }


    private function recursiveWhereHas($query, $relation, $property, $searchTerm)
    {
        if ($searchTerm == "null") {
            $searchTerm = null;
        }
        if (strpos($property, '.') !== false) {

            $nestedRelation = substr($property, 0, strpos($property, '.'));
            $nestedProperty = substr($property, strpos($property, '.') + 1);

            $query->whereHas($relation, function ($q) use ($nestedRelation, $nestedProperty, $searchTerm) {
                $this->recursiveWhereHas($q, $nestedRelation, $nestedProperty, $searchTerm);
            });
        } else {
            $query->whereHas($relation, function ($q) use ($property, $searchTerm) {
                $q->where($property, '=', $searchTerm);
            });
        }
    }
}

//     public function generateReport(Request $request){

//         $data = $request->json()->all();
//         $startDate = $data['start'];
//         $endDate = $data['end'];
//         $startDateFormatted = Carbon::createFromFormat('j/n/Y', $startDate)->format('Y-m-d');
//         $endDateFormatted = Carbon::createFromFormat('j/n/Y', $endDate)->format('Y-m-d');



//         // ! *************************************
//         $Map = $data['and'];
//         $not = $data['not'];
//         // ! *************************************

//         $filtersOrders = PedidosShopify::with(['operadore.up_users','transportadora','users.vendedores','novedades','pedidoFecha','ruta','subRuta'])
//             ->whereRaw("STR_TO_DATE(fecha_entrega, '%e/%c/%Y') BETWEEN ? AND ?", [$startDateFormatted, $endDateFormatted])
//             ->where((function ($filtersOrders) use ($Map) {
//                 foreach ($Map as $condition) {
//                     foreach ($condition as $key => $valor) {
//                         if (strpos($key, '.') !== false) {
//                             $relacion = substr($key, 0, strpos($key, '.'));
//                             $propiedad = substr($key, strpos($key, '.') + 1);
//                             $this->recursiveWhereHas($filtersOrders, $relacion, $propiedad, $valor);
//                         } else {
//                             $filtersOrders->where($key, '=', $valor);
//                         }

//                     }
//                 }
//             }))->where((function ($filtersOrders) use ($not) {
//             foreach ($not as $condition) {
//                 foreach ($condition as $key => $valor) {
//                     if (strpos($key, '.') !== false) {
//                         $relacion = substr($key, 0, strpos($key, '.'));
//                         $propiedad = substr($key, strpos($key, '.') + 1);
//                         $this->recursiveWhereHas($filtersOrders, $relacion, $propiedad, $valor);
//                     } else {
//                         $filtersOrders->where($key, '!=', $valor);
//                     }

//                 }
//             }
//         }));


//     if ($filtersOrders->isEmpty()) {
//         return response()->json([
//             'code' => 400,
//             'Result' => 'No Existe Ningun Registro'
//         ], 400);
//     }

//     // Transforma los resultados en un formato deseado (similar al formato del código original)
//     $result = $filtersOrders->map(function ($objeto) {
//         return [
//             "Fecha de Ingreso" => $objeto->Marca_T_I,
//             "Fecha de Entrega" => $objeto->Fecha_Entrega,
//             // Agrega los otros campos que necesites
//         ];
//     });

//     // Exporta los resultados a Excel (se asume que ya tienes configurada la exportación a Excel en Laravel)

//     // Genera un nombre de archivo único
//     $uniqueFileName = 'data_' . Str::random(6) . '.xlsx';

//     // Ruta para almacenar el archivo
//     $filePath = storage_path('public/uploads/') . $uniqueFileName;

//     // Guarda los datos en el archivo Excel
//     // (debes implementar la lógica de exportación a Excel en Laravel)

//     // Crea un registro en la base de datos para el informe generado
//     // GenerateReport::create([
//     //     'fecha' => $fecha,
//     //     'archivo' => $filePath,
//     //     'id_master' => $idMaster
//     // ]);

//     // // Retorna la respuesta con la ubicación del archivo generado
//     // return response()->json([
//     //     'code' => 200,
//     //     'Result' => $filePath
//     // ], 200);
// }


    

