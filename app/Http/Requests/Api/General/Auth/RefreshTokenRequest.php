<?php

namespace App\Http\Requests\Api\General\Auth;

use App\Http\Requests\Api\ApiMasterRequest;
use Illuminate\Foundation\Http\FormRequest;

class RefreshTokenRequest extends ApiMasterRequest
{
    public function rules(): array
    {
        return [
            'old_device_token' => 'sometimes|nullable|string',
            'device_token'     => 'required|string',
            'type'             => 'required|string|in:android,ios'
        ];
    }

    public function attributes(): array
    {
        return [
            'old_device_token' => __('Old device token'),
            'device_token'     => __('new device token'),
            'type'             => __('device type')
        ];
    }
    public function authorize(): bool
    {
        return true;
    }
}
