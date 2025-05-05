<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('categories')->insert([
            [
                "name" => "Áo",
                "slug" => "ao",
                "parent_id" => null,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Áo Polo",
                "slug" => "ao-polo",
                "parent_id" => 1.0,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Quần",
                "slug" => "quan",
                "parent_id" => null,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Quần Short",
                "slug" => "quan-short",
                "parent_id" => 3.0,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Quần Jean",
                "slug" => "quan-jean",
                "parent_id" => 3.0,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Áo Jacket",
                "slug" => "ao-jacket",
                "parent_id" => 1.0,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Áo Sơ Mi",
                "slug" => "ao-so-mi",
                "parent_id" => 1.0,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "name" => "Áo Thun",
                "slug" => "ao-thun",
                "parent_id" => 1.0,
                "deleted_at" => null,
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }
}
