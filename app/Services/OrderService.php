<?php

namespace App\Services;

use App\models\Order;
use App\models\Product;
use App\Enum\OrderStatus;
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
}
