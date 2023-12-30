<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/shopify/webhooks/customer_data_request', 'ShopifyWebhookController@handleCustomerDataRequest');
Route::post('/shopify/webhooks/customer_redact', 'ShopifyWebhookController@handleCustomerRedact');
Route::post('/shopify/webhooks/shop_redact', 'ShopifyWebhookController@handleShopRedact');
