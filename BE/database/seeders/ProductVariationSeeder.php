<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductVariationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('product_variations')->insert([
            ['product_id'=>1,'sku'=>"PRD1",'variant_image'=>null,'regular_price'=>300000,'sale_price'=>199000,'stock_quantity'=>100],
            ['product_id'=>1,'sku'=>"PRD2",'variant_image'=>null,'regular_price'=>300000,'sale_price'=>189000,'stock_quantity'=>90],
            
        ]);
    }
}
