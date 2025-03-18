<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    private function getRandomDate()
    {
        return Carbon::create(2025, 2, rand(1, 28), rand(0, 23), rand(0, 59), rand(0, 59));
    }

    public function run(): void
    {
        DB::table('products')->insert([
            [
                'name' => "Tên sản phẩm 1",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 1,
                'slug' => 'ten-san-pham-1',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 2",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 4,
                'slug' => 'ten-san-pham-2',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 3",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 3,
                'slug' => 'ten-san-pham-3',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 4",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 4,
                'slug' => 'ten-san-pham-4',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 5",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 5,
                'slug' => 'ten-san-pham-5',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 6",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 1,
                'slug' => 'ten-san-pham-6',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 7",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 2,
                'slug' => 'ten-san-pham-7',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 8",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 3,
                'slug' => 'ten-san-pham-8',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 9",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 4,
                'slug' => 'ten-san-pham-9',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Tên sản phẩm 10",
                'description' => "mô tả",
                'short_description' => "mô tả ngắn",
                'main_image' => 5,
                'slug' => 'ten-san-pham-10',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ]
        ]);
    }
}
