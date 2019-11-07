<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Business
Route::post('/api/business/login','BusinessController@login');
Route::post('/api/business/register','BusinessController@register');
Route::put('/api/business/update','BusinessController@update');
Route::post('/api/business/upload','BusinessController@upload');
Route::get('/api/business/getimage/{filename}','BusinessController@getImage'); // business profile image
Route::post('/api/business/info','BusinessController@info');
Route::post('/api/business/new/bank','BusinessController@newBank');
Route::put('/api/business/update/bank','BusinessController@updateBank');
Route::post('/api/business/get/bank','BusinessController@getBank');
Route::post('/api/business/new/subscription','BusinessController@newSubscription');
Route::post('/api/business/get/subscription','BusinessController@getSubscription');
Route::post('/api/business/cancel/subscription','BusinessController@cancelSubscription');
Route::put('/api/business/renew/subscription','BusinessController@renewSubscription');
Route::post('/api/business/get/deliveries/availible','BusinessController@getDeliveriesAvailible');
Route::post('/api/business/contact/delivery','BusinessController@contactDelivery');

Route::post('/api/business/new/product','ProductsController@newProduct');
Route::put('/api/business/update/product','ProductsController@update');
Route::post('/api/business/upload/product','ProductsController@upload');
Route::get('/api/business/getimage/product/{filename}','ProductsController@getImage'); // product image
Route::get('/api/business/get/product/{id}','ProductsController@getProductById'); // product image


//Delivery
Route::post('/api/delivery/login','DeliveryController@login');
Route::post('/api/delivery/register','DeliveryController@register');
Route::put('/api/delivery/update','DeliveryController@update');
Route::post('/api/delivery/upload','DeliveryController@upload');
Route::get('/api/delivery/getimage/{filename}','DeliveryController@getImage'); // business profile image
Route::post('/api/delivery/info','DeliveryController@info');
Route::post('/api/delivery/new/bank','DeliveryController@newBank');
Route::put('/api/delivery/update/bank','DeliveryController@updateBank');
Route::post('/api/delivery/get/bank','DeliveryController@getBank');
Route::put('/api/delivery/update/request','DeliveryController@updateDeliveryRequest');
Route::post('/api/delivery/get/order','DeliveryController@getOrder');
Route::put('/api/delivery/take/order','DeliveryController@takeOrder');
Route::put('/api/delivery/arrived/on/business','DeliveryController@deliveryArrivedOnBusinessPlace');
Route::put('/api/delivery/on/way/to/customer','DeliveryController@deliveryOnWayToCustomerPlace');
Route::put('/api/delivery/arrived/on/customer','DeliveryController@deliveryArrivedOnCustomerPlace');


//User
Route::post('/api/user/getuser','UserController@getUser');
Route::post('/api/user/login','UserController@login');
Route::post('/api/user/register','UserController@register');
Route::put('/api/user/update','UserController@update');
Route::post('/api/user/upload','UserController@upload');
Route::get('/api/user/getimage/{filename}','UserController@getImage'); // business profile image
Route::post('/api/user/info','UserController@info');
Route::post('/api/user/new/location','UserController@newLocation');
Route::put('/api/user/update/location','UserController@updateLocation');
Route::post('/api/user/get/locations','UserController@getLocations');
Route::put('/api/user/update/order','UserController@updateOrder');
Route::post('/api/user/like/product','UserController@likeProduct');
Route::post('/api/user/unlike/product','UserController@unlikeProduct');
Route::post('/api/user/like/business','UserController@likeBusiness');
Route::post('/api/user/unlike/business','UserController@unlikeBusiness');
Route::post('/api/user/like/delivery','UserController@likeDelivery');
Route::post('/api/user/unlike/delivery','UserController@unlikeDelivery');













