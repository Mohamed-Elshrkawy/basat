<?php

namespace App\Http\Resources\Api\General\Account;

use App\Enums\UserTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return match ($this->user_type) {
            UserTypeEnum::Client->value    => $this->clientResource(),
            UserTypeEnum::Driver->value    => $this->driverResource(),
            default                        => $this->baseUserData(),
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
            'avatar'        => $this->avatar,
            'is_active'     =>(bool)$this->is_active,
            'is_notify'     =>(bool)$this->is_notify,
            'locale'        =>$this->locale,
            'token'         => $this->when(!empty($this->token), $this->token),
        ];
    }

    /* ============================================================
     |                          Resource Variants
     ============================================================ */

    private function clientResource(): array
    {
        return array_merge($this->baseUserData(), [
            //
        ]);
    }

    private function driverResource(): array
    {
        return array_merge($this->baseUserData(), [
            //
        ]);
    }
}
