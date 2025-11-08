<?php

namespace App\Http\Requests\Api\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
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
            'fcm_token' => 'required|string|min:10|max:200',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fcm_token.required' => __('validation.required', ['attribute' => 'FCM token']),
            'fcm_token.string' => __('validation.string', ['attribute' => 'FCM token']),
            'fcm_token.min' => __('validation.min.string', ['attribute' => 'FCM token', 'min' => 10]),
            'fcm_token.max' => __('validation.max.string', ['attribute' => 'FCM token', 'max' => 200]),
        ];
    }
}
