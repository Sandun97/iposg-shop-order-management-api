<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_order_restores_product_stock()
    {
        $shop = Shop::factory()->create();

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'stock' => 10,
            'price' => 100
        ]);

        $order = Order::create([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'total_amount' => 200
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'qty' => 2,
            'subtotal' => 200
        ]);

        $product->update([
            'stock' => 8
        ]);

        $response = $this->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled'
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 10
        ]);
    }
}