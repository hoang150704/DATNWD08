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
        if ($event->order->payment_status_id == 2) {
            Notification::create([
                'title' => '<span style="color: blue;"> Đơn hàng thanh toán thành công: ' . $event->order->code . '</span>',
                'message' => '<strong>Khách hàng:</strong> ' . $event->order->o_name . '<br>' .
                    '<strong>Đơn hàng trị giá:</strong> <span style="color: blue;">' . number_format($event->order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                    '<strong>Phương thức thanh toán:</strong> ' . strtoupper($event->order->payment_method) . '<br>' .
                    '<small><i>Vào lúc: ' . $event->order->created_at->format('H:i d/m/Y') . '</i></small>',
                'order_id' => $event->order->id,
                'created_at' => $event->order->created_at
            ]);
        }

        if ($event->order->cancelled_at) {
            Notification::create([
                'title' => '<span style="color: orange;"> Đơn hàng bị huỷ: ' . $event->order->code . '</span>',
                'message' => '<strong>Khách hàng:</strong> ' . $event->order->o_name . '<br>' .
                    '<strong>Đơn hàng trị giá:</strong> <span style="color: orange;">' . number_format($event->order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                    '<strong>Phương thức thanh toán:</strong> ' . strtoupper($event->order->payment_method) . '<br>' .
                    '<strong>Lý do huỷ: </strong> ' . $event->order->cancel_reason . '<br>' .
                    '<small><i>Vào lúc: ' . $event->order->cancelled_at->format('H:i d/m/Y') . '</i></small>',
                'order_id' => $event->order->id,
                'created_at' => $event->order->cancelled_at
            ]);
        } else {
            Notification::create([
                'title' => '<span style="color: green;"> Có đơn hàng mới: ' . $event->order->code . '</span>',
                'message' => '<strong>Khách hàng:</strong> ' . $event->order->o_name . '<br>' .
                    '<strong>Đơn hàng trị giá:</strong> <span style="color: green;">' . number_format($event->order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                    '<strong>Phương thức thanh toán:</strong> ' . strtoupper($event->order->payment_method) . '<br>' .
                    '<small><i>Vào lúc: ' . $event->order->created_at->format('H:i d/m/Y') . '</i></small>',
                'order_id' => $event->order->id,
                'created_at' => $event->order->created_at
            ]);
            if ($event->voucher) {
                if ($event->voucher->usage_limit == 0 || $event->voucher->usage_limit < 0) {
                    Notification::create([
                        'title' => '<span style="color: orange;"> Voucher hết lượt dùng </span>',
                        'message' => 'Voucher <strong>' . $event->voucher->code . '</strong> đã hết lượt dùng  <br>' .
                            '<small><i>Vào lúc: ' . now()->format('H:i d/m/Y') . '</i></small>',
                        'voucher_id' => $event->voucher->id,
                    ]);
                }
            }
        }
    }
}
