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
            ['name' => 'Chờ xử lý'],
            ['name' => 'Đã xử lý'],
            ['name' => 'Đang giao hàng'],
            ['name' => 'Chưa thanh toán'],
            ['name' => 'Đã thanh toán'],
            ['name' => 'Đã hoàn thành'],
            ['name' => 'Giao hàng thất bại'],
            ['name' => 'Đã giao hàng'],
        ]);

    }
}
