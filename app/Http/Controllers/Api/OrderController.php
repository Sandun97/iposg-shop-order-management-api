<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $order = $orderService->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $order
        ], 201);
    }

    public function cancel(Order $order, OrderService $orderService): JsonResponse
    {
        $order = $orderService->cancel($order);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    
}
