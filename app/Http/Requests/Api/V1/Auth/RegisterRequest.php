<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'national_id' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('users', 'national_id')->where('user_type', 'client'),
            ],

            'gender' => ['required', 'in:male,female'],

            'phone' => [
                'required',
                'string',
                Rule::unique('users', 'phone')->where('user_type', 'client'),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('user_type', 'client'),
            ],

            'password' => ['required', 'string', 'min:8', 'max:16'],

            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
