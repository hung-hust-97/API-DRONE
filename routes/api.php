<?php

use App\Http\Controllers\ApiDroneController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return "dáds";
});


Route::post('login','UserController@loginPost');
Route::post('register','UserController@requestRegister');
Route::post('refreshtoken', 'UserController@refreshToken');
Route::get('/unauthorized', 'UserController@unauthorized');



Route::group(['middleware' => ['auth:api']], function() {
    Route::get('user','UserController@getUser');
    Route::post('logout', 'UserController@logout');
    Route::post('uploadimage','ApiDroneController@uploadImage');
    Route::get('flycam','ApiDroneController@getFlycamByFID');
    Route::get('flycams', 'ApiDroneController@getFlycamByOwenerID');
    Route::post('flycam/new', 'ApiDroneController@creatFlycamNew');
    Route::post('flycam/update', 'ApiDroneController@updateFLycam');
});