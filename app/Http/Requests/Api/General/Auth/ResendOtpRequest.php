<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Http\Requests\Api\ApiMasterRequest;

use Illuminate\Validation\Rule;

class ResendOtpRequest extends ApiMasterRequest
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
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => __('Phone'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
