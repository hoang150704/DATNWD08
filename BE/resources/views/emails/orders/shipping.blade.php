@component('mail::message')
# Đơn hàng đang được giao

Đơn hàng **{{ $order->code }}** của bạn đã được chuyển cho đơn vị vận chuyển.

Dự kiến giao: {{ $shipment->to_estimate_date ? $shipment->to_estimate_date->format('d/m/Y') : 'Đang cập nhật' }}

@component('mail::button', ['url' => url('/order-tracking/'.$order->code)])
Theo dõi đơn hàng
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent