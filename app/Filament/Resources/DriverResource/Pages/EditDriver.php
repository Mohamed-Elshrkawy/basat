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
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ✅ التأكد من بقاء user_type = 'driver'
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_type'] = 'driver';

        return $data;
    }

    // ✅ منع تعديل غير السائقين
    protected function beforeFill(): void
    {
        if ($this->record->user_type !== 'driver') {
            abort(403, 'غير مصرح لك بتعديل هذا المستخدم');
        }
    }

    // ✅ تحميل بيانات Driver والـ Vehicle
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // تحميل بيانات Driver
        if ($this->record->driver) {
            $data['driver'] = [
                'bio' => $this->record->driver->bio,
                'availability_status' => $this->record->driver->availability_status,
                'avg_rating' => $this->record->driver->avg_rating,
                'current_lat' => $this->record->driver->current_lat,
                'current_lng' => $this->record->driver->current_lng,
            ];
        }

        // ✅ تحميل بيانات Vehicle (hasOne)
        if ($this->record->vehicle) {
            $data['vehicle'] = [
                'brand_id' => $this->record->vehicle->brand_id,  // ✅ brand_id
                'vehicle_model_id' => $this->record->vehicle->vehicle_model_id,  // ✅ vehicle_model_id
                'plate_number' => $this->record->vehicle->plate_number,
                'seat_count' => $this->record->vehicle->seat_count,
                'type' => $this->record->vehicle->type,
                'is_active' => $this->record->vehicle->is_active,
            ];

            // ✅ تحميل الوسائل (Amenities)
            $data['vehicle_amenities'] = $this->record->vehicle->amenities->map(function ($amenity) {
                return [
                    'amenity_id' => $amenity->id,
                    'price' => $amenity->pivot->price,
                ];
            })->toArray();
        }

        return $data;
    }

    // ✅ حفظ التعديلات
    protected function afterSave(): void
    {
        $data = $this->form->getState();

        // تحديث/إنشاء Driver
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

        // ✅ تحديث/إنشاء Vehicle (hasOne) - الهيكل الجديد
        if (isset($data['vehicle']) && !empty($data['vehicle']['plate_number'])) {
            $vehicle = $this->record->vehicle;  // ✅ hasOne

            if ($vehicle) {
                // تحديث السيارة الموجودة
                $vehicle->update([
                    'brand_id' => $data['vehicle']['brand_id'] ?? null,  // ✅ brand_id
                    'vehicle_model_id' => $data['vehicle']['vehicle_model_id'] ?? null,  // ✅ vehicle_model_id
                    'plate_number' => $data['vehicle']['plate_number'],
                    'seat_count' => $data['vehicle']['seat_count'] ?? 50,
                    'type' => $data['vehicle']['type'] ?? 'public_bus',  // ✅ public_bus
                    'is_active' => $data['vehicle']['is_active'] ?? true,
                ]);
            } else {
                // إنشاء سيارة جديدة
                $vehicle = $this->record->vehicle()->create([
                    'brand_id' => $data['vehicle']['brand_id'] ?? null,
                    'vehicle_model_id' => $data['vehicle']['vehicle_model_id'] ?? null,
                    'plate_number' => $data['vehicle']['plate_number'],
                    'seat_count' => $data['vehicle']['seat_count'] ?? 50,
                    'type' => $data['vehicle']['type'] ?? 'public_bus',
                    'is_active' => $data['vehicle']['is_active'] ?? true,
                ]);
            }

            // ✅ مزامنة الوسائل (Amenities)
            if (isset($data['vehicle_amenities']) && is_array($data['vehicle_amenities'])) {
                $amenitiesData = [];
                foreach ($data['vehicle_amenities'] as $amenity) {
                    if (isset($amenity['amenity_id']) && isset($amenity['price'])) {
                        $amenitiesData[$amenity['amenity_id']] = ['price' => $amenity['price']];
                    }
                }
                $vehicle->amenities()->sync($amenitiesData);
            } else {
                // إذا لم يتم تحديد وسائل، نحذف الوسائل الموجودة
                $vehicle->amenities()->detach();
            }
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات السائق بنجاح';
    }
}
