<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_type'] = 'driver';

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $driverData = $data['driver'] ?? [];
        $vehicleData = $data['vehicle'] ?? [];

        unset($data['driver'], $data['vehicle']);

        $user = static::getModel()::create($data);

        if (!empty($driverData)) {
            $user->driver()->create([
                'bio' => $driverData['bio'] ?? null,
                'availability_status' => $driverData['availability_status'] ?? 'unavailable',
                'avg_rating' => $driverData['avg_rating'] ?? 0,
                'current_lat' => $driverData['current_lat'] ?? null,
                'current_lng' => $driverData['current_lng'] ?? null,
            ]);
        } else {
            $user->driver()->create([
                'availability_status' => 'unavailable',
                'avg_rating' => 0,
            ]);
        }

        if (!empty($vehicleData) && !empty($vehicleData['plate_number'])) {
            $user->vehicle()->create([
                'brand_id' => $vehicleData['brand_id'] ?? null,
                'vehicle_model_id' => $vehicleData['vehicle_model_id'] ?? null,
                'plate_number' => $vehicleData['plate_number'],
                'seat_count' => $vehicleData['seat_count'] ?? 50,
                'type' => $vehicleData['type'] ?? 'public_bus',
                'is_active' => $vehicleData['is_active'] ?? true,
            ]);
        }

        $user->wallet()->create(['balance' => 0]);

        return $user;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('Driver added successfully');
    }
}
