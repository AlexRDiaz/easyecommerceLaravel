<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateIntegrationAPIRequest;
use App\Http\Requests\API\UpdateIntegrationAPIRequest;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Class IntegrationAPIController
 */
class IntegrationAPIController extends Controller
{

    /**
     * Display a listing of the Integrations.
     * GET|HEAD /integrations
     */
    public function index()
    {
        //
        $integrations = Integration::all();
        return response()->json($integrations);
    }
    /**
     * Store a newly created Integration in storage.
     * POST /integrations
     */
    
    /**
     * Display the specified Integration.
     * GET|HEAD /integrations/{id}
     */
    public function show($id): JsonResponse
    {
        /** @var Integration $integration */
        $integration = Integration::find($id);

        if (empty($integration)) {
            return response()->json([
                'error' => 'No data '
            ], 404);
        }


        return response()->json([
            "res" =>$integration
        ]);
    
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $integration = Integration::where($data["name"])->get();

        if (!empty($integration)) {


            
            return response()->json([
                'error' => 'No data '
            ], 404);
        }
        $newIntegration=new Integration();
        $newIntegration->name=$data["name"];
        $newIntegration->description=$data["description"];
        
        

        


        return response()->json([
            "res" =>$integration
        ]);
    
    }



    /**
     * Update the specified Integration in storage.
     * PUT/PATCH /integrations/{id}
     */
    // public function update($id, UpdateIntegrationAPIRequest $request): JsonResponse
    // {
    //     $input = $request->all();

    //     /** @var Integration $integration */
    //     $integration = $this->integrationRepository->find($id);

    //     if (empty($integration)) {
    //         return $this->sendError('Integration not found');
    //     }

    //     $integration = $this->integrationRepository->update($input, $id);

    //     return $this->sendResponse($integration->toArray(), 'Integration updated successfully');
    // }

    /**
     * Remove the specified Integration from storage.
     * DELETE /integrations/{id}
     *
     * @throws \Exception
     */
    // public function destroy($id): JsonResponse
    // {
    //     /** @var Integration $integration */
    //     $integration = $this->integrationRepository->find($id);

    //     if (empty($integration)) {
    //         return $this->sendError('Integration not found');
    //     }

    //     $integration->delete();

    //     return $this->sendSuccess('Integration deleted successfully');
    // }
}
