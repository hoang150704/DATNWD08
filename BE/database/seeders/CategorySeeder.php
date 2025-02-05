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
        //
        DB::table('categories')->insert([
            ['name' => 'Áo', 'slug' => 'ao', 'parent_id' => null],
            ['name' => 'Áo thun','slug' =>  'ao-thun', 'parent_id' => 1],
            ['name' => 'Áo sơ mi','slug' =>  'ao-so-mi', 'parent_id' => 1],
            ['name' => 'Áo sơ mi châu Âu','slug' =>  'ao-so-mi-chau-au' , 'parent_id' => 1],
            ['name' => 'Quần', 'slug' => 'quan', 'parent_id' => null],
        ]);

    }
}
