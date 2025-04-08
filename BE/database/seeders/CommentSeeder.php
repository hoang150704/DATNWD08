<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $hasUser = rand(0, 1); // 50% là user đăng nhập, 50% là khách

            DB::table('product_reviews')->insert([
                'order_id' => rand(1, 5),
                'order_item_id' => rand(1, 20),
                'product_id' => rand(1, 10),
                'user_id' => $hasUser ? rand(1, 2) : null,
                'customer_name' => $hasUser ? null : fake()->name(),
                'customer_mail' => $hasUser ? null : fake()->safeEmail(),
                'rating' => rand(1, 5),
                'content' => fake()->realText(100),
                'images' => json_encode([
                    'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1742457318/prc8cyqzrpb5xvvjvclg.jpg',
                    'https://res.cloudinary.com/dkrn3fe2o/image/upload/v1742457321/gjrgnvhk3ozaef4lggma.jpg'
                ]),
                'is_active' => rand(0, 1),
                'is_updated' => rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
