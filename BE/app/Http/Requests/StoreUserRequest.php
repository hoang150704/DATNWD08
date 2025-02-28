<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Trả về true để cho phép mọi người sử dụng request này.
        // Bạn có thể thay đổi nếu cần kiểm tra quyền truy cập
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
            'name'      => 'required|string|max:255',
            'avatar'    => 'nullable|integer',
            'username'  => 'required|string|max:50|unique:users',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8|max:20|confirmed',
            'role'   => 'required',
        ];
    }

    // Phương thức này được gọi khi xác thực thất bại
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
