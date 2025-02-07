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
    public function run(): void
    {
        //
        DB::table('product_category_relations')->insert([
            [  'product_id'=>1,
            'category_id'=>1],
            [  'product_id'=>1,
            'category_id'=>2],
        ]);
    }
}
