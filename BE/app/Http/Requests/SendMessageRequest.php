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
            'guest_id'        => 'nullable|string|max:255',
            'attachments'           => 'nullable|array|max:5',
            'attachments.*.url'     => 'required|url',
            'attachments.*.type'    => 'required|in:image,file',
            'attachments.*.name'    => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'Thiếu ID hội thoại.',
            'conversation_id.exists'   => 'Hội thoại không tồn tại trong hệ thống.',
            'content.string'           => 'Nội dung tin nhắn phải là chuỗi.',
            'content.max'              => 'Nội dung tin nhắn không được vượt quá 1000 ký tự.',
            'type.in'                  => 'Loại tin nhắn không hợp lệ. Chỉ chấp nhận: text, image, file, system.',
            'guest_id.string'          => 'Mã khách phải là chuỗi.',
            'guest_id.max'             => 'Mã khách không được vượt quá 255 ký tự.',
            'attachments.array'         => 'File đính kèm phải là dạng danh sách.',
            'attachments.max'           => 'Không được gửi quá 5 file cùng lúc.',
            'attachments.*.url.required' => 'Thiếu đường dẫn file.',
            'attachments.*.url.url'      => 'File phải có đường dẫn hợp lệ.',
            'attachments.*.type.required' => 'Thiếu loại file.',
            'attachments.*.type.in'      => 'Loại file không hợp lệ (chỉ image hoặc file).',
            'attachments.*.name.string'  => 'Tên file phải là chuỗi.',
            'attachments.*.name.max'     => 'Tên file không được quá 255 ký tự.',
        ];
    }
}
