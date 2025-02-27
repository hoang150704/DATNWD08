<?php

namespace App\Http\Requests\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'total_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'payment_method' => 'required',
            'shipping' => 'required|numeric|min:0',
            'o_name' => 'required|string|max:255',
            'o_address' => 'required|string|max:500',
            'o_phone' => 'required|regex:/^0\d{9}$/',
            'o_mail' => 'nullable|email|unique:users,email',
            // 'products' => 'required|array|min:1',
            // 'products.*.product_id' => 'required|exists:products,id',
            // 'products.*.quantity' => 'required|integer|min:1',
            // 'products.*.price' => 'required|numeric|min:0'
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'total_amount.required' => 'Tổng tiền không được để trống.',
    //         'total_amount.numeric' => 'Tổng tiền phải là số.',
    //         'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
    //         'o_phone.regex' => 'Số điện thoại không đúng định dạng (bắt đầu bằng 0, 10 số).',
    //         'products.required' => 'Bạn phải chọn ít nhất một sản phẩm.',
    //         'products.*.product_id.exists' => 'Sản phẩm không tồn tại trong hệ thống.',
    //         'products.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn 0.'
    //     ];
    // }
}
