<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateReserveAPIRequest;
use App\Http\Requests\API\UpdateReserveAPIRequest;
use App\Models\Reserve;
use App\Repositories\ReserveRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;

/**
 * Class ReserveAPIController
 */
class ReserveAPIController extends AppBaseController
{
    private ReserveRepository $reserveRepository;

    public function __construct(ReserveRepository $reserveRepo)
    {
        $this->reserveRepository = $reserveRepo;
    }

    /**
     * Display a listing of the Reserves.
     * GET|HEAD /reserves
     */
    public function index(Request $request): JsonResponse
    {
        $reserves = $this->reserveRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($reserves->toArray(), 'Reserves retrieved successfully');
    }

    /**
     * Store a newly created Reserve in storage.
     * POST /reserves
     */
    public function store(CreateReserveAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        $reserve = $this->reserveRepository->create($input);

        return $this->sendResponse($reserve->toArray(), 'Reserve saved successfully');
    }

    /**
     * Display the specified Reserve.
     * GET|HEAD /reserves/{id}
     */
    public function show($id): JsonResponse
    {
        /** @var Reserve $reserve */
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            return $this->sendError('Reserve not found');
        }

        return $this->sendResponse($reserve->toArray(), 'Reserve retrieved successfully');
    }

    /**
     * Update the specified Reserve in storage.
     * PUT/PATCH /reserves/{id}
     */
    public function update($id, UpdateReserveAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        /** @var Reserve $reserve */
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            return $this->sendError('Reserve not found');
        }

        $reserve = $this->reserveRepository->update($input, $id);

        return $this->sendResponse($reserve->toArray(), 'Reserve updated successfully');
    }

    /**
     * Remove the specified Reserve from storage.
     * DELETE /reserves/{id}
     *
     * @throws \Exception
     */
    public function destroy($id): JsonResponse
    {
        /** @var Reserve $reserve */
        $reserve = $this->reserveRepository->find($id);

        if (empty($reserve)) {
            return $this->sendError('Reserve not found');
        }

        $reserve->delete();

        return $this->sendSuccess('Reserve deleted successfully');
    }
}
