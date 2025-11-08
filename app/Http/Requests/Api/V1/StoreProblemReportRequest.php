<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProblemReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}