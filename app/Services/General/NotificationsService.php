<?php

namespace App\Services\General;

use App\Enums\UserTypeEnum;
use App\Http\Resources\Api\General\Notification\NotificationResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsService
{
    public $user;
    public function __construct()
    {
        $this->user = request()->user();
    }

    public function list(Request|FormRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $notifications = $this->user->notifications()
            ->when($request->filled('notification_type'),function ($query) use ($request) {
                $query->where('data->notification_type', $request->notification_type);
            })
            ->latest()
            ->paginate();

        return  NotificationResource::collection($notifications)->additional([
            'message' => '',
            'status' => 'success',
            'unread_count' => $this->user->unreadNotifications()->count()
        ]);
    }

    public function updateAsRead(DatabaseNotification $notification, string $type= null): \Illuminate\Http\JsonResponse
    {
        if ($notification) {
            abort_if($notification->notifiable_id !== $this->user->id, 403, __('You can not mark this notification as read'));
            $notification->markAsRead();
            return json( __('Notification marked as read'));
        }

        $this->user->unreadNotifications()->update(['read_at' => now()]);
        return json( __('All notifications marked as read'));
    }

    public function destroy(DatabaseNotification $notification, string $type= null): \Illuminate\Http\JsonResponse
    {
        if ($notification) {
            abort_if($notification->notifiable_id !== $this->user->id, 403, __('You can not delete this notification'));
            $notification->delete();
            return json(__('Notification was deleted successfully'));
        }

        $this->user->notifications()->delete();
        return json(__('All notifications were deleted successfully'));
    }

}
