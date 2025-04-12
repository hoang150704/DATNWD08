@component('mail::message')
# Đơn hàng đã bị hủy

Đơn hàng **{{ $order->code }}** đã bị hủy.

**Lý do:** {{ $order->cancel_reason }}

Nếu bạn cần hỗ trợ thêm, hãy liên hệ với chúng tôi.

Thanks,<br>
{{ config('app.name') }}
@endcomponent