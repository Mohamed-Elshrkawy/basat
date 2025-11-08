<?php

namespace App\Notifications;

use App\Services\PushNotification\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    protected FCMService $fcmService;


    public function __construct(public array $data , ?FCMService $fcmService = null)
    {
        $this->fcmService = $fcmService ?? new FCMService();
    }

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ((bool)$notifiable->is_notify) {
            $channels[] = 'fcm';
        }
        return $channels;
    }


    public function toArray($notifiable): array
    {
        return $this->data;
    }


    public function toFcm($notifiable): array
    {
        return $this->fcmService->sendNotificationsToUsers([$notifiable->id], $this->data);
    }
}
