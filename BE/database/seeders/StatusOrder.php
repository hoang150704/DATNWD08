<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusOrder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Trạng thái hệ thông
        DB::table('order_statuses')->insert([
            ['code' => 'pending', 'name' => 'Chờ xác nhận', 'type' => 'normal'],
            ['code' => 'confirmed', 'name' => 'Đã xác nhận', 'type' => 'normal'],
            ['code' => 'shipping', 'name' => 'Đang giao', 'type' => 'normal'],
            ['code' => 'completed', 'name' => 'Đã giao thành công', 'type' => 'normal'],
            ['code' => 'closed', 'name' => 'Hoàn thành', 'type' => 'success'],
            ['code' => 'return_requested', 'name' => 'Yêu cầu trả hàng', 'type' => 'return'],
            ['code' => 'return_approved', 'name' => 'Đã duyệt trả hàng', 'type' => 'return'],
            ['code' => 'refunded', 'name' => 'Đã hoàn tiền', 'type' => 'refund'],
            ['code' => 'cancelled', 'name' => 'Đã huỷ', 'type' => 'cancel'],
        ]);
        //Trạng thái thanh toán
        DB::table('payment_statuses')->insert([
            ['code' => 'unpaid', 'name' => 'Chưa thanh toán'],
            ['code' => 'paid', 'name' => 'Đã thanh toán'],
            ['code' => 'refunded', 'name' => 'Đã hoàn tiền'],
            ['code' => 'cancelled', 'name' => 'Đã hủy'],
        ]);
        //Trạng thái GHN
        DB::table('shipping_statuses')->insert([
            ['code' => 'not_created', 'name' => 'Chưa tạo vận đơn'],
            ['code' => 'created', 'name' => 'Đã tạo vận đơn (chưa lấy)'],
            ['code' => 'picked', 'name' => 'Đã lấy hàng'],
            ['code' => 'delivering', 'name' => 'Đang giao'],
            ['code' => 'delivered', 'name' => 'Đã giao'],
            ['code' => 'returned', 'name' => 'Đã hoàn hàng'],
            ['code' => 'failed', 'name' => 'Giao thất bại'],
            ['code' => 'cancelled', 'name' => 'Vận đơn bị huỷ'],
        ]);
    }
}
