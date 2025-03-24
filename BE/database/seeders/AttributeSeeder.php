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
            [
                "id" => 1,
                "name" => "Màu sắc",
                "is_default" => 1,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "id" => 2,
                "name" => "Kích thước",
                "is_default" => 1,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now()
            ],
        ]);
    }
}
