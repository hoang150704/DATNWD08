@component('mail::message')
# Xin chào, {{ $name }}!

Vui lòng nhấn vào nút bên dưới để đổi mật khẩu mới.

@component('mail::button', ['url' => $resetUrl])
Xác Thực Email
@endcomponent

Nếu bạn không bạn thực hiện hành động này, xin hãy liên hệ với chúng tôi.

Trân trọng,  
**{{ config('app.name') }}**

---

Nếu bạn không thể nhấn vào nút trên, hãy sao chép đường dẫn sau và dán vào trình duyệt của bạn:

[{{ $resetUrl }}]({{ $resetUrl }})
@endcomponent
