<?php

namespace App\Http\Resources\Api\General\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawRequestResource extends JsonResource
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
            'transaction_id' => $this->transaction_id,
            'wallet_id' => $this->wallet_id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'IBAN' => $this->IBAN,
            'admin_note' => $this->admin_note,
            'admin' => $this->whenLoaded('admin', [
                'id' => $this->admin_details?->id,
                'name' => $this->admin_details?->name,
                'image' => $this->admin_details?->image,
                'email' => $this->admin_details?->email,
            ]),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'approved_at_format' => $this->approved_at?->translatedFormat('d M Y h:i A'),
            'rejected_at' => $this->rejected_at?->toDateTimeString(),
            'rejected_at_format' => $this->rejected_at?->translatedFormat('d M Y h:i A'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'created_at_format' => $this->created_at?->translatedFormat('d M Y h:i A'),


            'transaction' => [
                'status' => $this->transaction?->status,
                'amount' => $this->transaction?->amount,
            ],

            'wallet' => [
                'balance' => $this->wallet?->balance,
                'withdrawal_balance' => $this->wallet?->withdrawal_balance,
                'payable' => [
                    'id' => $this->wallet?->payable?->id,
                    'name' => $this->wallet?->payable?->full_name ?? $this->wallet?->payable?->name,
                    'email' => $this->wallet?->payable?->email ?? null,
                    'image' => $this->wallet?->payable?->image ?? null,
                    'type' => class_basename($this->wallet?->payable_type),
                ],
            ],
        ];
    }
}
