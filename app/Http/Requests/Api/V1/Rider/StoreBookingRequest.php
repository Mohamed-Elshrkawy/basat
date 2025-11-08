<?php

namespace App\Http\Requests\Api\V1\Rider;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'exists:schedules,id'],
            'trip_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'pickup_stop_id' => ['required', 'exists:stops,id'],
            'dropoff_stop_id' => ['required', 'exists:stops,id', 'different:pickup_stop_id'],
            'seat_numbers' => ['required', 'array', 'min:1'],
            'seat_numbers.*' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:wallet,cash'],
        ];
    }
} 