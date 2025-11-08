<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

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

    // ✅ التأكد من بقاء user_type = 'admin' عند التعديل
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_type'] = 'admin';

        return $data;
    }

    // ✅ (اختياري) منع التعديل إذا كان المستخدم ليس admin
    protected function beforeFill(): void
    {
        // التحقق من أن السجل هو admin فعلاً
        if ($this->record->user_type !== 'admin') {
            abort(403, 'غير مصرح لك بتعديل هذا المستخدم');
        }
    }
}
