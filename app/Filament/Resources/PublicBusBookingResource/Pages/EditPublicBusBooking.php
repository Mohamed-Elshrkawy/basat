<?php

namespace App\Filament\Resources\PublicBusBookingResource\Pages;

use App\Filament\Resources\PublicBusBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublicBusBooking extends EditRecord
{
    protected static string $resource = PublicBusBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
