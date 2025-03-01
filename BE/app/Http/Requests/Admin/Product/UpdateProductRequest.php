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
            'attributes'=>'array',
            'name' => 'required|max:255',
            'type' => 'required|in:0,1',
            'weight'=>'required|integer',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'main_image' => 'nullable|integer',
            'categories' => 'array|nullable',
            'categories.*' => 'integer',
            'images' => 'array|nullable',
            'images.*' => 'integer',
            'slug' => 'required',
            'variants' => 'array|nullable',
            'variants.*.variant_id' => 'nullable|integer',
            'variants.*.regular_price' => 'nullable|integer',
            'variants.*.sale_price' => 'nullable|integer',
            'variants.*.stock_quantity' => 'nullable|integer',
            'variants.*.sku' => 'nullable|string',
            'variants.*.values' => 'nullable|array',
            'variants.*.values.*' => 'integer',
        ];
    }
}
