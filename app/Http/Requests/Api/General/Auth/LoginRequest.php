<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Http\Requests\Api\ApiMasterRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class LoginRequest extends ApiMasterRequest
{
    public function rules(): array
    {
        $user_type = request()->header('user_type');

        return [
            'phone'         => [
                'required',
                Rule::exists('users', 'phone')
                    ->where('user_type', $user_type)
            ],
            'password'     => [
                'required',
                'string',
                Password::min(8)->max(16)
            ],
            'device_token' => 'sometimes|string',
            'type'         => 'sometimes|string|in:android,ios,huawei,web',
        ];
    }

    public function messages(): array
    {
        return parent::messages() + ['auth.exists' => __('account doest exists')];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('Phone'),
            'password' => __('Password'),
            'device_token' => __('Device Token'),
            'type' => __('Type'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
