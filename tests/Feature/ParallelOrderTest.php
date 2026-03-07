<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Shop;
use App\Models\Product;

class ParallelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_parallel_orders_do_not_create_negative_stock()
    {
        $shop = Shop::factory()->create();

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'stock' => 5,
            'price' => 100
        ]);

        $payload = [
            'shop_id' => $shop->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 3
                ]
            ]
        ];

        // Simulate two orders placed at nearly the same time
        $response1 = $this->postJson('/api/orders', $payload);
        $response2 = $this->postJson('/api/orders', $payload);

        // First order should succeed
        $this->assertTrue(
            $response1->status() === 201 || $response2->status() === 201
        );

        // One of them must fail
        $this->assertTrue(
            $response1->status() === 422 || $response2->status() === 422
        );

        // Stock should never become negative
        $product->refresh();

        $this->assertGreaterThanOrEqual(0, $product->stock);
    }
}