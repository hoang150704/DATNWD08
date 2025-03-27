<?php

namespace App\Services;

class ShippingStatusMapper
{
    protected const GHN_TO_SHIPPING = [
        // Đã tạo đơn
        'ready_to_pick' => 'created',
        'picking' => 'created',
        'money_collect_picking' => 'created',
        // Đã lấy hàng
        'picked' => 'picked',
        // Vận chuyển nội bộ
        'storing' => 'picked',
        'transporting' => 'picked',
        'sorting' => 'picked',
        // Đang giao
        'delivering' => 'delivering',
        'money_collect_delivering' => 'delivering',
        // Giao thành công
        'delivered' => 'delivered',
        // Giao thất bại
        'delivery_fail' => 'failed',
        // Chờ hoàn hàng/
        'waiting_to_return' => 'failed',
        'return' => 'failed',
        'return_transporting' => 'failed',
        'return_sorting' => 'failed',
        // Đang hoàn hàng
        'returning' => 'failed',
        // Đã hoàn hàng
        'returned' => 'returned',
        // Huỷ đơn
        'cancel' => 'cancelled',
        // Trạng thái đặc biệt
        'exception' => 'failed',
        'return_fail' => 'failed',
        'damage' => 'failed',
        'lost' => 'failed',
    ];

    protected const SHIPPING_TO_ORDER = [
        'not_created' => 'pending',
        'created' => 'confirmed',
        'picked' => 'shipping',
        'delivering' => 'shipping',
        'delivered' => 'completed',
        'returned' => 'return_approved',
        'failed' => 'confirmed', 
        'cancelled' => 'cancelled',
    ];
    

    public static function toShipping(string $ghnStatus): ?string
    {
        return self::GHN_TO_SHIPPING[$ghnStatus] ?? null;
    }

    public static function toOrder(string $shippingStatus): ?string
    {
        return self::SHIPPING_TO_ORDER[$shippingStatus] ?? null;
    }
}
