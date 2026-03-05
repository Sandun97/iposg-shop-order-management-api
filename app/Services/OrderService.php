<?php

namespace App\Services;

use App\models\Order;
use App\models\Product;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {

            $order = Order::create([
                'shop_id' => $data['shop_id'],
                'status' => OrderStatus::PENDING,
            ]);

            $total_amount = 0;

            foreach ($data['items'] as $item) {

                $product = Product::where('id', $item['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

                if (!$product) {
                    throw ValidationException::withMessages(['product_id' => 'Product not found']);
                }

                if ($product->stock < $item['qty']) {
                    throw ValidationException::withMessages([
                        'stock' => "Insufficient stock for product : {$product->name}"
                    ]);
                }

                $subtotal = $product->price * $item['qty']; // Calculate subtotal

                $product->decrement('stock', $item['qty']); // Reduce stock

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'qty' => $item['qty'],
                    'subtotal' => $subtotal,
                ]);

                $total_amount += $subtotal; // Accumulate total amount
            }

            $order->update([
                'total_amount' => $total_amount,
                'status' => OrderStatus::COMPLETED,
            ]);

            return $order->load('items');

        });
    }

    public function list(array $filters)
    {
        return Order::with('items')
            ->when($filters['shop_id'] ?? null, fn ($q, $shopId) =>
                $q->where('shop_id', $shopId)
            )
            ->when($filters['status'] ?? null, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->when($filters['from'] ?? null, fn ($q, $from) =>
                $q->whereDate('created_at', '>=', $from)
            )
            ->when($filters['to'] ?? null, fn ($q, $to) =>
                $q->whereDate('created_at', '<=', $to)
            )
            ->latest()
            ->paginate(10);
    }

    public function get(Order $order): Order
    {
        return $order->load('items');
    }

    public function cancel(Order $order): Order
    {        
        if ($order->status === OrderStatus::CANCELLED) {
            throw ValidationException::withMessages(['status' => 'Order already cancelled']);
        }

        if ($order->status !== OrderStatus::COMPLETED) {
            throw ValidationException::withMessages(['status' => 'Only completed orders can be cancelled']);
        }

        return DB::transaction(function () use ($order) {

            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $product->increment('stock', $item->qty); // Restore stock
            }

            $order->update(['status' => OrderStatus::CANCELLED]);

            return $order->fresh('items');
            
        });
    }
}
