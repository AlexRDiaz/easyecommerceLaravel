<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateReserveAPIRequest;
use App\Http\Requests\API\UpdateReserveAPIRequest;
use App\Models\Reserve;
use App\Repositories\ReserveRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Class ReserveAPIController
 */
class ReserveAPIController extends Controller
{


    /**
     * Display a listing of the Reserves.
     * GET|HEAD /reserves
     */
    public function index(Request $request)
    {
        $reserves = Reserve::all();
        //     $request->except(['skip', 'limit']),
        //     $request->get('skip'),
        //     $request->get('limit')
        // );

        return response()->json(['reserve' => $reserves], Response::HTTP_OK);
    }

    /**
     * Store a newly created Reserve in storage.
     * POST /reserves
     */
    public function store(CreateReserveAPIRequest $request)
    {

        $data = $request->json()->all();
        $product_id = $data['product_id'];
        $skuProduct = $data['sku_product'];
        $units = $data['units'];
        $description = $data['description'];
        $type = $data['type'];

        $createReserve = new Reserve();


        $createReserve->save();
        return $createReserve;
    }

    /**
     * Display the specified Reserve.
     * GET|HEAD /reserves/{id}
     */
    public function show($id)
    {
        // /** @var Reserve $reserve */
        $reserve = Reserve::find($id);

        // if (empty($reserve)) {
        //     return $this->sendError('Reserve not found');
        // }

        return $this->sendResponse($reserve->toArray(), 'Reserve retrieved successfully');
    }

    /**
     * Update the specified Reserve in storage.
     * PUT/PATCH /reserves/{id}
     */

    public function findByProductAndSku(Request $request)
    {
        $input = $request->all();

        $reserve = Reserve::where('product_id', $input["product_id"])->where("sku", $input['sku'])
            ->where("id_comercial", $input['sku'])->first();

        if ($reserve == null) {
            return response()->json(['response' => false], Response::HTTP_NOT_FOUND);
        }
        return response()->json(['reserve' => $reserve, "response" => true], Response::HTTP_OK);
    }
    public function update($id, UpdateReserveAPIRequest $request)
    {
        // $input = $request->all();

        // /** @var Reserve $reserve */
        // $reserve = $this->reserveRepository->find($id);

        // if (empty($reserve)) {
        //     return $this->sendError('Reserve not found');
        // }

        // $reserve = $this->reserveRepository->update($input, $id);

        // return $this->sendResponse($reserve->toArray(), 'Reserve updated successfully');
    }

    /**
     * Remove the specified Reserve from storage.
     * DELETE /reserves/{id}
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        // /** @var Reserve $reserve */
        // $reserve = $this->reserveRepository->find($id);

        // if (empty($reserve)) {
        //     return $this->sendError('Reserve not found');
        // }

        // $reserve->delete();

        // return $this->sendSuccess('Reserve deleted successfully');
    }
}
