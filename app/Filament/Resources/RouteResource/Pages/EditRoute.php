<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoute extends EditRecord
{
    protected static string $resource = RouteResource::class;

    public  function getTitle(): string
    {
        return __('Edit route');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label(__('View')),
            Actions\DeleteAction::make()
                ->label(__('Delete')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('Route updated successfully');
    }
}
