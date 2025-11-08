<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label(__('View')),
            Actions\DeleteAction::make()
                ->label(__('Delete')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_type'] = 'driver';

        return $data;
    }

    protected function beforeFill(): void
    {
        if ($this->record->user_type !== 'driver') {
            abort(403, __('You are not authorized to edit this user'));
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->driver) {
            $data['driver'] = [
                'bio' => $this->record->driver->bio,
                'availability_status' => $this->record->driver->availability_status,
                'avg_rating' => $this->record->driver->avg_rating,
                'current_lat' => $this->record->driver->current_lat,
                'current_lng' => $this->record->driver->current_lng,
            ];
        }

        if ($this->record->vehicle) {
            $data['vehicle'] = [
                'brand_id' => $this->record->vehicle->brand_id,
                'vehicle_model_id' => $this->record->vehicle->vehicle_model_id,
                'plate_number' => $this->record->vehicle->plate_number,
                'seat_count' => $this->record->vehicle->seat_count,
                'type' => $this->record->vehicle->type,
                'is_active' => $this->record->vehicle->is_active,
            ];

            $data['vehicle_amenities'] = $this->record->vehicle->amenities->map(function ($amenity) {
                return [
                    'amenity_id' => $amenity->id,
                    'price' => $amenity->pivot->price,
                ];
            })->toArray();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        if (isset($data['driver'])) {
            $driver = $this->record->driver;

            if ($driver) {
                $driver->update([
                    'bio' => $data['driver']['bio'] ?? null,
                    'availability_status' => $data['driver']['availability_status'] ?? 'unavailable',
                    'current_lat' => $data['driver']['current_lat'] ?? null,
                    'current_lng' => $data['driver']['current_lng'] ?? null,
                ]);
            } else {
                $this->record->driver()->create([
                    'bio' => $data['driver']['bio'] ?? null,
                    'availability_status' => $data['driver']['availability_status'] ?? 'unavailable',
                    'avg_rating' => 0,
                    'current_lat' => $data['driver']['current_lat'] ?? null,
                    'current_lng' => $data['driver']['current_lng'] ?? null,
                ]);
            }
        }

        if (isset($data['vehicle']) && !empty($data['vehicle']['plate_number'])) {
            $vehicle = $this->record->vehicle; // hasOne

            if ($vehicle) {
                $vehicle->update([
                    'brand_id' => $data['vehicle']['brand_id'] ?? null,
                    'vehicle_model_id' => $data['vehicle']['vehicle_model_id'] ?? null,
                    'plate_number' => $data['vehicle']['plate_number'],
                    'seat_count' => $data['vehicle']['seat_count'] ?? 50,
                    'type' => $data['vehicle']['type'] ?? 'public_bus',
                    'is_active' => $data['vehicle']['is_active'] ?? true,
                ]);
            } else {
                $vehicle = $this->record->vehicle()->create([
                    'brand_id' => $data['vehicle']['brand_id'] ?? null,
                    'vehicle_model_id' => $data['vehicle']['vehicle_model_id'] ?? null,
                    'plate_number' => $data['vehicle']['plate_number'],
                    'seat_count' => $data['vehicle']['seat_count'] ?? 50,
                    'type' => $data['vehicle']['type'] ?? 'public_bus',
                    'is_active' => $data['vehicle']['is_active'] ?? true,
                ]);
            }

            if (isset($data['vehicle_amenities']) && is_array($data['vehicle_amenities'])) {
                $amenitiesData = [];
                foreach ($data['vehicle_amenities'] as $amenity) {
                    if (isset($amenity['amenity_id']) && isset($amenity['price'])) {
                        $amenitiesData[$amenity['amenity_id']] = ['price' => $amenity['price']];
                    }
                }
                $vehicle->amenities()->sync($amenitiesData);
            } else {
                $vehicle->amenities()->detach();
            }
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('Driver updated successfully');
    }
}
