<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateProductAPIRequest;
use App\Http\Requests\API\UpdateProductAPIRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Controller;

/**
 * Class ProductAPIController
 */
class ProductAPIController extends Controller
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepository = $productRepo;
    }

    /**
     * Display a listing of the Products.
     * GET|HEAD /products
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->productRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($products->toArray(), 'Products retrieved successfully');
    }

    /**
     * Store a newly created Product in storage.
     * POST /products
     */
    public function store(CreateProductAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        $product = $this->productRepository->create($input);

        return $this->sendResponse($product->toArray(), 'Product saved successfully');
    }

    /**
     * Display the specified Product.
     * GET|HEAD /products/{id}
     */
    public function show($id): JsonResponse
    {
        /** @var Product $product */
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            return $this->sendError('Product not found');
        }

        return $this->sendResponse($product->toArray(), 'Product retrieved successfully');
    }

    /**
     * Update the specified Product in storage.
     * PUT/PATCH /products/{id}
     */
    public function update($id, UpdateProductAPIRequest $request): JsonResponse
    {
        $input = $request->all();

        /** @var Product $product */
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            return $this->sendError('Product not found');
        }

        $product = $this->productRepository->update($input, $id);

        return $this->sendResponse($product->toArray(), 'Product updated successfully');
    }

    /**
     * Remove the specified Product from storage.
     * DELETE /products/{id}
     *
     * @throws \Exception
     */
    public function destroy($id): JsonResponse
    {
        /** @var Product $product */
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            return $this->sendError('Product not found');
        }

        $product->delete();

        return $this->sendSuccess('Product deleted successfully');
    }
}
