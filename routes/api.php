<?php

use App\Http\Controllers\Api\OrderController;

Route::post('/orders', [OrderController::class, 'store']);
Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
Route::get('/orders', [OrderController::class, 'index']);


