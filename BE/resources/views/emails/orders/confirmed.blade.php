@component('mail::message')
# Xác nhận đơn hàng thành công

Cảm ơn bạn đã đặt hàng tại {{ config('app.name') }}.

**Mã đơn hàng:** {{ $order->code }}

Đơn hàng của bạn đã được xác nhận và đang được chuẩn bị để giao.

@component('mail::button', ['url' => url('/order-tracking/'.$order->code)])
Theo dõi đơn hàng
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
