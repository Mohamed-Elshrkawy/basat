<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'national_id' => $this->national_id,
            'mobile_verified_at' => $this->mobile_verified_at ? $this->mobile_verified_at->toDateTimeString() : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'avatar_url' => $this->avatar_url ?? asset('assets/images/default-avatar.jpg'),
        ];
    }
}