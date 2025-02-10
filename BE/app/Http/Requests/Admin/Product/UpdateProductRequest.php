<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            //
            'name' => 'required|max:255',
            'type' => 'in:0,1|required',
            'slug'=>'required|max:255',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'main_image'=>'nullable|integer',
            'categories'=>'array|nullable',
            'categories.*'=>'integer',
            'images'=>'array|nullable',
            'images.*'=>'integer',
            'variants'=>'array',
            'variants.*.regular_price'=>'integer|nullable',
            'variants.*.sale_price'=>'integer|nullable',
            'variants.*.stock_quantity'=>'integer|nullable',
            'variants.*.variant_image'=>'integer|nullable',
            'variants.*.sku'=>'nullable',
            'variants.*.variant_id'=>'integer|nullable',
            'variants.*.values'=>'array|nullable',
        ];
    }
}
