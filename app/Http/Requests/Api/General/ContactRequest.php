<?php

namespace App\Http\Requests\Api\General;

use App\Http\Requests\Api\ApiMasterRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends ApiMasterRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = auth('sanctum')->user();

        return [
            'type' => ['required', 'in:suggestion,complaint'],
            'subject' => ['required', 'string'],
            'message' => ['required', 'string'],
            'name' => [
                'nullable',
                'string',
                Rule::requiredIf(is_null($user)),
            ],
            'phone' => [
                'nullable',
                'string',
                Rule::requiredIf(is_null($user)),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::requiredIf(is_null($user)),
            ],
        ];
    }

    public function attributes(): array
    {
        return[
            'type' => __('Type'),
            'subject' => __('Subject'),
            'message' => __('Message'),
            'name' => __('Name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
        ];

    }
}
