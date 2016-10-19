<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', 'WelcomeController@index');

$api = app('Dingo\Api\Routing\Router');

$this->app['Dingo\Api\Transformer\Factory']->setAdapter(function ($app) {
	return new Dingo\Api\Transformer\Adapter\Fractal(new League\Fractal\Manager, 'include', ',');
});

$api->version('v1', function ($api) {
	$api->get('items/all/{appid}', 'App\Http\Controllers\Api\ItemsController@getItemsListByAppId');

	$api->get('items/all/730,570/compact', 'App\Http\Controllers\Api\ItemsController@getItemsListLoungeDestroyer');

	$api->get('items/{identifier}/{value}', 'App\Http\Controllers\Api\ItemsController@getItem')->where('identifier', '(id|name)');

	$api->get('items/{identifier}/{value}/price', 'App\Http\Controllers\Api\ItemsController@getPrice')->where('identifier', '(id|name)');

	$api->get('items/{identifier}/{value}/saleshistory', 'App\Http\Controllers\Api\ItemsHistoryController@getItemHistory')->where('identifier', '(id|name)');

	$api->get('items/{identifier}/{value}/saleshistory/{date}', 'App\Http\Controllers\Api\ItemsHistoryController@getItemHistory')->where('identifier', '(id|name)');

	$api->get('items/prices/archive', 'App\Http\Controllers\Api\PricesController@getPricesByDatesAndNames');

	$api->post('items/prices/archive', 'App\Http\Controllers\Api\PricesController@getPricesByDatesAndNames');

	$api->get('items/prices', 'App\Http\Controllers\Api\PricesController@getPrices');

	$api->post('items/prices', 'App\Http\Controllers\Api\PricesController@getPrices');

	// <editor-fold desc="deprecated routes">
	$api->get('items/history/archive', 'App\Http\Controllers\Api\PricesController@getPricesByDatesAndNames');

	$api->post('items/history/archive', 'App\Http\Controllers\Api\PricesController@getPricesByDatesAndNames');

	$api->get('prices/archive', 'App\Http\Controllers\Api\PricesController@getPrices');

	$api->post('prices/archive', 'App\Http\Controllers\Api\PricesController@getPrices');
	// </editor-fold>
});

Route::group(['middleware' => 'auth'], function () {
	Route::get('/admin', 'AdminController@index');

	Route::get('/admin/perflog/{stat}', 'AdminController@perflog');

	Route::get('/admin/odditems/{type}', 'AdminController@oddItems');

	Route::get('/admin/comparisons/{type}', 'AdminController@comparisons');
});