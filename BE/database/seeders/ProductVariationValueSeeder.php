<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductVariationValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('product_variation_values')->insert([
            ['variation_id'=>1,'attribute_value_id'=>1],
            ['variation_id'=>1,'attribute_value_id'=>7],
            ['variation_id'=>1,'attribute_value_id'=>1],
            ['variation_id'=>1,'attribute_value_id'=>8],
        ]);
    }
}
