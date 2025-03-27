<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\DB;

class OrderStatusFlowService
{
    // Luồng trạng thái hợp lệ
    protected const FLOW = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping' => ['completed', 'return_requested'],
        'completed' => ['closed', 'return_requested'],

        // Trả hàng
        'return_requested' => ['return_approved', 'completed'],
        'return_approved' => ['refunded'],

        // Kết thúc
        'refunded' => [],
        'closed' => [],
        'cancelled' => [],
    ];

    /**
     * Kiểm tra xem có được chuyển trạng thái không
     */
    public static function canChange(Order $order, string $toStatusCode): bool
    {
        $from = $order->status->code;

        if (!isset(self::FLOW[$from])) {
            return false;
        }
        if (
            $toStatusCode === 'confirmed' &&
            $order->payment_method === 'vnpay' &&
            ($order->paymentStatus->code ?? null) !== 'paid'
        ) {
            return false;
        }
        return in_array($toStatusCode, self::FLOW[$from]);
    }

    /**
     * Gọi đổi trạng thái (nếu hợp lệ)
     */
    public static function change(Order $order, string $toStatusCode, string $changedBy = 'system'): bool
    {
        if (!self::canChange($order, $toStatusCode)) {
            return false;
        }

        $fromStatusId = $order->order_status_id;
        $toStatusId = OrderStatus::where('code', $toStatusCode)->value('id');

        if (!$toStatusId) return false;

        $order->update([
            'order_status_id' => $toStatusId,
        ]);

        // Lưu log
        DB::table('order_status_logs')->insert([
            'order_id' => $order->id,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $toStatusId,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);

        return true;
    }
    public static function getNextStatuses(Order $order): array
    {
        $currentStatusCode = $order->status->code ?? null;
        $from = $order->status->code;
        if (!$currentStatusCode || !isset(self::FLOW[$currentStatusCode])) {
            return [];
        }


        return self::FLOW[$currentStatusCode];
    }
    public static function createInitialStatus(Order $order, string $statusCode = 'pending', string $changedBy = 'system'): void
    {
        $statusId = OrderStatus::where('code', $statusCode)->value('id');

        DB::table('order_status_logs')->insert([
            'order_id' => $order->id,
            'from_status_id' => null,
            'to_status_id' => $statusId,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
