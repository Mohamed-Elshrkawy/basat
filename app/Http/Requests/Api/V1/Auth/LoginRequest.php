<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'user_type' => ['required', 'in:client,driver'],
            'fcm_token' => ['sometimes', 'string'],
            'device_token' => ['sometimes', 'string'],
        ];
    }
}
