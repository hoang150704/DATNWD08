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
            ['name' => 'Màu xanh dương', 'attribute_id' => 2],
            ['name' => 'Màu xanh lá', 'attribute_id' => 2],
            ['name' => 'Màu đỏ', 'attribute_id' => 1],
            ['name' => 'Màu tím', 'attribute_id' => 1],
            ['name' => 'Màu vàng', 'attribute_id' => 1],
            ['name' => 'Màu trắng', 'attribute_id' => 1],
            ['name' => 'Màu đen', 'attribute_id' => 1],
            ['name' => 'Màu hồng', 'attribute_id' => 1],
            ['name' => 'Màu cam', 'attribute_id' => 2],
            ['name' => 'Màu nâu', 'attribute_id' => 1],
            ['name' => 'Màu bạc', 'attribute_id' => 2],
            ['name' => 'Màu xám', 'attribute_id' => 2],
        ]);
    }
}
