<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateSchemasTestAPIRequest;
use App\Http\Requests\API\UpdateSchemasTestAPIRequest;
use App\Models\SchemasTest;
use App\Repositories\SchemasTestRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;

/**
 * Class SchemasTestAPIController
 */
class SchemasTestAPIController extends AppBaseController
{
    private SchemasTestRepository $schemasTestRepository;

    public function __construct(SchemasTestRepository $schemasTestRepo)
    {
        $this->schemasTestRepository = $schemasTestRepo;
    }

    /**
     * Display a listing of the SchemasTests.
     * GET|HEAD /schemas-tests
     */
    public function index(Request $request): JsonResponse
    {
        $schemasTests = $this->schemasTestRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($schemasTests->toArray(), 'Schemas Tests retrieved successfully');
    }

    /**
     * Store a newly created SchemasTest in storage.
     * POST /schemas-tests
     */
    public function store(CreateSchemasTestAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        $schemasTest = $this->schemasTestRepository->create($input);

        return $this->sendResponse($schemasTest->toArray(), 'Schemas Test saved successfully');
    }

    /**
     * Display the specified SchemasTest.
     * GET|HEAD /schemas-tests/{id}
     */
    public function show($id): JsonResponse
    {
        /** @var SchemasTest $schemasTest */
        $schemasTest = $this->schemasTestRepository->find($id);

        if (empty($schemasTest)) {
            return $this->sendError('Schemas Test not found');
        }

        return $this->sendResponse($schemasTest->toArray(), 'Schemas Test retrieved successfully');
    }

    /**
     * Update the specified SchemasTest in storage.
     * PUT/PATCH /schemas-tests/{id}
     */
    public function update($id, UpdateSchemasTestAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        /** @var SchemasTest $schemasTest */
        $schemasTest = $this->schemasTestRepository->find($id);

        if (empty($schemasTest)) {
            return $this->sendError('Schemas Test not found');
        }

        $schemasTest = $this->schemasTestRepository->update($input, $id);

        return $this->sendResponse($schemasTest->toArray(), 'SchemasTest updated successfully');
    }

    /**
     * Remove the specified SchemasTest from storage.
     * DELETE /schemas-tests/{id}
     *
     * @throws \Exception
     */
    public function destroy($id): JsonResponse
    {
        /** @var SchemasTest $schemasTest */
        $schemasTest = $this->schemasTestRepository->find($id);

        if (empty($schemasTest)) {
            return $this->sendError('Schemas Test not found');
        }

        $schemasTest->delete();

        return $this->sendSuccess('Schemas Test deleted successfully');
    }
}
