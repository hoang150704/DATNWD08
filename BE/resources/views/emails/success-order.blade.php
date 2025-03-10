@component('mail::message')
# Xác nhận đơn hàng

Xin chào **{{ $order->o_name }}**,

Cảm ơn bạn đã đặt hàng tại cửa hàng của chúng tôi. Dưới đây là thông tin đơn hàng của bạn:

## Thông tin đơn hàng
- **Mã đơn hàng:** {{ $order->code }}
- **Địa chỉ giao hàng:** {{ $order->o_address }}
- **Số điện thoại:** {{ $order->o_phone }}
- **Phương thức thanh toán:** {{ $order->payment_method }}
- **Tổng tiền:** **{{ number_format($order->final_amount, 0, ',', '.') }} VNĐ**

## Danh sách sản phẩm:
@component('mail::table')
| Sản phẩm      | Giá                 | Số lượng | Tổng                |
|--------------|--------------------|---------|---------------------|
@foreach($orderItems as $item)
| **{{ $item->product_name }}** | {{ number_format($item->price, 0, ',', '.') }} VNĐ | {{ $item->quantity }} | **{{ number_format($item->price * $item->quantity, 0, ',', '.') }} VNĐ** |
@endforeach
@endcomponent

Chúng tôi sẽ sớm liên hệ để xác nhận đơn hàng và tiến hành giao hàng.

Trân trọng,  
**Đội ngũ Shine Light Việt Nam**
@endcomponent
