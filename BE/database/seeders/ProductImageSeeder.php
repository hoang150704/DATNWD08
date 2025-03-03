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
                'product_id' => 1,
                'library_id' => 1
            ],
            [
                'product_id' => 1,
                'library_id' => 3
            ],
            [
                'product_id' => 1,
                'library_id' => 4
            ],
            [
                'product_id' => 1,
                'library_id' => 5
            ],
        ]);
    }
}
