<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'type' => 'in:0,1|required',
            'description' => 'nullable|string',
            'short_description' => 'nullable',
            'main_image' => "nullable|integer",
            'categories' => 'array|nullable',
            'categories.*' => 'integer',
            'images' => 'array|nullable',
            'images.*' => 'integer|nullable',
            'variants' => 'array|required',
            'variants.*.regular_price' => 'integer|nullable',
            'variants.*.sale_price' => 'integer|nullable',
            'variants.*.stock_quantity' => 'integer|nullable',
            'variants.*.sku' => 'nullable',
            'variants.*.values' => Rule::when($this->type == 0, ['required', 'array']),
            'variants.*.values.*' => 'integer',


        ];
    }
}
