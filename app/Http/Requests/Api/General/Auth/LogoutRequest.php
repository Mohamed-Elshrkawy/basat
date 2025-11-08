<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Http\Requests\Api\ApiMasterRequest;
use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends ApiMasterRequest
{
    public function rules(): array
    {
        return [
            'device_token' => 'sometimes|string',
            'type'         => 'sometimes|string|in:android,ios,huawei,web',
        ];
    }

    public function attributes()
    {
        return [
            'device_token' => __('Device token'),
            'type'         => __('Device type')
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
