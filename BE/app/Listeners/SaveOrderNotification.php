<?php

namespace App\Listeners;

use App\Events\CancelOrderEvent;
use App\Events\OrderEvent;
use App\Events\VoucherEvent;
use App\Models\Notification;
use App\Models\Voucher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        if ($event->order->cancelled_at) {
            Notification::create([
                'title' => 'Đơn hàng bị huỷ: ' . $event->order->code,
                'message' => 'Đơn hàng trị giá ' . $event->order->final_amount . ' đã bị huỷ',
                'created_at' => $event->order->cancelled_at
            ]);
        } else {
            Notification::create([
                'title' => 'Có đơn hàng mới: ' . $event->order->code,
                'message' => '<strong>Khách hàng:</strong> ' . $event->order->o_name . '<br>' .
                    '<strong>Đơn hàng trị giá:</strong> <span style="color: green;">' . number_format($event->order->final_amount, 0, ',', '.') . 'đ</span><br>' .
                    '<strong>Phương thức thanh toán:</strong> ' . strtoupper($event->order->payment_method) . '<br>' .
                    '<small><i>Vào lúc: ' . $event->order->created_at->format('H:i d/m/Y') . '</i></small>',
                'created_at' => $event->order->created_at
            ]);
            if ($event->voucher) {
                if ($event->voucher->usage_limit == 0 || $event->voucher->usage_limit < 0) {
                    Notification::create([
                        'title' => 'Voucher hết lượt dùng: ' . $event->voucher->code,
                        'message' => 'Voucher ' . $event->voucher->code . ' đã hết lượt dùng',
                        'created_at' => $event->order->created_at
                    ]);
                }
            }
        }
    }
}
