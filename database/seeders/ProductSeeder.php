<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            [
                'shop_id' => 1,
                'name' => 'Milk Powder',
                'price' => 750,
                'stock' => 50,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'shop_id' => 1,
                'name' => 'Tea Packet',
                'price' => 200,
                'stock' => 100,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'shop_id' => 2,
                'name' => 'Sugar',
                'price' => 150,
                'stock' => 80,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
