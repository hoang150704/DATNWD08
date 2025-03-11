<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $id = $this->route('user')->id;
        
        return [
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $id,
            'avatar'    => 'nullable|string',
            'role'      => 'required',
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
