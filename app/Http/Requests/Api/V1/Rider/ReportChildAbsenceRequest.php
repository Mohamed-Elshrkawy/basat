<?php

namespace App\Http\Requests\Api\V1\Rider;

use Illuminate\Foundation\Http\FormRequest;

class ReportChildAbsenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'absence_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
} 