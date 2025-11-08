<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_type'] = 'customer';

        return $data;
    }


    protected function handleRecordCreation(array $data): Model
    {
        $user = static::getModel()::create($data);

        $user->wallet()->create(['balance' => 0]);

        return $user;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('User created successfully');
    }
}
