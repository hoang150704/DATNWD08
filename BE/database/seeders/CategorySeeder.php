<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $count = 1;

        for ($i = 0; $i < 100; $i++) {
            Category::create([
                'name' => 'Danh mục ' . $i,
                'slug' => 'danh-muc-' . $count++,
                'parent_id' => null,
            ]);
        }
    }
}