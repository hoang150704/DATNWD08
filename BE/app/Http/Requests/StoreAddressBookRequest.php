<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAddressBookRequest extends FormRequest
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
            'user_id'   => 'required|integer',
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|min:10|max:15',
            'address'   => 'required|string|max:255',
            'is_active' => ['nullable', Rule::in([0, 1])]
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Bạn có thể tùy chỉnh thông báo lỗi ở đây
        $errors = $validator->errors();

        // Trả về lỗi dưới dạng JSON với mã trạng thái 422
        throw new HttpResponseException(
            response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $errors,
            ], 422)
        );
    }
}
