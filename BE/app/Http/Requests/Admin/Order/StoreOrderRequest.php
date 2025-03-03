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
            'shipping' => 'required|numeric|min:0',
            'o_name' => 'required|string|max:255',
            'o_address' => 'required|string|max:500',
            'o_phone' => ['required', 'regex:/^0[1-9][0-9]{8}$/'], // Số điện thoại Việt Nam 10 số, bắt đầu bằng 0
            'o_mail' => 'nullable|email|max:255',
            'note' => 'nullable|string',

            // Validate danh sách sản phẩm trong đơn hàng
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variation_id' => 'nullable|exists:product_variations,id', 
            'products.*.image' => 'nullable|url', 
            'products.*.variation' => 'nullable',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.weight'=>'required',
        ];
    }

}
