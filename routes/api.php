<?php

use App\Http\Controllers\Api\GenerateReportAPIController;
use App\Http\Controllers\API\OrdenesRetiroAPIController;
use App\Http\Controllers\API\PedidosShopifyAPIController;
use App\Http\Controllers\API\UpUserAPIController;
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
    // *
    Route::put('/vendedores/{id}', [App\Http\Controllers\API\VendedoreAPIController::class, 'update']);


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


    // *
    Route::post('orders/post/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'shopifyPedidos']);


    Route::post('seller/invoice', [App\Http\Controllers\API\VendedoreAPIController::class, 'mybalanceVF']);

    Route::get('user/verifyterms/{id}',[App\Http\Controllers\API\UpUserAPIController::class, 'verifyTerms']);
    Route::put('user/updateterms/{id}', [App\Http\Controllers\API\UpUserAPIController::class, 'updateAcceptedTerms']);

    // -- wallet-ordenesretiro

    Route::get('seller/misaldo/{id}', [App\Http\Controllers\API\MiSaldoAPIController::class, 'getSaldo']);





    //Route::resource('/users', App\Http\Controllers\API\UpUserAPIController::class);
    Route::post('/users', [UpUserAPIController::class, 'store']);
    Route::put('/users/{id}', [UpUserAPIController::class, 'update']);



    Route::post('/login', [UpUserAPIController::class, 'login']);

    Route::get('users/{id}', [UpUserAPIController::class, 'users']);

    Route::get('/sellers/{id}/{search?}', [UpUserAPIController::class, 'getSellers']);
  
    Route::post('/report', [GenerateReportAPIController::class, 'generateExcel']);


    
    Route::prefix('seller/ordenesretiro')->group(function () {
        Route::get('/retiro/{id}', [OrdenesRetiroAPIController::class, 'getOrdenesRetiroNew']);

        Route::post('/{id}', [OrdenesRetiroAPIController::class, 'getOrdenesRetiro']);
        Route::post('/withdrawal/{id}', [OrdenesRetiroAPIController::class, 'withdrawal']);
        
    });

    

    Route::prefix('generate-reports')->group(function () {
        Route::get('/', [GenerateReportAPIController::class, 'index']);
        Route::get('/{id}', [GenerateReportAPIController::class, 'show']);
        Route::post('/', [GenerateReportAPIController::class, 'store']);
        Route::put('/{id}', [GenerateReportAPIController::class, 'update']);
        Route::delete('/{id}', [GenerateReportAPIController::class, 'destroy']);
        Route::get('/seller/{id}', [GenerateReportAPIController::class, 'getBySeller']); 
    });

});



