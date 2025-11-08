<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRoute extends CreateRecord
{
    protected static string $resource = RouteResource::class;

    public  function getTitle(): string
    {
        return __('Add new route');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('Route created successfully');
    }
}
