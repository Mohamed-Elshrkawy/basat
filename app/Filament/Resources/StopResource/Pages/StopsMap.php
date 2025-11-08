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

    public function getTitle(): string
    {
        return __('Stops map');
    }

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function getNavigationLabel(): string
    {
        return __('Stops map');
    }

    public function getStops()
    {
        return Stop::all()->map(function ($stop) {
            return [
                'id'        => $stop->id,
                'name'      => [
                    'ar' => $stop->name['ar'] ?? $stop->getTranslation('name', 'ar') ?? __('Not set'),
                    'en' => $stop->name['en'] ?? $stop->getTranslation('name', 'en') ?? __('Not set'),
                ],
                'lat'       => (float) $stop->lat,
                'lng'       => (float) $stop->lng,
                'is_active' => (bool) $stop->is_active,
            ];
        });
    }

    public function createStop(array $data)
    {
        try {
            $stop = Stop::create([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en'],
                ],
                'lat'       => $data['lat'],
                'lng'       => $data['lng'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            Notification::make()
                ->title(__('Stop created successfully'))
                ->success()
                ->send();

            return [
                'id'        => $stop->id,
                'name'      => [
                    'ar' => $stop->name['ar'] ?? $stop->getTranslation('name', 'ar'),
                    'en' => $stop->name['en'] ?? $stop->getTranslation('name', 'en'),
                ],
                'lat'       => (float) $stop->lat,
                'lng'       => (float) $stop->lng,
                'is_active' => (bool) $stop->is_active,
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Error while creating'))
                ->danger()
                ->send();

            return null;
        }
    }

    public function updateStop($stopId, array $data)
    {
        try {
            $stop = Stop::findOrFail($stopId);

            $stop->update([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en'],
                ],
                'lat'       => $data['lat'],
                'lng'       => $data['lng'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            Notification::make()
                ->title(__('Stop updated successfully'))
                ->success()
                ->send();

            return [
                'id'        => $stop->id,
                'name'      => [
                    'ar' => $stop->name['ar'] ?? $stop->getTranslation('name', 'ar'),
                    'en' => $stop->name['en'] ?? $stop->getTranslation('name', 'en'),
                ],
                'lat'       => (float) $stop->lat,
                'lng'       => (float) $stop->lng,
                'is_active' => (bool) $stop->is_active,
            ];
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Error while updating'))
                ->danger()
                ->send();

            return null;
        }
    }

    public function updateStopLocation($stopId, $lat, $lng)
    {
        try {
            $stop = Stop::findOrFail($stopId);

            $stop->update([
                'lat' => $lat,
                'lng' => $lng,
            ]);

            Notification::make()
                ->title(__('Location updated successfully'))
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Error while updating location'))
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
                ->title(__('Stop deleted successfully'))
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Error while deleting'))
                ->danger()
                ->send();

            return false;
        }
    }
}
