<?php

namespace App\Services\PushNotification;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;

class SendByRedis
{
    private const REDIS_CHANNEL = '9_channel';
    private const DEFAULT_LOCALE = 'ar';

    public function send($notifiable, Notification $notification): bool
    {
        try {
            $data = $notification->toArray($notifiable);

            $translatedContent = $this->translateNotificationContent(
                $data,
                $notifiable->locale ?? self::DEFAULT_LOCALE,
                $notifiable->uuid ?? null
            );

            $payload = $this->formatPayload($notifiable, $translatedContent);

            return $this->publishToRedis($payload);
        } catch (\Exception $e) {
            Log::error('Redis notification failed', [
                'user_id' => $notifiable->uuid ?? null,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function formatPayload($user, array $data): array
    {
        $event = null;

        switch ($user->user_type) {
            case 'admin':
                $event = 'admin_notification';
                $data['admin_id'] = $user->id ?? 1;
                break;

            case 'client':
                $event = 'client_notification';
                $data['client_id'] = $user->uuid ?? null;
                break;

            default:
                $event = 'general_notification';
        }

        return [
            'event'   => $event,
            'payload' => $data,
        ];
    }

    private function publishToRedis(array $payload): bool
    {
        $published = Redis::publish(
            self::REDIS_CHANNEL,
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        return $published > 0;
    }

    private function translateNotificationContent(array $data, string $locale, ?string $clientId): array
    {
        $created_at = now()
            ->locale($locale)
            ->translatedFormat('D d M Y - h:i A');

        return [
            'title'      => is_array($data['title'] ?? null)
                ? ($data['title'][$locale] ?? reset($data['title']))
                : ($data['title'] ?? null),
            'body'       => is_array($data['body'] ?? null)
                ? ($data['body'][$locale] ?? reset($data['body']))
                : ($data['body'] ?? null),
            'type'       => $data['type']       ?? null,
            'notify_id'  => $data['notify_id']  ?? null,
            'created_at' => $created_at,
        ];
    }
}
