<?php

namespace App\Http\Resources\Api\General\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'description' => $this->description[app()->getLocale()] ?? null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'is_up' => (bool) $this->is_up,
            'created_at_format' => $this->created_at?->translatedFormat('d M Y h:i A'),
        ];
    }
}
