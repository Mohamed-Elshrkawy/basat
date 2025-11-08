<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Http\Requests\Api\ApiMasterRequest;
use Illuminate\Validation\Rule;

class VerifyForgetPasswordRequest extends ApiMasterRequest
{
    public function rules(): array
    {
        $user_type = request()->header('user_type');

        return [
            'phone'      => [
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
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('Phone'),
            'code' => __('Code'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
