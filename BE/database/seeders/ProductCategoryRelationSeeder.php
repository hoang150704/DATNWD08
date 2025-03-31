<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategoryRelationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('product_category_relations')->insert([
            [
                "product_id" => 1,
                "category_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "category_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "category_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "category_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 3,
                "category_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 3,
                "category_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 4,
                "category_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 4,
                "category_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "category_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "category_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "category_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "category_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "category_id" => 3,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "category_id" => 4,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 8,
                "category_id" => 3,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 8,
                "category_id" => 4,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 9,
                "category_id" => 3,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 9,
                "category_id" => 4,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 10,
                "category_id" => 3,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 10,
                "category_id" => 4,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 11,
                "category_id" => 3,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 11,
                "category_id" => 4,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }
}
