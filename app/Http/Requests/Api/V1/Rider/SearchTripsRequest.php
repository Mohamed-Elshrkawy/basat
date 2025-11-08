<?php

namespace App\Http\Requests\Api\V1\Rider;

use Illuminate\Foundation\Http\FormRequest;

class SearchTripsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pickup_city_id' => ['required', 'integer', 'exists:cities,id'],
            'dropoff_city_id' => ['required', 'integer', 'exists:cities,id'],
            'trip_date' => ['required', 'date_format:Y-m-d'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i'],
        ];
    }
} 