<?php

namespace App\Http\Resources\Api\Client\BookingSeat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingSammaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'qr_code' => $this->qr_code_url,
            'trip_type'=>$this->trip_type,

            'outbound_stops' => [
                'boarding' => [
                    'id' => $this->outboundBoardingStop->id,
                    'name' => $this->outboundBoardingStop->stop->name,
                    'time' => $this->outboundBoardingStop->departure_time,
                    'time_formatted' => $this->outboundBoardingStop->departure_time->translatedFormat('h:i A'),
                ],
                'dropping' => [
                    'id' => $this->outboundDroppingStop->id,
                    'name' => $this->outboundDroppingStop->stop->name,
                    'time' => $this->outboundDroppingStop->arrival_time,
                    'time_formatted' => $this->outboundDroppingStop->arrival_time->translatedFormat('h:i A'),
                ],
            ],

            // معلومات المحطات للعودة
            'return_stops' => $this->trip_type === 'round_trip' ? [
                'boarding' => [
                    'id' => $this->returnBoardingStop->id,
                    'name' => $this->returnBoardingStop->stop->name,
                    'time' => $this->returnBoardingStop->departure_time,
                    'time_formatted' => $this->returnBoardingStop->departure_time->translatedFormat('h:i A'),
                ],
                'dropping' => [
                    'id' => $this->returnDroppingStop->id,
                    'name' => $this->returnDroppingStop->stop->name,
                    'time' => $this->returnDroppingStop->arrival_time,
                    'time_formatted' => $this->returnDroppingStop->arrival_time->translatedFormat('h:i A'),
                ],
            ] : null,

            'distance' => $this->schedule->distance,

            'travel_date_formatted' => $this->travel_date->translatedFormat('D, d M Y h:i A'),

            'fare' => $this->schedule->fare,
            'number_of_seats' => $this->number_of_seats,
            'total_amount' => (float) $this->total_amount,

        ];
    }
}
