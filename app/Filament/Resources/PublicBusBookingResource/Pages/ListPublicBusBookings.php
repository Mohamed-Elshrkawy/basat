<?php

namespace App\Filament\Resources\PublicBusBookingResource\Pages;

use App\Filament\Resources\PublicBusBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublicBusBookings extends ListRecords
{
    protected static string $resource = PublicBusBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
