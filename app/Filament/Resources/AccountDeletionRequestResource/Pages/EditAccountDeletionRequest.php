<?php

namespace App\Filament\Resources\AccountDeletionRequestResource\Pages;

use App\Filament\Resources\AccountDeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountDeletionRequest extends EditRecord
{
    protected static string $resource = AccountDeletionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
