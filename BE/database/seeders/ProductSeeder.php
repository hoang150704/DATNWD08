<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('products')->insert([
            ['name' => "Áo Thun Nam Họa Tiết In Excursion Mighty Bear Form Regular", 'description' => "Áo thun xịn của mình",'weight'=>500 ,'short_description' => 'Áo thun xịn của mình', 'main_image' => 2, 'slug' => 'ao-thun-nam-hoa-tiet-in-excursion-mighty-bear-form-regular'],
        ]);
    }
}
