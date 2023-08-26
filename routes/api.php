<?php

use App\Http\Controllers\API\PedidosShopifyAPIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::resource('pedidos_shopifies', App\Http\Controllers\API\pedidos_shopifiesAPIController::class)
    ->except(['create', 'edit']);

Route::resource('schemas-tests', App\Http\Controllers\API\SchemasTestAPIController::class)
    ->except(['create', 'edit']);

    Route::resource('pedidos-shopify', PedidosShopifyAPIController::class)
    ->except(['create', 'edit']);

 Route::post('pedidos-shopify/filter', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRange']);
 
 Route::post('pedidos-shopify/products', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getProductsDashboardLogistic']);
 Route::post('pedidos-shopify/routes/count', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getProductsDashboardRoutesCount']);
