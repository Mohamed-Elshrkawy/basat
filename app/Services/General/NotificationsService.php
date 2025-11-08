<?php

namespace App\Services\General;

use App\Enums\UserTypeEnum;
use App\Http\Resources\Api\General\Notification\NotificationResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsService
{
    public function list(Request|FormRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $user = $this->getUser();
        $count = $user->unreadNotifications()->count();
        if ($request->count) {
            return json($count);
        }
        $notifications = $user
            ->notifications()
            ->when($request->type == 'unread', fn($q) => $q->unread())
            ->when($request->type == 'read', fn($q) => $q->read())
            ->paginate();

        return NotificationResource::collection($notifications)->additional([
            'message' => '',
            'status' => 'success',
            'count' => $count
        ]);
    }

    public function updateAsRead(DatabaseNotification $notification, string $type= null): \Illuminate\Http\JsonResponse
    {
        $user = $this->getUser();
        if($type == 'all'){
            $user->unreadNotifications()->update(['read_at' => now()]);
            return json(__('All notifications marked as read'));
        }
        $notification->markAsRead();
        return json(__('Notification marked as read'));
    }

    public function destroy(DatabaseNotification $notification, string $type= null): \Illuminate\Http\JsonResponse
    {
        $user = $this->getUser();
        if($type == 'all'){
            $user->unreadNotifications()->delete();
            return json(__('All notifications deleted successfully'));
        }
        $notification->delete();
        return json(__('Notification was deleted successfully'));
    }

    private function getUser()
    {
        $user= request()->user();
        if ($user->user_type == UserTypeEnum::Organizer->value) {
            return $user->organizer;
        } else {
            return $user;
        }
    }
}
