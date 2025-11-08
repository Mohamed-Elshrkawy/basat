<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Helpers\Helpers;
use App\Http\Requests\Api\ApiMasterRequest;
use App\Services\General\AuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends ApiMasterRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user_type = request()->header('user_type');

        return [
            'phone'         => [
                'required',
                Rule::exists('users', 'phone')
                    ->where('user_type', $user_type)
            ],
            'code'       => [
                'required',
                'digits_between:4,6',
                Rule::exists('users', 'reset_code')
                    ->where('user_type', $user_type)
                    ->where('phone', $this->phone)
            ],
            'password'   => ['sometimes', 'nullable', 'string', Password::min(8)->max(16)],
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('Phone'),
            'code' => __('Code'),
            'password' => __('Password'),
        ];
    }

    public function messages(): array
    {
        return [
            'password.min'              => __('The password must be at least 8 characters.'),
            'password.mixed'            => __('The password must contain at least one uppercase letter and one lowercase letter.'),
            'password.symbols'          => __('The password must contain at least one special character.'),
        ];
    }

}
