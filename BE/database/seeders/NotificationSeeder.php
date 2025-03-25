<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nette\Utils\Random;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titles = ['Đơn hàng mới', 'Thay đổi trạng thái đơn hàng', 'Chuyển khoản thành công', 'Voucher hết lượt dùng'];
        for ($i = 0; $i < 15; $i++) {
            DB::table('notifications')->insert([
                'title' => $titles[array_rand($titles)],
                'message' => fake()->text(20),
            ]);
        }
    }
}
