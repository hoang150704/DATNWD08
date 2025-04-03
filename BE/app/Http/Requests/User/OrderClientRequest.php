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
            // Thông tin khách hàng
            'o_name' => 'required|string|max:255',
            'o_mail' => 'nullable|email|max:255',
            'o_phone' => ['required', 'regex:/^(0[1-9]{1}[0-9]{8})$/'],
            'o_address' => 'required|string|max:500',

            // Thông tin đơn hàng
            'payment_method' => 'required|in:vnpay,ship_cod',
            'discount_amount' => 'nullable|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'shipping' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',

            // Danh sách sản phẩm
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:product_variations,id',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.name' => 'required|string|max:255',
            'products.*.weight' => 'required|numeric|min:0',
            'products.*.image_url' => 'nullable|url',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.regular_price' => 'required|numeric|min:0',
            'products.*.sale_price' => 'nullable|numeric|min:0',
            //
            'time.from_estimate_date' => 'nullable',
            'time.to_estimate_date' => 'nullable',

        ];
    }
}
