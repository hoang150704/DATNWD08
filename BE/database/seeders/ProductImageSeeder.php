<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('product_images')->insert([
            [
                "product_id" => 1,
                "library_id" => 5,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "library_id" => 4,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "library_id" => 1,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "library_id" => 2,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "library_id" => 3,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 1,
                "library_id" => 6,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 11,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 12,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 10,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 9,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 7,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 8,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 2,
                "library_id" => 13,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 3,
                "library_id" => 14,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 3,
                "library_id" => 15,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 3,
                "library_id" => 16,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 3,
                "library_id" => 17,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 3,
                "library_id" => 18,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 4,
                "library_id" => 27,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 4,
                "library_id" => 26,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 4,
                "library_id" => 28,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 4,
                "library_id" => 24,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 4,
                "library_id" => 25,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "library_id" => 33,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "library_id" => 32,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "library_id" => 34,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "library_id" => 31,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "library_id" => 30,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 5,
                "library_id" => 29,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "library_id" => 40,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "library_id" => 39,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "library_id" => 38,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "library_id" => 36,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "library_id" => 35,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 6,
                "library_id" => 37,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "library_id" => 45,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "library_id" => 42,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "library_id" => 41,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "library_id" => 43,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 7,
                "library_id" => 44,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "product_id" => 8,
                "library_id" => 46,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 8,
                "library_id" => 47,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 8,
                "library_id" => 49,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 8,
                "library_id" => 48,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 8,
                "library_id" => 50,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 9,
                "library_id" => 59,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 9,
                "library_id" => 58,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 9,
                "library_id" => 57,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 9,
                "library_id" => 56,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 10,
                "library_id" => 60,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 10,
                "library_id" => 61,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 10,
                "library_id" => 62,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 10,
                "library_id" => 65,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 10,
                "library_id" => 64,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 10,
                "library_id" => 63,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 11,
                "library_id" => 66,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 11,
                "library_id" => 68,
                "created_at" => null,
                "updated_at" => null,
            ],
            [
                "product_id" => 11,
                "library_id" => 67,
                "created_at" => null,
                "updated_at" => null,
            ],
        ]);
    }
}
