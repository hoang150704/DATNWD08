<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {
            DB::table('order_items')->insert([
                'order_id' => rand(1, 15),
                'product_id' => rand(1, 20),
                'variation_id' => rand(1, 20),
                'variation' => json_encode([
                    "Kích thước" => Arr::random(["S", "M", "L"]),
                    "Màu sắc" => Arr::random(["Red", "Blue", "Green"])
                ]),
                'image' => fake()->imageUrl,
                'product_name' => fake()->name,
                'quantity' => rand(0, 99),
                'price' => rand(500, 1000000),
            ]);
        }








    }
}
