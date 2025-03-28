<?php

namespace App\Traits;

trait OrderTraits
{
    //Lấy subtitle
    public static function generateOrderSubtitle($order): string
    {
        $statusLogs = $order->statusLogs->sortByDesc('changed_at'); // Trạng thái đơn hàng
        $status = $order->status->code; // Mã trạng thái
        $shipping = $order->shippingStatus->code ?? null;
        $payment = $order->paymentStatus->code ?? null;
        $method = $order->payment_method;
        $lastStatusLog = $statusLogs->where('to_status_id', $order->order_status_id)->first();
        $changedBy = $lastStatusLog->changed_by ?? null;

        // Kiểm tra các trạng thái
    //     $hadReturnRequest = $statusLogs->contains(fn($log) =>
    //     $log->toStatus?->code === 'return_requested'
    // );
    
    $OrderReturnRejected = $statusLogs->contains(fn($log) =>
        $log->toStatus?->code === 'completed' &&
        $log->fromStatus?->code === 'return_requested'
    );
    

        return match (true) {
            // Đơn hàng mới tạo
            $status === 'pending' => $method === 'vnpay' && $payment === 'unpaid'
                ? 'Chờ thanh toán qua VNPAY'
                : 'Đơn hàng vừa được tạo, đang chờ xác nhận',

            // Đơn đã xác nhận
            $status === 'confirmed' => 'Đơn đã được xác nhận, đang chuẩn bị hàng',

            // Vận chuyển
            $status === 'shipping' => match ($shipping) {
                'delivering' => 'Đơn hàng đang được giao bởi GHN',
                'failed' => 'Giao hàng thất bại, đang xử lý lại',
                'returned' => 'GHN đã hoàn hàng về',
                'cancelled' => 'Vận chuyển đã huỷ',
                default => 'Đơn hàng đang trên đường vận chuyển',
            },

            // Giao thành công
            $status === 'completed' => $OrderReturnRejected
                ? 'Yêu cầu hoàn hàng đã bị từ chối'
                : 'Đơn hàng đã giao thành công',

            // Đã hoàn tất
            $status === 'closed' => 'Đơn hàng đã hoàn tất và không thể chỉnh sửa',

            // Bị huỷ
            $status === 'cancelled' => $changedBy === 'admin'
                ? 'Đơn hàng đã bị huỷ bởi admin'
                : 'Bạn đã huỷ đơn hàng',

            // Trả hàng
            $status === 'return_requested' => 'Bạn đã gửi yêu cầu hoàn hàng, đang chờ admin xử lý',

            $status === 'return_approved' => 'Admin đã đồng ý hoàn hàng, vui lòng gửi lại sản phẩm',

            $status === 'refunded' => match ($payment) {
                'refunded' => 'Đơn hàng đã hoàn tiền thành công',
                default => 'Hoàn tiền đang xử lý',
            },

            // Fallback
            default => 'Đơn hàng đang được xử lý',
        };
    }
}
