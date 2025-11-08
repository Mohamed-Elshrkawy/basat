<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_type'] = 'customer';

        return $data;
    }

    // ✅ منع تعديل غير العملاء
    protected function beforeFill(): void
    {
        if ($this->record->user_type !== 'customer') {
            abort(403, __('You are not allowed to edit this user'));
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('User updated successfully');
    }
}
