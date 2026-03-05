<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Shop;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_exception_when_stock_is_insufficient()
    {
        $shop = Shop::factory()->create();

        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'stock' => 1,
            'price' => 100
        ]);

        $service = new OrderService();

        $this->expectException(ValidationException::class);

        $service->create([
            'shop_id' => $shop->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5
                ]
            ]
        ]);
    }
}