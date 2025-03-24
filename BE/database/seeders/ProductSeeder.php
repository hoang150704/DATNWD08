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
                'name' => "Áo thun nam",
                'description' => "Áo thun nam chất liệu cotton thoáng mát.",
                'short_description' => "Áo thun nam cotton.",
                'main_image' => 1,
                'slug' => 'ao-thun-nam',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Áo sơ mi trắng",
                'description' => "Áo sơ mi trắng công sở, thanh lịch.",
                'short_description' => "Áo sơ mi trắng công sở.",
                'main_image' => 2,
                'slug' => 'ao-so-mi-trang',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Áo khoác",
                'description' => "Áo khoác chống nước, giữ ấm tốt.",
                'short_description' => "Áo khoác chống nước.",
                'main_image' => 3,
                'slug' => 'ao-khoac',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Quần jean nam",
                'description' => "Quần jean nam phong cách trẻ trung, cá tính.",
                'short_description' => "Quần jean nam cá tính.",
                'main_image' => 4,
                'slug' => 'quan-jean-nam',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Quần tây công sở",
                'description' => "Quần tây công sở lịch lãm, sang trọng.",
                'short_description' => "Quần tây công sở.",
                'main_image' => 5,
                'slug' => 'quan-tay-cong-so',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Áo len cổ lọ",
                'description' => "Áo len cổ lọ giữ ấm tốt vào mùa đông.",
                'short_description' => "Áo len cổ lọ mùa đông.",
                'main_image' => 1,
                'slug' => 'ao-len-co-lo',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Áo blazer nam",
                'description' => "Áo blazer nam lịch lãm, phong cách.",
                'short_description' => "Áo blazer nam phong cách.",
                'main_image' => 2,
                'slug' => 'ao-blazer-nam',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Áo hoodie nữ",
                'description' => "Áo hoodie nữ phong cách trẻ trung, năng động.",
                'short_description' => "Áo hoodie nữ năng động.",
                'main_image' => 3,
                'slug' => 'ao-hoodie-nu',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Quần legging thể thao",
                'description' => "Quần legging thể thao co giãn, thoải mái.",
                'short_description' => "Quần legging thể thao.",
                'main_image' => 4,
                'slug' => 'quan-legging-the-thao',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ],
            [
                'name' => "Áo dài nữ",
                'description' => "Áo dài nữ giữ ấm tốt, thời trang.",
                'short_description' => "Áo dài nữ thời trang.",
                'main_image' => 5,
                'slug' => 'ao-da-dai-nu',
                'rating' => rand(0, 50) / 10,
                'created_at' => $this->getRandomDate()
            ]
        ]);
    }    
}
