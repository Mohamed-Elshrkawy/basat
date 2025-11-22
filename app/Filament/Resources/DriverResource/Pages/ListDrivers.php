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

            'public_bus' => Tab::make(__('Public Bus'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('vehicle', fn($q) => $q->where('type', 'public_bus')))
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereHas('vehicle', fn($q) => $q->where('type', 'public_bus'))
                    ->count()
                )
                ->badgeColor('info'),

            'private_bus' => Tab::make(__('Private Bus'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('vehicle', fn($q) => $q->where('type', 'private_bus')))
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereHas('vehicle', fn($q) => $q->where('type', 'private_bus'))
                    ->count()
                )
                ->badgeColor('info'),

            'school_bus' => Tab::make(__('School Bus'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('vehicle', fn($q) => $q->where('type', 'school_bus')))
                ->badge(fn () =>
                $this->getModel()::where('user_type', 'driver')
                    ->whereHas('vehicle', fn($q) => $q->where('type', 'school_bus'))
                    ->count()
                )
                ->badgeColor('info'),

        ];
    }
}
