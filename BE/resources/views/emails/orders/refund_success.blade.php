@component('mail::message')
# Hoàn tiền thành công

Chúng tôi đã hoàn tiền thành công cho đơn hàng **{{ $order->code }}** qua cổng thanh toán VNPay.

**Số tiền:** {{ number_format($order->final_amount, 0, ',', '.') }} VNĐ

Bạn vui lòng kiểm tra tài khoản trong vòng 3-5 ngày làm việc.

Thanks,<br>
{{ config('app.name') }}
@endcomponent