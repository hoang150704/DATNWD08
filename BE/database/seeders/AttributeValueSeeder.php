<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('attribute_values')->insert([
            ['name' => 'Màu đỏ', 'attribute_id' => 1],
            ['name' => 'Màu tím', 'attribute_id' => 1],
            ['name' => 'Màu vàng', 'attribute_id' => 1],
            ['name' => 'Màu trắng', 'attribute_id' => 1],
            ['name' => 'Màu đen', 'attribute_id' => 1],
            ['name' => 'Màu hồng', 'attribute_id' => 1],
            ['name' => 'S', 'attribute_id' => 2],
            ['name' => 'M', 'attribute_id' => 2],
            ['name' => 'L', 'attribute_id' => 2],
            ['name' => 'XL', 'attribute_id' => 2],
            ['name' => 'XXL', 'attribute_id' => 2],
            ['name' => 'XXXL', 'attribute_id' => 2],
        ]);
    }
}
