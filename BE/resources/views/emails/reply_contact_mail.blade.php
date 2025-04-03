@component('mail::message')
# 🎯 **THƯ PHẢN HỒI TỪ SEVENSTYLE**

---

### **Xin chào {{ $name }}**,  

Chúng tôi **trân trọng** cảm ơn bạn đã liên hệ với SevenStyle!  
Dưới đây là nội dung phản hồi chi tiết:

---

## 📌 **NỘI DUNG PHẢN HỒI**  
<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
{{ $content }}
</div>

---

### 🔗 **LIÊN KẾT HỮU ÍCH**  
@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
👉 Truy cập Website SevenStyle
@endcomponent

---

### ❓ **CẦN HỖ TRỢ THÊM?**  
Nếu bạn có thắc mắc khác, vui lòng:  
📧 Email: **support@sevenstyle.com**  
☎ Hotline: **1900 123 456**  

---

**Trân trọng,**  
<strong style="color: #2d3748;">🏆 SevenStyle Team</strong>  
*"Đồng hành cùng phong cách của bạn"*
@endcomponent