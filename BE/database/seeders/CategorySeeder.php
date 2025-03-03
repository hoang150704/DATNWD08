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
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Danh mục 1', 'slug' => 'danh-muc-1', 'parent_id' => null],
            ['name' => 'Danh mục 2', 'slug' => 'danh-muc-2', 'parent_id' => 1],
            ['name' => 'Danh mục 3', 'slug' => 'danh-muc-3', 'parent_id' => 1],
            ['name' => 'Danh mục 4', 'slug' => 'danh-muc-4', 'parent_id' => 1],
            ['name' => 'Danh mục 5', 'slug' => 'danh-muc-5', 'parent_id' => 1],
        ]);
    }
}
