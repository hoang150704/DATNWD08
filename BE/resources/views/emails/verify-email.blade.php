@component('mail::message')
# Xin chào, {{ $name }}!

Vui lòng nhấn vào nút bên dưới để xác thực địa chỉ email của bạn.

@component('mail::button', ['url' => $verificationUrl])
Xác Thực Email
@endcomponent

Nếu bạn không đăng ký tài khoản, vui lòng bỏ qua email này.

Trân trọng,  
**{{ config('app.name') }}**

---

Nếu bạn không thể nhấn vào nút trên, hãy sao chép đường dẫn sau và dán vào trình duyệt của bạn:

[{{ $verificationUrl }}]({{ $verificationUrl }})
@endcomponent
