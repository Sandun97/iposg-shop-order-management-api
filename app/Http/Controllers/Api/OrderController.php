<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use App\Http\Resources\OrderResource;

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

    public function index(OrderService $orderService)
    {
        $orders = $orderService->list(request()->only([
            'shop_id',
            'status',
            'from',
            'to'
        ]));

        return OrderResource::collection($orders);
    }
    
}
