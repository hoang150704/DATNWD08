@component('mail::message')
# Xác thực đơn hàng

Bạn vừa yêu cầu xác thực đơn hàng có mã **{{ $orderCode }}**.

## Mã xác thực của bạn là:

@component('mail::panel')
# {{ $otp }}
@endcomponent

Mã này có hiệu lực trong 5 phút.  
Vui lòng không chia sẻ mã này với bất kỳ ai.

Nếu bạn không yêu cầu, vui lòng bỏ qua email này.

Trân trọng,  
**SevenStyle**
@endcomponent
