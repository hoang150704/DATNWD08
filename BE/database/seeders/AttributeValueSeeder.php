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
        //
        DB::table('attribute_values')->insert([
            ['name' => 'Màu đỏ', 'attribute_id'=>1],
            ['name' => 'Màu xanh', 'attribute_id'=>1],
            ['name' => 'Màu tím', 'attribute_id'=>1],
            ['name' => 'Màu violet', 'attribute_id'=>1],
            ['name' => 'Màu trắng', 'attribute_id'=>1],
            ['name' => 'Màu đen', 'attribute_id'=>1],
            ['name' => '36', 'attribute_id'=>2],
            ['name' => '37', 'attribute_id'=>2],
            ['name' => '38', 'attribute_id'=>2],
            ['name' => '39', 'attribute_id'=>2],
            ['name' => '40', 'attribute_id'=>2],
            ['name' => '41', 'attribute_id'=>2],
        ]);
    }
}
