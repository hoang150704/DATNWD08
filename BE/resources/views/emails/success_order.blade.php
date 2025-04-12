@component('mail::message')
# Xác nhận đơn hàng

Xin chào **{{ $order->o_name }}**,

Cảm ơn bạn đã đặt hàng tại cửa hàng của chúng tôi. Dưới đây là thông tin đơn hàng của bạn:

## Thông tin đơn hàng
- **Mã đơn hàng:** {{ $order->code }}
- **Ngày đặt hàng:** {{ $order->created_at->format('d/m/Y H:i') }}
- **Địa chỉ giao hàng:** {{ $order->o_address }}
- **Số điện thoại:** {{ $order->o_phone }}
- **Phương thức thanh toán:** {{ $order->payment_method }}
- **Trạng thái thanh toán (ID):** {{ $order->payment_status_id }}
- **Trạng thái đơn hàng (ID):** {{ $order->order_status_id ?? 'N/A' }}
- **Tổng tiền:** **{{ number_format($order->final_amount, 0, ',', '.') }} VNĐ**

## Danh sách sản phẩm:
@component('mail::table')
| Sản phẩm      | Giá                 | Số lượng | Tổng                |
|--------------|--------------------|---------|---------------------|
@foreach($orderItems as $item)
| **{{ $item->product_name }}** | {{ number_format($item->price, 0, ',', '.') }} VNĐ | {{ $item->quantity }} | **{{ number_format($item->price * $item->quantity, 0, ',', '.') }} VNĐ** |
@endforeach
@endcomponent

Chúng tôi sẽ sớm liên hệ để xác nhận và tiến hành xử lý đơn hàng.

Trân trọng,  
**SevenStyle**
@endcomponent
