<?php

namespace App\Services;

use App\Models\Order;

class OrderActionService
{
    public static function availableActions(Order $order, string $role = 'user'): array
    {
        $status = $order->status->code ?? null;
        $payment = $order->paymentStatus->code ?? null;
        $shipping = $order->shippingStatus->code ?? null;

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
                    if ( !$order->shipment->shipping_code) {
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
