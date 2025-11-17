<?php

namespace App\Http\Requests\Api\Client\PrivateTrip;

use Illuminate\Foundation\Http\FormRequest;

class SearchPrivateTripRequest extends FormRequest
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
            'trip_type' => 'required|string|in:one_way,round_trip',
            'start_city_id' => 'required|exists:cities,id',
            'end_city_id' => 'required|exists:cities,id|different:start_city_id',
            'number_of_seats' => 'required|integer|min:1|max:50',
            'travel_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required_if:trip_type,round_trip|nullable|date|after:travel_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'trip_type.required' => __('Trip type is required'),
            'trip_type.in' => __('Trip type must be one_way or round_trip'),
            'start_city_id.required' => __('Start city is required'),
            'start_city_id.exists' => __('Start city does not exist'),
            'end_city_id.required' => __('End city is required'),
            'end_city_id.exists' => __('End city does not exist'),
            'end_city_id.different' => __('End city must be different from start city'),
            'number_of_seats.required' => __('Number of seats is required'),
            'number_of_seats.min' => __('Number of seats must be at least 1'),
            'number_of_seats.max' => __('Number of seats cannot exceed 50'),
            'travel_date.required' => __('Travel date is required'),
            'travel_date.after_or_equal' => __('Travel date must be today or later'),
            'return_date.required_if' => __('Return date is required for round trip'),
            'return_date.after' => __('Return date must be after travel date'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'trip_type' => __('trip type'),
            'start_city_id' => __('start city'),
            'end_city_id' => __('end city'),
            'number_of_seats' => __('number of seats'),
            'travel_date' => __('travel date'),
            'return_date' => __('return date'),
        ];
    }
}
