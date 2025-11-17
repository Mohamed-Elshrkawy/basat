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
            'notes' => 'nullable|string|max:500',

            // محطات الذهاب
            'outbound_boarding_stop_id' => 'required|exists:schedule_stops,id',
            'outbound_dropping_stop_id' => 'required|exists:schedule_stops,id|different:outbound_boarding_stop_id',

            // محطات العودة (مطلوبة فقط إذا كانت الرحلة ذهاب وعودة)
            'return_boarding_stop_id' => 'required_if:trip_type,round_trip|nullable|exists:schedule_stops,id',
            'return_dropping_stop_id' => 'required_if:trip_type,round_trip|nullable|exists:schedule_stops,id|different:return_boarding_stop_id',
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
            'outbound_boarding_stop_id' => __('Outbound Boarding Stop'),
            'outbound_dropping_stop_id' => __('Outbound Dropping Stop'),
            'return_boarding_stop_id' => __('Return Boarding Stop'),
            'return_dropping_stop_id' => __('Return Dropping Stop'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'outbound_dropping_stop_id.different' => __('Dropping stop must be different from boarding stop'),
            'return_dropping_stop_id.different' => __('Dropping stop must be different from boarding stop'),
            'return_boarding_stop_id.required_if' => __('Return boarding stop is required for round trip'),
            'return_dropping_stop_id.required_if' => __('Return dropping stop is required for round trip'),
        ];
    }
}
