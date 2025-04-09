<?php

namespace App\Services;

class GhnStatusFlowService
{
    public static array $statusFlow = [
        // Trạng thái bắt đầu
        'ready_to_pick' => ['picking', 'cancel'],
        // Đang lấy hàng
        'picking' => ['money_collect_picking', 'picked', 'cancel'],
        'money_collect_picking' => ['picked'],
        // Hàng đã lấy
        'picked' => ['storing', 'sorting', 'transporting', 'delivering', 'delivery_fail', 'damaged', 'lost'],
        // Vận chuyển / kho  
        'sorting' => ['transporting'],
        'storing' => ['transporting', 'sorting', 'delivering', 'delivery_fail', 'damaged', 'lost'],
        'transporting' => ['storing', 'delivering', 'delivery_fail', 'damaged', 'lost'],
        // Giao hàng
        'delivering' => ['money_collect_delivering', 'delivered', 'delivery_fail', 'damaged', 'lost'],
        'money_collect_delivering' => ['delivered'],
        'delivered' => [],
        // Giao thất bại
        'delivery_fail' => ['waiting_to_return', 'storing','return', 'damaged', 'lost'],
        // Trả hàng
        'waiting_to_return' => ['return'],
        'return' => ['return_sorting', 'return_transporting', 'damaged', 'lost'],
        'return_sorting' => ['return_transporting'],
        'return_transporting' => ['returning', 'damaged', 'lost'],
        'returning' => ['returned', 'return_fail', 'damaged', 'lost'],
        'return_fail' => ['return', 'damaged', 'lost'],
        'returned' => [],
        // Ngoại lệ
        'exception' => [],
        // Hủy, hư, mấ
        'cancel' => [],
        'damaged' => [],
        'lost' => [],
    ];

    public static function canChange(string $currentStatus, string $nextStatus): bool
    {
        if (!isset(self::$statusFlow[$currentStatus])) {
            return false;
        }

        return in_array($nextStatus, self::$statusFlow[$currentStatus]);
    }
}
