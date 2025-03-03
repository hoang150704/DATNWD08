<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('attributes')->insert([
            ['name' => 'Màu sắc', 'is_default' => 0],
            ['name' => 'Kích thước', 'is_default' => 0],
        ]);
    }
}
