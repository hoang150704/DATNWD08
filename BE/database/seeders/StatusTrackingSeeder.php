<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusTrackingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('status_trackings')->insert([
            ['name' => 'Chờ xử lý', 'next_status_allowed' => json_encode(['2', '7'])],
            ['name' => 'Đã xử lý', 'next_status_allowed' => json_encode(['3', '7'])],
            ['name' => 'Đang giao hàng', 'next_status_allowed' => json_encode(['5', '6', '7'])],
            ['name' => 'Đã hoàn thành', 'next_status_allowed' => json_encode([])],
            ['name' => 'Giao hàng thất bại', 'next_status_allowed' => json_encode(['3', '7'])],
            ['name' => 'Đã giao hàng', 'next_status_allowed' => json_encode(['4'])],
            ['name' => 'Huỷ', 'next_status_allowed' => json_encode([])],
        ]);
    }
}
