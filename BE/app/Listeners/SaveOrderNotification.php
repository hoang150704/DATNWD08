<?php

namespace App\Listeners;

use App\Events\CancelOrderEvent;
use App\Events\OrderEvent;
use App\Events\VoucherEvent;
use App\Models\Notification;
use App\Models\Voucher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SaveOrderNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderEvent $event): void
    {
        $order = $event->order;
        $voucher = $event->voucher;

        // Nếu đơn hàng bị huỷ
        if ($order->cancelled_at) {
            Notification::create([
                'title' => '<span style="color: red;"> Đơn hàng bị huỷ: ' . $order->code . '</span>',
                'message' => '<strong>Khách hàng:</strong> ' . $order->o_name . '<br>' .
                    '<strong>Đơn hàng trị giá:</strong> <span style="color: red;">' . number_format($order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                    '<strong>Phương thức thanh toán:</strong> ' . strtoupper($order->payment_method) . '<br>' .
                    '<strong>Lý do huỷ:</strong> ' . $order->cancel_reason . '<br>' .
                    '<small><i>Vào lúc: ' . $order->cancelled_at->format('H:i d/m/Y') . '</i></small>',
                'order_id' => $order->id,
                'created_at' => $order->cancelled_at
            ]);
            return; // Chạy vào đây thì dừng hàm 😁
        }

        // Nếu đơn hàng thanh toán thành công
        if ($order->payment_status_id == 2) {
            Notification::create([
                'title' => '<span style="color: blue;"> Đơn hàng thanh toán thành công: ' . $order->code . '</span>',
                'message' => '<strong>Khách hàng:</strong> ' . $order->o_name . '<br>' .
                    '<strong>Đơn hàng trị giá:</strong> <span style="color: blue;">' . number_format($order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                    '<strong>Phương thức thanh toán:</strong> ' . strtoupper($order->payment_method) . '<br>' .
                    '<small><i>Vào lúc: ' . $order->created_at->format('H:i d/m/Y') . '</i></small>',
                'order_id' => $order->id,
                'created_at' => $order->created_at
            ]);
            return; // Chạy vào đây thì dừng hàm 😁
        }

        // Đơn hàng mới auto
        Notification::create([
            'title' => '<span style="color: green;"> Có đơn hàng mới: ' . $order->code . '</span>',
            'message' => '<strong>Khách hàng:</strong> ' . $order->o_name . '<br>' .
                '<strong>Đơn hàng trị giá:</strong> <span style="color: green;">' . number_format($order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                '<strong>Phương thức thanh toán:</strong> ' . strtoupper($order->payment_method) . '<br>' .
                '<small><i>Vào lúc: ' . $order->created_at->format('H:i d/m/Y') . '</i></small>',
            'order_id' => $order->id,
            'created_at' => $order->created_at
        ]);

        // Nếu có voucher hết lượt dùng
        if ($voucher && ($voucher->usage_limit <= 0)) {
            Notification::create([
                'title' => '<span style="color: orange;"> Voucher hết lượt dùng </span>',
                'message' => 'Voucher <strong>' . $voucher->code . '</strong> đã hết lượt dùng<br>' .
                    '<small><i>Vào lúc: ' . now()->format('H:i d/m/Y') . '</i></small>',
                'voucher_id' => $voucher->id,
            ]);
        }
    }
}
