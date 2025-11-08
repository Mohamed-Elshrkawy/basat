<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['permissions']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $permissionIds = $this->form->getState()['permissions'] ?? [];

        if (!empty($permissionIds)) {
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            $this->record->syncPermissions($permissions);
        }
    }
}
