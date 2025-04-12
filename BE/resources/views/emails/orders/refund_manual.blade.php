@component('mail::message')
# Hoàn tiền thủ công

Chúng tôi đã xử lý hoàn tiền thủ công cho đơn hàng **{{ $order->code }}**.

**Số tiền:** {{ number_format($order->final_amount, 0, ',', '.') }} VNĐ

@if($transaction->image)
![Ảnh chuyển khoản]({{ $transaction->image }})
@endif

Nếu có vấn đề phát sinh, hãy liên hệ chúng tôi để được hỗ trợ.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
