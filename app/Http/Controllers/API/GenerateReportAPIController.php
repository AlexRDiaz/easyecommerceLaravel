<?php

namespace App\Http\Controllers\API;

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


}
