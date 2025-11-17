<?php

namespace App\Filament\Resources\PrivateBusBookingResource\Pages;

use App\Filament\Resources\PrivateBusBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateBusBooking extends EditRecord
{
    protected static string $resource = PrivateBusBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
