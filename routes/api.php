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

Route::resource('orders', App\Http\Controllers\OrderController::class);
Route::put('/orders/{order_id}/add', [App\Http\Controllers\OrderController::class, 'update']);
Route::post('/orders/{order_id}/pay', [App\Http\Controllers\OrderController::class, 'pay']);
