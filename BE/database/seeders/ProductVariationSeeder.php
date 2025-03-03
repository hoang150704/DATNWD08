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
        DB::table('product_variations')->insert([
            ['product_id' => 1, 'sku' => "PRD1", 'variant_image' => null, 'regular_price' => 300000, 'sale_price' => 199000, 'stock_quantity' => 100],
            ['product_id' => 1, 'sku' => "PRD2", 'variant_image' => null, 'regular_price' => 300000, 'sale_price' => 189000, 'stock_quantity' => 90],
            ['product_id' => 2, 'sku' => "PRD3", 'variant_image' => null, 'regular_price' => 350000, 'sale_price' => 249000, 'stock_quantity' => 80],
            ['product_id' => 2, 'sku' => "PRD4", 'variant_image' => null, 'regular_price' => 350000, 'sale_price' => 239000, 'stock_quantity' => 75],
            ['product_id' => 3, 'sku' => "PRD5", 'variant_image' => null, 'regular_price' => 400000, 'sale_price' => 299000, 'stock_quantity' => 60],
            ['product_id' => 3, 'sku' => "PRD6", 'variant_image' => null, 'regular_price' => 400000, 'sale_price' => 289000, 'stock_quantity' => 55],
            ['product_id' => 4, 'sku' => "PRD7", 'variant_image' => null, 'regular_price' => 450000, 'sale_price' => 349000, 'stock_quantity' => 50],
            ['product_id' => 4, 'sku' => "PRD8", 'variant_image' => null, 'regular_price' => 450000, 'sale_price' => 339000, 'stock_quantity' => 45],
            ['product_id' => 5, 'sku' => "PRD9", 'variant_image' => null, 'regular_price' => 500000, 'sale_price' => 399000, 'stock_quantity' => 40],
            ['product_id' => 5, 'sku' => "PRD10", 'variant_image' => null, 'regular_price' => 500000, 'sale_price' => 389000, 'stock_quantity' => 35],
        ]);        
    }
}
