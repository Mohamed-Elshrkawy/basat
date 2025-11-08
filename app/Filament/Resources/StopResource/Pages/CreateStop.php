<?php

namespace App\Filament\Resources\StopResource\Pages;

use App\Filament\Resources\StopResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStop extends CreateRecord
{
    protected static string $resource = StopResource::class;

    // ✅ عنوان الصفحة مترجم
    public  function getTitle(): string
    {
        return __('Add new stop');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('Stop created successfully');
    }
}
