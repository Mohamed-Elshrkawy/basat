<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Http\Requests\Api\ApiMasterRequest;
use Illuminate\Validation\Rule;

class VerifyRequest extends ApiMasterRequest
{
    public function rules(): array
    {
        $user_type = request()->header('user_type');

        return  [
            'phone'         => [
                'required',
                Rule::exists('users', 'phone')
                    ->where('user_type', $user_type)
            ],
            'code'          => [
                'required',
                'digits_between:4,6',
                Rule::exists('users', 'reset_code')
                    ->where('user_type', $user_type)
                    ->where('phone', $this->phone)
            ],
            'device_token'  => 'sometimes|string',
            'type'          => 'sometimes|string|in:android,ios,huawei,web',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('Phone'),
            'code' => __('Code'),
            'device_token' => __('Device token'),
            'type' => __('Device type'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
