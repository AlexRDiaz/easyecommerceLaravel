<?php

use App\Http\Controllers\API\GenerateReportAPIController;
use App\Http\Controllers\API\OrdenesRetiroAPIController;
use App\Http\Controllers\API\PedidosShopifyAPIController;
use App\Http\Controllers\API\ProductAPIController;
use App\Http\Controllers\API\ProviderAPIController;
use App\Http\Controllers\API\RutaAPIController;
use App\Http\Controllers\API\TransaccionPedidoTransportadoraAPIController;
use App\Http\Controllers\API\TransportadorasShippingCostAPIController;
use App\Http\Controllers\API\UpUserAPIController;
use App\Http\Controllers\API\VendedoreAPIController;
use App\Http\Controllers\API\WarehouseAPIController;
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

    Route::post('logistic/filter/novelties', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRangeLogisticNovelties']);

    // ! update status and comment
    Route::post('logistic/update-status-comment', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateOrderStatusAndComment']);


    Route::resource('orden_retiro', App\Http\Controllers\API\OrdenesRetiroAPIController::class)
        ->except(['create', 'edit']);
    //  ************************* LOGISTIC **************************

    // ! esta es solo para ver lo que tiene registrado en cada usuario en los permisos
    Route::get('permisos', [App\Http\Controllers\API\UpUserAPIController::class, 'getPermisos']);

    // * obtiene los datos de cada rol con su id y accesos
    Route::get('access-total', [App\Http\Controllers\API\RolesFrontAPIController::class, 'index']);
    // ! accesos en base al id proporcionado
    Route::get('access-ofid/{id}', [App\Http\Controllers\API\RolesFrontAPIController::class, 'getRoleById']);

    // ! getPermissionsSellerPrincipalforNewSeller
    Route::get('sellerprincipal-for-newseller/{id}', [App\Http\Controllers\API\UpUserAPIController::class, 'getPermissionsSellerPrincipalforNewSeller']);

    Route::post('edit-personal-access', [App\Http\Controllers\API\UpUserAPIController::class, 'managePermission']);


    Route::post('new-access', [App\Http\Controllers\API\UpUserAPIController::class, 'updatePermissions']);

    // eliminacion de accesos enviando el active con false
    Route::post('dlt-rolesaccess', [App\Http\Controllers\API\UpUserAPIController::class, 'deletePermissions']);


    Route::post('upd-rolesaccess', [App\Http\Controllers\API\UpUserAPIController::class, 'newPermission']);

    // ! generate roles
    Route::get('getespc-access/{rol}', [App\Http\Controllers\API\RolesFrontAPIController::class, 'getAccesofEspecificRol']);

    // * --> PRINTEDGUIDES

    Route::post('pedidos-shopifies-prtgd', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getOrdersForPrintedGuidesLaravel']);

    Route::post('upd/pedidossho-printedg', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateOrderInteralStatusLogisticLaravel']);

    Route::post('upd/pedidossho-LogisticStatusPrint', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateOrderLogisticStatusPrintLaravel']);

    Route::post('pedido-shopifie', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getOrderByIDLaravel']);

    // * --> GUIDES_SENT

    Route::post('send-guides/printg', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getOrdersForPrintGuidesInSendGuidesPrincipalLaravel']);
    Route::post('send-guides', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getOrdersSendGuides']);
    // getOrdersSendGuides


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

    Route::post('pedidos-shopify/filter', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRange']);

    //  ! ↓ LA ORIGINAL
    Route::middleware(['jwt.auth'])->group(function () {
        // Rutas protegidas que requieren autenticación JWT

        // Agrega más rutas protegidas aquí según sea necesario
    });




    //  ! MIA OPERATOR
    Route::post('operator/filter', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getDevolucionesOperator']);
    //  ! MIA TRANSPORTADORAS

    Route::get('transportadoras', [App\Http\Controllers\API\TransportadorasAPIController::class, 'getTransportadoras']);

    // ! MIA OPERATORESBYTRANSPORT

    Route::get('operatoresbytransport/{id}', [App\Http\Controllers\API\TransportadorasAPIController::class, 'getOperatoresbyTransport']);

    // ! MIA VENDEDORES

    Route::get('vendedores', [App\Http\Controllers\API\VendedoreAPIController::class, 'getVendedores']);

    Route::post('vendedores-sld', [App\Http\Controllers\API\VendedoreAPIController::class, 'getSaldoPorId']);


    // *
    Route::put('/vendedores/{id}', [App\Http\Controllers\API\VendedoreAPIController::class, 'update']);
    Route::get('/vendedores/saldo/{id}', [VendedoreAPIController::class, 'getSaldo']);
    Route::get('/vendedores/refereds/{id}', [VendedoreAPIController::class, 'getRefereds']);


    // ! TRANSACCIONES
    Route::get("transacciones", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'index']);
    // ! LAST 30
    Route::get("transacciones-lst", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'last30rows']);
    // ! CREDIT TRANSACTION
    Route::post("transacciones/credit", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'Credit']);
    // ! CREDIT TRANSACTION
    Route::post("transacciones/debit", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'Debit']);
    // ! ***********************

    // !  TRANSACTIONS BY ID SELLER
    Route::get("transacciones/bySeller/{id}", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'getTransactionsById']);
    // ! ***********************
    // !  Rollback transactions
    Route::post("transacciones/rollback", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'rollbackTransaction']);
    // ! ***********************
    // ! GetExistTransactions
    Route::post("transacciones/exist", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'getExistTransaction']);

    // ! GetTransacctions by date
    Route::post("transacciones/by-date", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'getTransactionsByDate']);
    // ! GetTransacctions To rollback
    Route::get("transacciones/to-rollback/{id}", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'getTransactionToRollback']);

    Route::post("transacciones/cleanTransactionsFailed/{id}", [\App\Http\Controllers\API\TransaccionesAPIController::class, 'cleanTransactionsFailed']);






    Route::post('pedidos-shopify/filter/sellers', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getReturnSellers']);



    Route::post('pedidos-shopify/products/counters', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getCounters']);
    Route::post('pedidos-shopify/products/counters/logistic', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getCountersLogistic']);

    Route::post('pedidos-shopify/routes/count', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getProductsDashboardRoutesCount']);
    Route::post('pedidos-shopify/products/values/transport', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'CalculateValuesTransport']);
    Route::post('pedidos-shopify/products/values/seller', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'CalculateValuesSeller']);
    Route::post('pedidos-shopify/testChatby', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'testChatby']);


    // *
    Route::post('shopify/pedidos/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'shopifyPedidos']);


    Route::post('seller/invoice', [App\Http\Controllers\API\VendedoreAPIController::class, 'mybalanceVF']);

    Route::get('user/verifyterms/{id}', [App\Http\Controllers\API\UpUserAPIController::class, 'verifyTerms']);
    Route::put('user/updateterms/{id}', [App\Http\Controllers\API\UpUserAPIController::class, 'updateAcceptedTerms']);

    // -- wallet-ordenesretiro

    Route::get('seller/misaldo/{id}', [App\Http\Controllers\API\MiSaldoAPIController::class, 'getSaldo']);
    // *
    Route::put('pedidos-shopify/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'update']);


    Route::put('pedidos-shopify/update/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateCampo']);
    Route::put('pedidos-shopify/update/status/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateStatus']);


    //Route::resource('/users', App\Http\Controllers\API\UpUserAPIController::class);
    Route::post('/users', [UpUserAPIController::class, 'store']);
    Route::post('/users/general', [UpUserAPIController::class, 'storeGeneral']);
    Route::post('/users/providers', [App\Http\Controllers\API\UpUserAPIController::class, 'storeProvider']);
    Route::put('/users/providers/{id}', [App\Http\Controllers\API\UpUserAPIController::class, 'updateProvider']);

    Route::get('/users/subproviders/{id}/{search?}', [App\Http\Controllers\API\UpUserAPIController::class, 'getSubProviders']);
    Route::post('/users/subproviders/add', [App\Http\Controllers\API\UpUserAPIController::class, 'storeSubProvider']);
    Route::put('/users/subproviders/update/{id}', [App\Http\Controllers\API\UpUserAPIController::class, 'updateSubProvider']);


    Route::put('/users/{id}', [UpUserAPIController::class, 'update']);

    Route::get('/users/master/{id}', [UpUserAPIController::class, 'getSellerMaster']);


    Route::post('/login', [UpUserAPIController::class, 'login']);

    Route::get('users/{id}', [UpUserAPIController::class, 'users']);


    Route::get('/sellers/{id}/{search?}', [UpUserAPIController::class, 'getSellers']);

    Route::post('/report', [GenerateReportAPIController::class, 'generateExcel']);



    Route::prefix('seller/ordenesretiro')->group(function () {
        Route::get('/retiro/{id}', [OrdenesRetiroAPIController::class, 'getOrdenesRetiroNew']);
        Route::get('/ret-count/{id}', [OrdenesRetiroAPIController::class, 'getOrdenesRetiroCount']);
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

    // *
    Route::prefix('rutas')->group(function () {
        Route::get('/', [RutaAPIController::class, 'index']);
        Route::get('/active', [RutaAPIController::class, 'activeRoutes']);
        Route::get('/{id}', [RutaAPIController::class, 'show']);
    });



    // upUsersPedidos
    //testgetUserPedidos
    // getUserPedidos

    Route::get('up-user-pedidos/{id}', [UpUserAPIController::class, 'getUserPedidos']);


    // !general stats

    Route::post('data-stats-rt', [App\Http\Controllers\API\TransportStatsAPIController::class, 'fetchDataByDate3']);
    Route::post('data-stats', [App\Http\Controllers\API\TransportStatsAPIController::class, 'fetchDataByDate2']);

    // TEST ↓↓
    // Route::post('up-user-pedidos-gnral', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'generateTransportStatsTR']);




    // *
    Route::get('transportadorasbyroute/{id}', [App\Http\Controllers\API\TransportadorasAPIController::class, 'getTransportsByRoute']);
    Route::put('pedidos-shopify/updateroutetransport/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateOrderRouteAndTransport']);
    //  *
    Route::post('pedidos-shopify/filterall', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'getByDateRangeAll']);
    //  *  delete
    //  *


    Route::prefix('shippingcost')->group(function () {
        Route::get('/', [TransportadorasShippingCostAPIController::class, 'index']);
        Route::post('/', [TransportadorasShippingCostAPIController::class, 'store']);
        Route::post('/getbydate', [TransportadorasShippingCostAPIController::class, 'byDate']);
        Route::post('/{id}', [App\Http\Controllers\API\TransportadorasShippingCostAPIController::class, 'getByTransportadora']);
        Route::put('/{id}', [TransportadorasShippingCostAPIController::class, 'update']);
        Route::get('/perday', [App\Http\Controllers\API\TransportadorasShippingCostAPIController::class, 'getShippingCostPerDay']);
        Route::post('bytransportadora/{id}', [App\Http\Controllers\API\TransportadorasShippingCostAPIController::class, 'getByTransportadora']);
    });

    // 
    Route::prefix('transaccionespedidotransportadora')->group(function () {
        Route::get('/', [TransaccionPedidoTransportadoraAPIController::class, 'index']);
        Route::post('/getByDate', [TransaccionPedidoTransportadoraAPIController::class, 'getByDate']);
        Route::post('/', [TransaccionPedidoTransportadoraAPIController::class, 'store']);
        Route::put('/{id}', [TransaccionPedidoTransportadoraAPIController::class, 'update']);
        Route::post('/bydates', [TransaccionPedidoTransportadoraAPIController::class, 'getByTransportadoraDates']);
        Route::delete('/{id}', [TransaccionPedidoTransportadoraAPIController::class, 'destroy']);
    });

    Route::prefix('providers')->group(function () {

        Route::get('/all/{search?}', [ProviderAPIController::class, 'getProviders']);
    });

});

// api/upload
//Route::get('/tu-ruta', 'TuController@tuMetodo')->middleware('cors');

Route::post('upload', [App\Http\Controllers\API\TransportadorasShippingCostAPIController::class, 'uploadFile']);
//      *
Route::put('pedidos-shopify/updatefieldtime/{id}', [App\Http\Controllers\API\PedidosShopifyAPIController::class, 'updateFieldTime']);

// *
Route::prefix('warehouses')->group(function () {
    Route::get('/', [WarehouseAPIController::class, 'index']);
    Route::get('/{id}', [WarehouseAPIController::class, 'show']);
    Route::post('/', [WarehouseAPIController::class, 'store']);
    Route::put('/{id}', [WarehouseAPIController::class, 'update']);
    Route::delete('/deactivate/{id}', [WarehouseAPIController::class, 'deactivate']);
    Route::post('/activate/{id}', [WarehouseAPIController::class, 'activate']);
    Route::get('/provider/{id}', [WarehouseAPIController::class, 'filterByProvider']);

});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductAPIController::class, 'index']);
    Route::post('/all', [ProductAPIController::class, 'getProducts']);
    Route::get('/{id}', [ProductAPIController::class, 'show']);
    Route::post('/', [ProductAPIController::class, 'store']);
    Route::put('/{id}', [ProductAPIController::class, 'update']);
    Route::put('delete/{id}', [ProductAPIController::class, 'destroy']);

});




Route::resource('products', App\Http\Controllers\API\ProductAPIController::class)
    ->except(['create', 'edit']);

Route::resource('providers', App\Http\Controllers\API\ProviderAPIController::class)
    ->except(['create', 'edit']);

Route::resource('up-users-providers-links', App\Http\Controllers\API\UpUsersProvidersLinkAPIController::class)
    ->except(['create', 'edit']);

