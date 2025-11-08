<?php

namespace App\Filament\Resources\AccountDeletionRequestResource\Pages;

use App\Filament\Resources\AccountDeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountDeletionRequests extends ListRecords
{
    protected static string $resource = AccountDeletionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
