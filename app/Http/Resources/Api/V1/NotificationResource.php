<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $data = $this->data;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $data['title'][$locale] ?? $data['title']['en'] ?? 'Notification',
            'body' => $data['body'][$locale] ?? $data['body']['en'] ?? '',
            'related_id' => $data['trip_id'] ?? null,
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
} 