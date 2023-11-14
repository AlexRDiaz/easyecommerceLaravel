<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $products = Product::with('warehouse')->get();
        return response()->json($products);
    }


    public function getProducts(Request $request)
    {
        //
        $data = $request->json()->all();

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];

        $populate = $data['populate'];
        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        $and = $data['and'];
        
        $products = Product::with($populate)
        ->where(function ($products) use ($searchTerm, $filteFields) {
            foreach ($filteFields as $field) {
                if (strpos($field, '.') !== false) {
                    $relacion = substr($field, 0, strpos($field, '.'));
                    $propiedad = substr($field, strpos($field, '.') + 1);
                    $this->recursiveWhereHas($products, $relacion, $propiedad, $searchTerm);
                } else {
                    $products->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            }
        })
        ->where((function ($pedidos) use ($and) {
            foreach ($and as $condition) {
                foreach ($condition as $key => $valor) {
                    $parts = explode("/", $key);
                    $type = $parts[0];
                    $filter = $parts[1];
                    if (strpos($filter, '.') !== false) {
                        $relacion = substr($filter, 0, strpos($filter, '.'));
                        $propiedad = substr($filter, strpos($filter, '.') + 1);
                        $this->recursiveWhereHas($pedidos, $relacion, $propiedad, $valor);
                    } else {
                        if ($type == "equals") {
                            $pedidos->where($filter, '=', $valor);
                        } else {
                            $pedidos->where($filter, 'LIKE', '%' . $valor . '%');
                        }
                    }
                }
            }
        }));

        // ! sort
        $orderByText = null;
        $orderByDate = null;
        $sort = $data['sort'];
        $sortParts = explode(':', $sort);

        $pt1 = $sortParts[0];

        $type = (stripos($pt1, 'fecha') !== false || stripos($pt1, 'marca') !== false) ? 'date' : 'text';

        $dataSort = [
            [
                'field' => $sortParts[0],
                'type' => $type,
                'direction' => $sortParts[1],
            ],
        ];

        foreach ($dataSort as $value) {
            $field = $value['field'];
            $direction = $value['direction'];
            $type = $value['type'];

            if ($type === "text") {
                $orderByText = [$field => $direction];
            } else {
                $orderByDate = [$field => $direction];
            }
        }

        if ($orderByText !== null) {
            $products->orderBy(key($orderByText), reset($orderByText));
        } else {
            $products->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
        }
        // ! **************************************************
        $products = $products->paginate($pageSize, ['*'], 'page', $pageNumber);
        return response()->json($products);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->json()->all();
        // return response()->json($data, 200);

        $product_name = $data['product_name'];
        $stock = $data['stock'];
        $features = json_encode($data['features']);
        $price = $data['price'];
        $url_img = $data['url_img'];
        $warehouse_id = $data['warehouse_id'];

        $newProduct = new Product();
        $newProduct->product_name = $product_name;
        $newProduct->stock = $stock;
        $newProduct->features = $features;
        $newProduct->price = $price;
        $newProduct->url_img = $url_img;
        $newProduct->warehouse_id = $warehouse_id;

        $newProduct->save();

        return response()->json($newProduct, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request,string $id)
    {
        // $product = Product::with('warehouse')->findOrFail($id);
        // return response()->json($product);
        $data = $request->json()->all();
        $populate = $data['populate'];
        $product = Product::with($populate)
            ->where('product_id', $id)
            ->first();
        if (!$product) {
            return response()->json(['message' => 'No se encontraro pedido con el ID especificado'], 404);
        }
        return response()->json($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $transaccion = Product::findOrFail($id);
        $transaccion->update($request->all());
        return response()->json($transaccion, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
