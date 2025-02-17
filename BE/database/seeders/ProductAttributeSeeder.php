<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('product_attributes')->insert([
            [
                'product_id' => 1,
                'attribute_id' => 1,
                'attribute_value_id' => 1
            ],
            [
                'product_id' => 1,
                'attribute_id' => 2,
                'attribute_value_id' => 7
            ],
            [
                'product_id' => 1,
                'attribute_id' => 2,
                'attribute_value_id' => 8
            ],
        ]);
    }
}
