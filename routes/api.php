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

Route::middleware(['cors'])->group(function () {
    Route::resource('pedidos_shopifies', App\Http\Controllers\API\pedidos_shopifiesAPIController::class)
        ->except(['create', 'edit']);

    Route::resource('schemas-tests', App\Http\Controllers\API\SchemasTestAPIController::class)
        ->except(['create', 'edit']);

    Route::resource('pedidos-shopify', PedidosShopifyAPIController::class)
        ->except(['create', 'edit']);

    Route::post('pedidos-shopify/filter/logistic', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRangeLogistic']);

    //  *************************   SELLER          *****************

    Route::get('pedidos-shopifies/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getOrderbyId']);
    // updateDateandStatus
    Route::post('upd/pedidos-shopifies', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateDateandStatus']);


    // ********************************************************
    // ! ↓ REGISTRO DE PEDIDOS
    Route::post('pedidos-shopifies', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'postOrdersPricipalOrders']);
    
    // ? para la fecha del pedido
    Route::post('shopify/pedidos', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'createDateOrderLaravel']);

    // ! ↓ traer todos los pedidos Laravel
    Route::post('new-pedidos-shopifies', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getPrincipalOrdersSellersFilterLaravel']);
    // ! ↓ PEDIDOS updateOrderInternalStatus
    Route::post('updOiS/pedidos-shopifies', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateOrderInternalStatus']);

    // ! ↓ PEDIDOS updateOrderInfoSellerLaravel
    Route::post('updtOrdIS/pedidos-shopifies', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateOrderInfoSellerLaravel']);

    //  ! ↓ LA ORIGINAL
    Route::post('pedidos-shopify/filter', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRange']);

    //  ! MIA OPERATOR
    Route::post('operator/filter', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getDevolucionesOperator']);
    //  ! MIA TRANSPORTADORAS

    Route::get('transportadoras', [App\Http\Controllers\API\TransportadorasAPIController::class, 'getTransportadoras']);

    // ! MIA OPERATORESBYTRANSPORT

    Route::get('operatoresbytransport/{id}', [App\Http\Controllers\API\TransportadorasAPIController::class, 'getOperatoresbyTransport']);

    // ! MIA VENDEDORES

    Route::get('vendedores', [App\Http\Controllers\API\VendedoreAPIController::class, 'getVendedores']);

    // ! TRANSACCIONES
    Route::get("transacciones", [\App\Http\Controllers\API\TransaccionesAPIController::class,'index']);
    // ! CREDIT TRANSACTION
    Route::post("transacciones/credit", [\App\Http\Controllers\API\TransaccionesAPIController::class,'Credit']);
    // ! CREDIT TRANSACTION
    Route::post("transacciones/debit", [\App\Http\Controllers\API\TransaccionesAPIController::class,'Debit']);
    // ! ***********************

    Route::post('pedidos-shopify/filter/sellers', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getReturnSellers']);



    Route::post('pedidos-shopify/products/counters', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getCounters']);
    Route::post('pedidos-shopify/products/counters/logistic', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getCountersLogistic']);

    Route::post('pedidos-shopify/routes/count', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getProductsDashboardRoutesCount']);
    Route::post('pedidos-shopify/products/values/transport', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'CalculateValuesTransport']);
    Route::post('pedidos-shopify/products/values/seller', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'CalculateValuesSeller']);


    Route::post('seller/invoice', [App\Http\Controllers\API\VendedoreAPIController::class, 'mybalanceVF']);
});