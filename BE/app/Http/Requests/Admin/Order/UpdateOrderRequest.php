<?php

namespace App\Http\Requests\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'o_name' => 'required|string|max:255',
            'o_address' => 'required|string|max:500',
            'o_phone' => 'required|regex:/^0\d{9}$/',
            'o_mail' => 'nullable',
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'o_name.required' => 'Họ tên không được để trống',
    //         'o_name.string' => 'Họ tên phải đúng định dạng',
    //         'o_address.required' => 'Địa chỉ không được để trống',
    //         'o_address.string' => 'Địa chỉ phải đúng định dạng',
    //         'o_phone.required' => 'Số điện thoại không được để trống',
    //         'o_phone.regex' => 'Số điện thoại không đúng định dạng (bắt đầu bằng 0, 10 số).',
    //         'o_mail.email' => 'Email không đúng định dạng'
    //     ];
    // }
}
