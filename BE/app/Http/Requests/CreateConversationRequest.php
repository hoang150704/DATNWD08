<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
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
            'guest_id' => 'nullable|uuid',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string|max:20',
        ];
    }
}
