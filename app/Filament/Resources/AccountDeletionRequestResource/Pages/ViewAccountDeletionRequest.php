<?php

namespace App\Filament\Resources\AccountDeletionRequestResource\Pages;

use App\Filament\Resources\AccountDeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewAccountDeletionRequest extends ViewRecord
{
    use Translatable;

    protected static string $resource = AccountDeletionRequestResource::class;
}
