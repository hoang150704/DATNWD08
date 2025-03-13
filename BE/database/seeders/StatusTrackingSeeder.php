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
            ['name' => 'Chờ xác nhận', 'next_status_allowed' => json_encode(['2', '10'])],
            ['name' => 'Đã xác nhận', 'next_status_allowed' => json_encode(['3', '10'])],
            ['name' => 'Đang vận chuyển', 'next_status_allowed' => json_encode(['4', '6'])],
            ['name' => 'Đã giao hàng', 'next_status_allowed' => json_encode(['5'])],
            ['name' => 'Hoàn thành', 'next_status_allowed' => json_encode([])],
            ['name' => 'Giao hàng thất bại', 'next_status_allowed' => json_encode(['7', '10'])],
            ['name' => 'Yêu cầu hoàn tiền', 'next_status_allowed' => json_encode(['8'])],
            ['name' => 'Xử lý yêu cầu hoàn tiền', 'next_status_allowed' => json_encode(['9'])],
            ['name' => 'Đã trả hàng', 'next_status_allowed' => json_encode(['5'])],
            ['name' => 'Hủy', 'next_status_allowed' => json_encode([])],
        ]);
        
    }
}
