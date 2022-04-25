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

Route::post('/users/create', 'App\Http\Controllers\UserController@create');
Route::post('/users/expense/create', 'App\Http\Controllers\UserController@createExpense');
Route::get('/users/balances', 'App\Http\Controllers\UserController@getBalances');
