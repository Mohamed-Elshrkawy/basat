<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Add Driver')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('ALL'))
                ->badge(fn () => $this->getModel()::where('user_type', 'driver')->count()),

            'available' => Tab::make(__('Available'))
                ->modifyQueryUsing(fn (Builder $query) =>
                $query->whereHas('driver', fn($q) => $q->where('availability_status', 'available'))
                )
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereHas('driver', fn($q) => $q->where('availability_status', 'available'))
                    ->count()
                )
                ->badgeColor('success'),

            'on_trip' => Tab::make(__('On Trip'))
                ->modifyQueryUsing(fn (Builder $query) =>
                $query->whereHas('driver', fn($q) => $q->where('availability_status', 'on_trip'))
                )
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereHas('driver', fn($q) => $q->where('availability_status', 'on_trip'))
                    ->count()
                )
                ->badgeColor('warning'),

            'unavailable' => Tab::make(__('Unavailable'))
                ->modifyQueryUsing(fn (Builder $query) =>
                $query->whereHas('driver', fn($q) => $q->where('availability_status', 'unavailable'))
                )
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereHas('driver', fn($q) => $q->where('availability_status', 'unavailable'))
                    ->count()
                )
                ->badgeColor('danger'),

            'verified' => Tab::make(__('Verified'))
                ->modifyQueryUsing(fn (Builder $query) =>
                $query->whereNotNull('mobile_verified_at')
                )
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereNotNull('mobile_verified_at')
                    ->count()
                )
                ->badgeColor('success'),

            'with_vehicle' => Tab::make(__('With Vehicle'))
                ->modifyQueryUsing(fn (Builder $query) => $query->has('vehicle'))
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->has('vehicle')
                    ->count()
                )
                ->badgeColor('info'),
        ];
    }
}
