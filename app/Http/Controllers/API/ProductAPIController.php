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


    // public function getProducts(Request $request)
    // {
    //     //all for catalog
    //     $data = $request->json()->all();

    //     $pageSize = $data['page_size'];
    //     $pageNumber = $data['page_number'];
    //     $searchTerm = $data['search'];

    //     $populate = $data['populate'];
    //     if ($searchTerm != "") {
    //         $filteFields = $data['or'];
    //     } else {
    //         $filteFields = [];
    //     }

    //     $andMap = $data['and'];

    //     $products = Product::with($populate)
    //         ->where(function ($products) use ($searchTerm, $filteFields) {
    //             foreach ($filteFields as $field) {
    //                 if (strpos($field, '.') !== false) {
    //                     $relacion = substr($field, 0, strpos($field, '.'));
    //                     $propiedad = substr($field, strpos($field, '.') + 1);
    //                     $this->recursiveWhereHas($products, $relacion, $propiedad, $searchTerm);
    //                 } else {
    //                     $products->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
    //                 }
    //             }
    //         })
    //         ->where((function ($products) use ($andMap) {
    //             foreach ($andMap as $condition) {
    //                 foreach ($condition as $key => $valor) {
    //                     if (strpos($key, '.') !== false) {
    //                         $relacion = substr($key, 0, strpos($key, '.'));
    //                         $propiedad = substr($key, strpos($key, '.') + 1);
    //                         $this->recursiveWhereHas($products, $relacion, $propiedad, $valor);
    //                     } else {
    //                         $products->where($key, '=', $valor);
    //                     }
    //                 }
    //             }
    //         }))
    //         ->whereHas('warehouse', function ($warehouse) {
    //             $warehouse->where('active', 1)
    //             ->where('approved', 1);
    //         })
    //         ->where('active', 1)//los No delete
    //         ->where('approved', 1);
    //     // ! sort
    //     $orderByText = null;
    //     $orderByDate = null;
    //     $sort = $data['sort'];
    //     $sortParts = explode(':', $sort);

    //     $pt1 = $sortParts[0];

    //     $type = (stripos($pt1, 'fecha') !== false || stripos($pt1, 'marca') !== false) ? 'date' : 'text';

    //     $dataSort = [
    //         [
    //             'field' => $sortParts[0],
    //             'type' => $type,
    //             'direction' => $sortParts[1],
    //         ],
    //     ];

    //     foreach ($dataSort as $value) {
    //         $field = $value['field'];
    //         $direction = $value['direction'];
    //         $type = $value['type'];

    //         if ($type === "text") {
    //             $orderByText = [$field => $direction];
    //         } else {
    //             $orderByDate = [$field => $direction];
    //         }
    //     }

    //     if ($orderByText !== null) {
    //         $products->orderBy(key($orderByText), reset($orderByText));
    //     } else {
    //         $products->orderBy(DB::raw("STR_TO_DATE(" . key($orderByDate) . ", '%e/%c/%Y')"), reset($orderByDate));
    //     }
    //     // ! **************************************************
    //     $products = $products->paginate($pageSize, ['*'], 'page', $pageNumber);
    //     return response()->json($products);
    // }


    public function getProducts(Request $request)
    {
        //all for catalog
        $data = $request->json()->all();

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];

        $populate = $data['populate'];
        $outFilters = $data['out_filters'] ?? [];

        // Asumiendo que las categorías vienen en un filtro llamado 'categories' dentro de 'out_filters'
        $categoryFilters = [];
        foreach ($outFilters as $filter) {
            if (isset($filter['input_categories'])) {
                $categoryFilters = $filter['input_categories'];
                break;
            }
        }
        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        $andMap = $data['and'];

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
            ->where((function ($products) use ($andMap) {
                foreach ($andMap as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($products, $relacion, $propiedad, $valor);
                        } else {
                            $products->where($key, '=', $valor);
                        }
                    }
                }
            }))
            ->whereHas('warehouse', function ($warehouse) {
                $warehouse->where('active', 1)
                    ->where('approved', 1);
            })
            ->where('active', 1) //los No delete
            ->where('approved', 1)
            ->when(isset($data['out_filters']), function ($query) use ($data) {
                foreach ($data['out_filters'] as $filter) {
                    foreach ($filter as $key => $value) {
                        if ($key === 'price_range') {
                            $priceRange = explode('-', $value);
                            $minPrice = isset($priceRange[0]) && $priceRange[0] !== '' ? floatval($priceRange[0]) : null;
                            $maxPrice = isset($priceRange[1]) && $priceRange[1] !== '' ? floatval($priceRange[1]) : null;

                            if (!is_null($minPrice) && !is_null($maxPrice)) {
                                $query->whereBetween('price', [$minPrice, $maxPrice]);
                            } elseif (!is_null($minPrice)) {
                                $query->where('price', '>=', $minPrice);
                            } elseif (!is_null($maxPrice)) {
                                $query->where('price', '<=', $maxPrice);
                            }
                        }
                    }
                }
            })
            ->when(count($categoryFilters) > 0, function ($query) use ($categoryFilters) {
                $query->where(function ($query) use ($categoryFilters) {
                    foreach ($categoryFilters as $category) {
                        $query->orWhereRaw("JSON_CONTAINS(JSON_EXTRACT(features, '$[*].categories'), '\"$category\"')");
                    }
                });
            });            
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
        // ! ******
        $products = $products->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($products);
    }

    public function getProductsByProvider(Request $request, string $id)
    {
        //
        $data = $request->json()->all();

        $pageSize = $data['page_size'];
        $pageNumber = $data['page_number'];
        $searchTerm = $data['search'];
        $id = $id;
        $populate = $data['populate'];
        if ($searchTerm != "") {
            $filteFields = $data['or'];
        } else {
            $filteFields = [];
        }

        $andMap = $data['and'];

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
            ->where((function ($products) use ($andMap) {
                foreach ($andMap as $condition) {
                    foreach ($condition as $key => $valor) {
                        if (strpos($key, '.') !== false) {
                            $relacion = substr($key, 0, strpos($key, '.'));
                            $propiedad = substr($key, strpos($key, '.') + 1);
                            $this->recursiveWhereHas($products, $relacion, $propiedad, $valor);
                        } else {
                            $products->where($key, '=', $valor);
                        }
                    }
                }
            }))
            ->whereHas('warehouse.provider', function ($provider)use ($id) {
                $provider->where('id', '=', $id);
            })
            ->whereHas('warehouse', function ($warehouse) {
                $warehouse->where('active', 1);
            })
            ->where('active', 1);//los No delete

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
        $price = $data['price'];
        $url_img = $data['url_img'];
        $isvariable = $data['isvariable'];
        // $features = json_encode($data['features']);
        $features = $data['features'];
        $warehouse_id = $data['warehouse_id'];

        $newProduct = new Product();
        $newProduct->product_name = $product_name;
        $newProduct->stock = $stock;        $newProduct->price = $price;
        $newProduct->url_img = $url_img;
        $newProduct->isvariable = $isvariable;
        $newProduct->features = $features;
        $newProduct->warehouse_id = $warehouse_id;
        // $newProduct->approved = 2;//Pendiente


        $newProduct->save();

        return response()->json($newProduct, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        // $product = Product::with('warehouse')->findOrFail($id);
        // return response()->json($product);
        $data = $request->json()->all();
        $populate = $data['populate'];
        $product = Product::with($populate)
            ->where('product_id', $id)
            ->first();
        if (!$product) {
            return response()->json(['message' => 'No se ha encontrado un producto con el ID especificado'], 404);
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
        Product::where('product_id', $id)
            ->update(['active' => 0]);
    }
}
