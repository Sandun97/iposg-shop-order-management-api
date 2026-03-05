<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;

Route::post('/orders', [OrderController::class, 'store']);
Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel']);
Route::get('/orders', [OrderController::class, 'show']);
Route::get('/orders/{order}', [OrderController::class, 'get']);
Route::get('/reports/top-products', [ReportController::class, 'topProducts']);


