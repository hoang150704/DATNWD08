<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class OrderClientRequest extends FormRequest
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
            'shipping' => 'required|numeric|min:0',
            'payment_method'=>'required|in:vnpay,cod',
            'o_name' => 'required|string|max:255',
            'o_address' => 'required|string|max:500',
            'o_phone' => ['required', 'regex:/^(0[1-9])[0-9]{8}$/'], // Số điện thoại VN hợp lệ
            'o_mail' => 'nullable|email|max:255',
            'note' => 'nullable|string|max:1000', // Giới hạn độ dài tránh lạm dụng
    
            // Validate danh sách sản phẩm trong đơn hàng
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variation_id' => 'nullable|exists:product_variations,id', 
            'products.*.image' => 'nullable|url', 
            'products.*.variation' => 'nullable|json', // Kiểm tra JSON hợp lệ
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.weight' => 'required|numeric|min:0', // Cải thiện kiểm tra trọng lượng
        ];
    }
    
}
