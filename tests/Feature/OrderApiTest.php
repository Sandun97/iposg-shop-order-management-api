<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Shop;
use App\Models\Product;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_and_stock_is_reduced()
    {
        $shop = Shop::factory()->create();

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'stock' => 25,
            'price' => 200
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 3
                ]
            ]
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'shop_id' => $shop->id
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'qty' => 3
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 22
        ]);
    }
}