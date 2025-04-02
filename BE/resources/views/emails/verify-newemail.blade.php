@component('mail::message')
# Xin chào, {{ $name }}!

Bạn vừa yêu cầu thay đổi email. Vui lòng nhập mã OTP sau để xác thực email mới của bạn:

@component('mail::panel')
## {{ $otp }}
@endcomponent

Mã OTP có hiệu lực trong **5 phút**.

Nếu bạn không yêu cầu thay đổi email, vui lòng bỏ qua email này.

Trân trọng,  
**{{ config('app.name') }}**

@endcomponent
