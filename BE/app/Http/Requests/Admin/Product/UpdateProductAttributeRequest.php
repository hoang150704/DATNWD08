<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductAttributeRequest extends FormRequest
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
            'attribute.parentVariants' => 'array',
            'attribute.parentVariants.*' => 'integer',

            // Wildcard validation cho các trường thuộc tính động
            'attribute.*' => 'sometimes|array', // Kiểm tra xem các trường thuộc tính có phải là mảng hay không
            'attribute.*.*' => 'sometimes|integer', // Kiểm tra xem các giá trị trong mảng có phải là số nguyên hay không
        ];
    }
}
