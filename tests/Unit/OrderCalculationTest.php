<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OrderService;

class OrderCalculationTest extends TestCase
{
    public function test_order_total_calculation()
    {
        $items = [
            [
                'price' => 100,
                'qty' => 2
            ],
            [
                'price' => 50,
                'qty' => 3
            ]
        ];

        $total = 0;

        foreach ($items as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $this->assertEquals(350, $total);
    }
}