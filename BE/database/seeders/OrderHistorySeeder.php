<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 10; $i++) {
            DB::table('order_histories')->insert([
                'order_id' => rand(1, 10),
                'type' => collect(['tracking', 'paid'])->random(),
                'status_id' => rand(1, 8),
            ]);
        }
    }
}
