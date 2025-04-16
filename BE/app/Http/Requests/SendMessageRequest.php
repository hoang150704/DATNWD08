<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cho phép mọi người gọi, kể cả khách
    }

    public function rules(): array
    {
        return [
            'conversation_id' => 'required|exists:conversations,id',
            'content'         => 'nullable|string|max:1000',
            'type'            => 'nullable|in:text,image,file,system',
            'guest_id'        => 'nullable|string|max:255', // nếu không đăng nhập

            // nếu có file thì dùng form-data => validate trong controller nếu cần
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'Thiếu ID hội thoại.',
            'conversation_id.exists'   => 'Hội thoại không tồn tại.',
        ];
    }
}
