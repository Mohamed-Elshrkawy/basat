<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'exists:users,mobile'],
            'verification_code' => ['required', 'string', 'digits:4'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
} 