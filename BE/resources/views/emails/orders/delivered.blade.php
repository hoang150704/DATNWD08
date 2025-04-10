@component('mail::message')
# Giao hàng thành công

Đơn hàng **{{ $order->code }}** của bạn đã được giao thành công.

Hy vọng bạn hài lòng với sản phẩm từ {{ config('app.name') }}.

Hãy để lại đánh giá để giúp chúng tôi phục vụ tốt hơn nhé!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
