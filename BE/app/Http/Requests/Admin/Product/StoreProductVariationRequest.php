<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariationRequest extends FormRequest
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
            'variant_image' => 'integer|nullable',
            'sku' => 'nullable',
            'regular_price' => 'integer|nullable',
            'sale_price' => 'integer|nullable',
            'stock_quantity' => 'integer|nullable',
            'values' => 'array|required',
            'values.*' => 'integer',
        ];
    }
}
