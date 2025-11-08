<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'type' => $this->type, // deposit, withdrawal, payment, refund, payout
            'description' => $this->getTranslation('description', app()->getLocale()),
            'related_trip_id' => $this->when($this->related_type === 'App\Models\Trip', $this->related_id),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
} 