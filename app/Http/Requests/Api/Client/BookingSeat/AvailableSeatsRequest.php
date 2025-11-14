<?php

namespace App\Http\Requests\Api\Client\BookingSeat;

use App\Http\Requests\Api\ApiMasterRequest;

class AvailableSeatsRequest extends ApiMasterRequest
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
        ];
    }


    public function attributes(): array
    {
        return [
            'schedule_id' => __('Schedule'),
            'travel_date' => __('Travel Date'),
        ];
    }
}
