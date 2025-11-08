<?php

namespace App\Http\Requests\Api\V1\Rider;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolSubscriptionRequest extends FormRequest
{
    public function rules(): array {
        return [
            'package_id' => ['required', 'exists:school_packages,id'],
            'school_id' => ['required', 'exists:schools,id'],
            'children_ids' => ['required', 'array', 'min:1'],
            'children_ids.*' => ['required', 'exists:children,id'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'payment_method' => ['required', 'in:wallet,cash'],
            'pickup_lat' => ['required', 'numeric'],
            'pickup_lng' => ['required', 'numeric'],
            'pickup_address' => ['required', 'string', 'max:255'],
        ];
    }
} 