<?php

namespace App\Filament\Resources\StopResource\Pages;

use App\Filament\Resources\StopResource;
use App\Models\Stop;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class StopsMap extends Page
{
    protected static string $resource = StopResource::class;

    protected static string $view = 'filament.resources.stop-resource.pages.stops-map';

    protected static ?string $title = 'خريطة المحطات';

    protected static ?string $navigationLabel = 'خريطة المحطات';

    // جلب جميع المحطات
    public function getStops()
    {
        return Stop::all();
    }

    // إنشاء محطة جديدة
    public function createStop(array $data)
    {
        try {
            $stop = Stop::create([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en']
                ],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'is_active' => $data['is_active'] ?? true
            ]);

            Notification::make()
                ->title('تم إضافة المحطة بنجاح')
                ->success()
                ->send();

            return [
                'id' => $stop->id,
                'name' => $stop->name,
                'lat' => (float) $stop->lat,
                'lng' => (float) $stop->lng,
                'is_active' => (bool) $stop->is_active
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء إضافة المحطة')
                ->danger()
                ->send();

            return null;
        }
    }

    // تحديث محطة موجودة
    public function updateStop($stopId, array $data)
    {
        try {
            $stop = Stop::findOrFail($stopId);

            $stop->update([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en']
                ],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'is_active' => $data['is_active'] ?? true
            ]);

            Notification::make()
                ->title('تم تحديث المحطة بنجاح')
                ->success()
                ->send();

            return [
                'id' => $stop->id,
                'name' => $stop->name,
                'lat' => (float) $stop->lat,
                'lng' => (float) $stop->lng,
                'is_active' => (bool) $stop->is_active
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء تحديث المحطة')
                ->danger()
                ->send();

            return null;
        }
    }

    // تحديث موقع المحطة (عند السحب)
    public function updateStopLocation($stopId, $lat, $lng)
    {
        try {
            $stop = Stop::findOrFail($stopId);

            $stop->update([
                'lat' => $lat,
                'lng' => $lng
            ]);

            Notification::make()
                ->title('تم تحديث موقع المحطة')
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء تحديث الموقع')
                ->danger()
                ->send();

            return false;
        }
    }

    // حذف محطة
    public function deleteStop($stopId)
    {
        try {
            $stop = Stop::findOrFail($stopId);
            $stop->delete();

            Notification::make()
                ->title('تم حذف المحطة بنجاح')
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء حذف المحطة')
                ->danger()
                ->send();

            return false;
        }
    }
}
