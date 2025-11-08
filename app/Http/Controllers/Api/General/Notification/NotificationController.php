<?php

namespace App\Http\Controllers\Api\General\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\General\ListRequest;
use App\Services\General\NotificationsService;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationsService $service){}


    public function index(ListRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->service->list($request);

    }

    public function markAsRead(DatabaseNotification $notification = null): \Illuminate\Http\JsonResponse
    {
        return $this->service->updateAsRead($notification);

    }

    public function destroy(DatabaseNotification $notification = null): \Illuminate\Http\JsonResponse
    {
        return $this->service->destroy($notification);

    }
}
