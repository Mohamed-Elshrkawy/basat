<?php

namespace App\Http\Resources\Api\General\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = app()->getLocale();
        $user = auth('api')->user();
        $gender = $user?->gender ?? 'male';

        return [
            'id' => $this->id,
            'icon' => $this->data['icon'] ?? asset('assets/images/logo.png'),
            'created_at' => $this->created_at?->translatedFormat('d/m/Y - h A'),
            'read_at' => $this->read_at?->translatedFormat('Y-m-d h:i A'),
            'is_readed' => !is_null($this->read_at),
            'created_time' => $this->created_at?->diffForHumans(),
            'type' => $this->data['type'] ?? null,
            'title' => $this->getLocalizedValue('title', $locale, $gender),
            'body' => $this->getLocalizedValue('body', $locale, $gender),
            'notify_id' => $this->data['notify_id'] ?? null,
            'sender_id' => $this->data['sender_id'] ?? null,
        ];
    }

    /**
     * Get localized value with proper fallback logic
     */
    private function getLocalizedValue(string $field, string $locale, string $gender): string
    {
        $value = $this->data[$field] ?? null;

        if (is_array($value)) {
            if (isset($value[$locale])) {
                $localeValue = $value[$locale];

                if (is_array($localeValue)) {
                    return $localeValue[$gender] ?? $localeValue['male'] ?? reset($localeValue);
                }

                return $localeValue;
            }

            if (isset($value['en'])) {
                $englishValue = $value['en'];

                if (is_array($englishValue)) {
                    return $englishValue[$gender] ?? $englishValue['male'] ?? reset($englishValue);
                }

                return $englishValue;
            }

            return reset($value);
        }

        return $value ?? '';
    }
}
