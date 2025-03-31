<?php

namespace App\Services;

use App\Traits\OrderTraits;

class OrderClientService
{
    use OrderTraits;

    protected function getDetailOrder($order, $isUser = true)
    {
        return
            [
                'order_id' => $order->id,
                'order_code' => $order->code,
                // Trạng thái và subtitle
                'status' => [
                    'code' => $order->status->code,
                    'name' => $order->status->name,
                    'type' => $order->status->type,
                ],
                'subtitle' => $this->generateOrderSubtitle($order),
                // Thanh toán + vận chuyển
                'payment_status' => $order->paymentStatus->name ?? null,
                'shipping_status' => $order->shippingStatus->name ?? null,
                // Thông tin số tiền
                'total_amount' => $order->total_amount,
                'final_amount' => $order->final_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_fee' => $order->shipping,
                // Thông tin người nhận
                'payment_method' => $order->payment_method,
                'o_name' => $order->o_name,
                'o_phone' => $order->o_phone,
                'o_email' => $order->o_mail,
                'o_address' => $order->o_address,
                // Sản phẩm
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'image' => $item->image,
                        'variation' => $item->variation,
                    ];
                }),
                // Lịch sử giao hàng
                'shipping_logs' => $order->shipment?->shippingLogs->map(function ($log) {
                    return [
                        'status' => $log->ghn_status,
                        'location' => $log->location,
                        'note' => $log->note,
                        'created_at' => $log->timestamp,
                    ];
                }),
                // Yêu cầu hoàn hàng (nếu có)
                'refund_requests' => $order->refundRequests->map(function ($refund) {
                    return [
                        'status' => $refund->status,
                        'reason' => $refund->reason,
                        'amount' => $refund->amount,
                        'approved_at' => optional($refund->approved_at),
                    ];
                }),
                // Timeline trạng thái
                'status_timelines' => $order->statusLogs->map(function ($log) {
                    return [
                        'from' => $log->fromStatus->name ?? null,
                        'to' => $log->toStatus->name ?? null,
                        'changed_at' => $log->changed_at,
                    ];
                }),
                // Các hành động khả dụng cho user
                'actions' => OrderActionService::availableActions($order, 'user')
            ];
    }
}
