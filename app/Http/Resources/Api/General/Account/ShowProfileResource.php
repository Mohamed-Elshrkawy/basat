<?php

namespace App\Http\Resources\Api\General\Account;

use App\Enums\UserTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return match ($this->user_type) {
            UserTypeEnum::Customer->value    => $this->clientResource(),
            UserTypeEnum::Driver->value      => $this->driverResource(),
            default                          => $this->baseUserData(),
        };
    }

    /* ============================================================
     |                          Shared Helpers
     ============================================================ */

    private function baseUserData(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'gender'        => $this->gender,
            'national_id'   => $this->national_id,
            'avatar'        => $this->avatar_url,
            'is_active'     =>(bool)$this->is_active,
            'is_notify'     =>(bool)$this->is_notify,
            'locale'        =>$this->locale,
            'unread_count'  => $this->unreadNotifications()->count(),
            'token'         => $this->when(!empty($this->token), $this->token),
        ];
    }

    /* ============================================================
     |                          Resource Variants
     ============================================================ */

    private function clientResource(): array
    {
        return array_merge($this->baseUserData(), [
            'wallet_balance' => $this->balance(),
            'active_service' => [
                'enable_seat_booking'   => (bool) setting('enable_seat_booking'),
                'enable_private_bus'    => (bool) setting('enable_private_bus'),
                'enable_subscriptions'  => (bool) setting('enable_subscriptions'),
            ]
        ]);
    }

    private function driverResource(): array
    {
        return array_merge($this->baseUserData(), [
            'type' => $this->vehicle->type,
            'type_arabic' => $this->vehicle->getTypeArabic(),
            'brand' => $this->vehicle->brand->name,
            'seat_count' => $this->vehicle->seat_count,
            'plate_number' => $this->vehicle->plate_number,
            'is_active' => (bool) $this->vehicle->is_active,
        ]);
    }
}
