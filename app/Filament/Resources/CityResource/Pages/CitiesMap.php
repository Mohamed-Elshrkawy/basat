<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use App\Models\City;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;

class CitiesMap extends Page
{
    protected static string $resource = CityResource::class;

    protected static string $view = 'filament.resources.city-resource.pages.cities-map';

    public  function getTitle(): string
    {
        return __('Cities map');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('Back to list'))
                ->icon('heroicon-o-arrow-left')
                ->url(CityResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getCities()
    {
        return City::all();
    }

    public function createCity($data)
    {
        try {
            $city = City::create([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en'],
                ],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            Notification::make()
                ->title(__('City added successfully'))
                ->body(__('Added: :name', ['name' => $data['name_ar']]))
                ->success()
                ->send();

            return $city->fresh();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('An error occurred'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    public function updateCity($id, $data)
    {
        try {
            $city = City::findOrFail($id);

            $city->update([
                'name' => [
                    'ar' => $data['name_ar'],
                    'en' => $data['name_en'],
                ],
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'is_active' => $data['is_active'] ?? $city->is_active,
            ]);

            Notification::make()
                ->title(__('City updated successfully'))
                ->success()
                ->send();

            return $city->fresh();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('An error occurred'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    public function deleteCity($id)
    {
        try {
            $city = City::findOrFail($id);
            $cityName = $city->getTranslation('name', 'ar');

            $city->delete();

            Notification::make()
                ->title(__('City deleted'))
                ->body(__('Deleted: :name', ['name' => $cityName]))
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('An error occurred'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }

    public function updateCityLocation($id, $lat, $lng)
    {
        try {
            $city = City::findOrFail($id);

            $city->update([
                'lat' => $lat,
                'lng' => $lng,
            ]);

            Notification::make()
                ->title(__('City location updated'))
                ->success()
                ->send();

            return true;
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('An error occurred'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }
}
