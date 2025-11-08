<?php

namespace App\Filament\Resources\StopResource\Pages;

use App\Filament\Resources\StopResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStop extends CreateRecord
{
    protected static string $resource = StopResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إضافة المحطة بنجاح';
    }
}
