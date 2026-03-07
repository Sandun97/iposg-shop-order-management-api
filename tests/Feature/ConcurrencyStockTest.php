<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Shop;
use App\Models\Product;

class ConcurrencyStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_never_becomes_negative_when_multiple_orders_are_created()
    {
        $shop = Shop::factory()->create();

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'stock' => 2,
            'price' => 100
        ]);

        $payload = [
            'shop_id' => $shop->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2
                ]
            ]
        ];

        // First order should succeed
        $response1 = $this->postJson('/api/orders', $payload);
        $response1->assertStatus(201);

        // Second order should fail due to insufficient stock
        $response2 = $this->postJson('/api/orders', $payload);
        $response2->assertStatus(422);

        // Stock should never go negative
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 0
        ]);
    }
}