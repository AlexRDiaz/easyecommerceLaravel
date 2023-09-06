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

 Route::post('pedidos-shopify/filter/logistic', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRangeLogistic']);
 Route::post('pedidos-shopify/filter', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRange']);

 
 Route::post('pedidos-shopify/products/counters', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getCounters']);
 Route::post('pedidos-shopify/products/counters/logistic', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getCountersLogistic']);

 Route::post('pedidos-shopify/routes/count', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getProductsDashboardRoutesCount']);
 Route::post('pedidos-shopify/products/values/transport', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'CalculateValuesTransport']);
 Route::post('pedidos-shopify/products/values/seller', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'CalculateValuesSeller']);

 
 Route::post('seller/invoice', [App\Http\Controllers\API\VendedoreAPIController::class, 'mybalanceVF']);

 
 
