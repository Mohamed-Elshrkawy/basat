<?php

namespace App\Http\Requests\Api\V1\Rider;

use Illuminate\Foundation\Http\FormRequest;

class SearchPrivateHireRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
            'seat_count' => ['sometimes', 'integer', 'min:1'],
            'trip_datetime' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
            'is_round_trip' => ['sometimes', 'boolean'],
            'return_datetime' => ['nullable', 'required_if:is_round_trip,true', 'date_format:Y-m-d H:i:s', 'after:trip_datetime'],
        ];
    }
} 