<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVariationRequest extends FormRequest
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
            'variant_image' => 'nullable|integer', 
            'sku' => 'nullable|string|max:255', 
            'regular_price' => 'nullable|integer|min:0', 
            'sale_price' => 'nullable|integer|min:0|lt:regular_price', 
            'stock_quantity' => 'nullable|integer|min:0', 
            'weight'=>'required|integer|min:0',
            'values' => 'required|array|min:1', 
            'values.*.id' => 'required|integer|distinct', 
            'values.*.attribute_value_id' => 'required|integer', 
        ];
    }

}
