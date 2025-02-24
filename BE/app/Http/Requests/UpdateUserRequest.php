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
            'username'  => 'required|string|max:255|unique:users,username,' . $id,
            'email'     => 'required|string|email|max:255|unique:users,email,' . $id,
            'password'  => 'required|string|min:8|max:20|confirmed',
            'phone'     => 'nullable|string|min:10|max:15',
            'avatar'    => 'nullable|image|max:2048',
            'role_id'   => 'required|integer',
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
