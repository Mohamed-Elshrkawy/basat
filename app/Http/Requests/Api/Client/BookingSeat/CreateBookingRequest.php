<?php

namespace App\Http\Requests\Api\Client\BookingSeat;

use App\Http\Requests\Api\ApiMasterRequest;

class CreateBookingRequest extends ApiMasterRequest
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
     */
    public function rules(): array
    {
        return [
            'schedule_id' => 'required|exists:schedules,id',
            'travel_date' => 'required|date|after_or_equal:today',
            'trip_type' => 'required|in:one_way,round_trip',
            'number_of_seats' => 'required|integer|min:1|max:10',
            'seat_numbers' => 'required|array|min:1|max:10',
            'seat_numbers.*' => 'required|integer|min:1|max:50',
            'payment_method' => 'required|in:cash,card,wallet,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'schedule_id' => __('Schedule'),
            'travel_date' => __('Travel Date'),
            'trip_type' => __('Trip Type'),
            'number_of_seats' => __('Number of Seats'),
            'seat_numbers' => __('Seat Numbers'),
            'payment_method' => __('Payment Method'),
            'notes' => __('Notes'),
        ];
    }
}
