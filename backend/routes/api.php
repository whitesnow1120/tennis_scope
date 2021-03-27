<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('history', 'App\Http\Controllers\TennisController@history');
Route::get('upcoming', 'App\Http\Controllers\TennisController@upComing');
Route::get('inplay', 'App\Http\Controllers\TennisController@inplay');
Route::get('relation', 'App\Http\Controllers\TennisController@relation');