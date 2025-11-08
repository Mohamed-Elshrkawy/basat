<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Notifications\Events\NotificationFailed;
use NotificationChannels\Fcm\FcmChannel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class DeleteExpiredNotificationTokens
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel == FcmChannel::class) {
            $report = Arr::get($event->data, 'report');
            
            if ($report && method_exists($report, 'target')) {
                $target = $report->target();
                
                if ($target && method_exists($target, 'value')) {
                    $token = $target->value();
                    
                    // Log the failed notification
                    Log::warning('FCM notification failed', [
                        'notifiable_type' => get_class($event->notifiable),
                        'notifiable_id' => $event->notifiable->id ?? null,
                        'token' => $token,
                        'error' => $report->error() ? $report->error()->getMessage() : 'Unknown error',
                    ]);
                    
                    // Remove expired token if the notifiable has the method
                    if (method_exists($event->notifiable, 'removeFcmToken')) {
                        $event->notifiable->removeFcmToken($token);
                        Log::info('Removed expired FCM token', [
                            'notifiable_type' => get_class($event->notifiable),
                            'notifiable_id' => $event->notifiable->id ?? null,
                            'token' => $token,
                        ]);
                    }
                }
            }
        }
    }
}
