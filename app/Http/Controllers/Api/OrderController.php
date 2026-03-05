<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use App\Http\Resources\OrderResource;
use App\models\Order;

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

    public function show(OrderService $orderService)
    {
        $orders = $orderService->list(request()->only([
            'shop_id',
            'status',
            'from',
            'to'
        ]));

        return OrderResource::collection($orders);
    }

    public function get(Order $order, OrderService $orderService)
    {
        $order = $orderService->get($order);

        return new OrderResource($order);
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
