<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Carbon\Carbon;

class OrderActionService
{
    public static function availableActions(Order $order, string $role = 'user'): array
    {
        $status = $order->status->code ?? null;
        $payment = $order->paymentStatus->code ?? null;
        $shipping = $order->shippingStatus->code ?? null;
        $completedLog = OrderStatusLog::where('order_id', $order->id)
            ->where('to_status_id', 4)
            ->latest('changed_at')
            ->first();
        $actions = [];

        if ($role === 'user') {
            switch ($status) {
                case 'pending':
                case 'confirmed':
                    $actions[] = 'cancel';
                    break;
                case 'completed':
                    $actions[] = 'return';
                    $actions[] = 'close';
                    break;
            }

            if ($payment === 'unpaid' && $order->payment_method === 'vnpay') {
                $actions[] = 'pay';
            }
            //XỬ lí đánh giá 
            if (
                in_array($status, ['completed', 'closed']) &&
                $completedLog &&
                Carbon::parse($completedLog->changed_at)->diffInDays(now()) <= 15
            ) {
                foreach ($order->items as $item) {
                    $review = Comment::where('order_item_id', $item->id)->first();

                    if (!$review) {
                        $actions[] = [
                            'action' => 'review',
                            'order_item_id' => $item->id,
                        ];
                    } elseif (!$review->is_updated) {
                        $actions[] = [
                            'action' => 'update_review',
                            'order_item_id' => $item->id,
                        ];
                    }
                }
            }
        }

        if ($role === 'admin') {
            switch ($status) {
                case 'pending':
                    if (!($order->payment_method === 'vnpay' && $payment === 'unpaid')) {
                        $actions[] = 'confirm'; // Xác nhận
                    }
                    $actions[] = 'cancel'; //Hủy
                    break;
                case 'confirmed':
                    if (!$order->shipment->shipping_code) {
                        $actions[] = 'ship'; // Chỉ hiện nút ship nếu chưa có shipping_code
                    } // Ship
                    $actions[] = 'cancel'; // Hủy
                    break;
                case 'return_requested':
                    $actions[] = 'approve_return'; // Đồng ý hoàn tiền
                    $actions[] = 'reject_return'; // Không đồng ý
                    break;
                case 'return_approved':
                    if ($order->payment_method === 'vnpay') {
                        $actions[] = 'refund_auto';   // Ưu tiên hoàn tự động
                        $actions[] = 'refund_manual'; // Dự phòng nếu VNPAY lỗi
                    } else {
                        $actions[] = 'refund_manual'; // Hoàn tiền tự ffoongj
                    }
                    break;
            }
            if ($shipping === 'failed') {
                $actions[] = 're_ship'; // Giao hàng lại
            }
            if ($shipping === 'returned') {
                if (!$order->shipment->return_confirmed) {
                    $actions[] = 'mark_return_received'; // Chưa xác nhận -> chỉ cho xác nhận là đã nhận đơn
                } else {
                    $actions[] = 'partial_refund'; // Đã xác nhận -> cho hoàn tiền 
                }
            }
        }

        return $actions;
    }
}
