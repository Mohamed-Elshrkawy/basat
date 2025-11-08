<?php

namespace App\Http\Requests\Api\V1\Rider;

use Illuminate\Foundation\Http\FormRequest;

class StorePrivateHireRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'exists:users,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'pickup_address' => ['required', 'string'],
            'dropoff_lat' => ['required', 'numeric'],
            'dropoff_lng' => ['required', 'numeric'],
            'dropoff_address' => ['required', 'string'],
            'trip_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'payment_method' => ['required', 'in:wallet,cash'],
            'amenity_ids' => ['nullable', 'string'],
            'distance' => ['nullable', 'numeric', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'], // duration in minutes
            'responsible_person_name' => ['nullable', 'string', 'max:255'],
            'responsible_person_id_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], // 5MB max
        ];
    }
} 