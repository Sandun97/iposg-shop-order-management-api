<?php

use App\Http\Controllers\Api\OrderController;

Route::post('/orders', [OrderController::class, 'store']);


