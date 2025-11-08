<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewDriver extends ViewRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Basic Information
                Infolists\Components\Section::make(__('Basic Information'))
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('Full Name'))
                                    ->size('lg')
                                    ->weight('bold')
                                    ->icon('heroicon-m-user'),

                                Infolists\Components\TextEntry::make('national_id')
                                    ->label(__('National ID'))
                                    ->copyable()
                                    ->icon('heroicon-m-identification'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label(__('Phone Number'))
                                    ->copyable()
                                    ->icon('heroicon-m-phone'),

                                Infolists\Components\TextEntry::make('gender')
                                    ->label(__('Gender'))
                                    ->formatStateUsing(fn ($state) => $state === 'male' ? __('Male') : __('Female'))
                                    ->badge()
                                    ->color(fn ($state) => $state === 'male' ? 'primary' : 'success'),

                                Infolists\Components\IconEntry::make('mobile_verified_at')
                                    ->label(__('Verification Status'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\TextEntry::make('mobile_verified_at')
                                    ->label(__('Verification Date'))
                                    ->dateTime()
                                    ->placeholder(__('Unverified')),
                            ]),
                    ])
                    ->icon('heroicon-o-user'),

                // Driver Info
                Infolists\Components\Section::make(__('Driver Info'))
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('driver.availability_status')
                                    ->label(__('Availability Status'))
                                    ->formatStateUsing(fn (?string $state): string => match($state) {
                                        'available' => __('Available'),
                                        'on_trip' => __('On Trip'),
                                        'unavailable' => __('Unavailable'),
                                        default => __('Undefined'),
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'available' => 'success',
                                        'on_trip' => 'warning',
                                        'unavailable' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('driver.avg_rating')
                                    ->label(__('Rating'))
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' ⭐' : __('No Rating'))
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        $state >= 4.5 => 'success',
                                        $state >= 3.5 => 'warning',
                                        $state > 0 => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('wallet.balance')
                                    ->label(__('Wallet Balance'))
                                    ->money('SAR')
                                    ->default(0)
                                    ->badge()
                                    ->color('success'),
                            ]),

                        Infolists\Components\TextEntry::make('driver.bio')
                            ->label(__('Driver Bio'))
                            ->placeholder(__('Not Available'))
                            ->columnSpanFull(),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('driver.current_lat')
                                    ->label(__('Latitude'))
                                    ->placeholder(__('Not Defined')),

                                Infolists\Components\TextEntry::make('driver.current_lng')
                                    ->label(__('Longitude'))
                                    ->placeholder(__('Not Defined')),
                            ])
                            ->hidden(fn ($record) => !$record->driver?->current_lat && !$record->driver?->current_lng),
                    ])
                    ->icon('heroicon-o-identification')
                    ->collapsible(),

                // ✅ Vehicle Information
                Infolists\Components\Section::make(__('Vehicle Information'))
                    ->schema([
                        // Vehicle Details Grid
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                // Brand
                                Infolists\Components\TextEntry::make('vehicle.brand.name')
                                    ->label(__('Brand'))
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-m-bookmark')
                                    ->placeholder(__('No Vehicle')),

                                // Model
                                Infolists\Components\TextEntry::make('vehicle.vehicleModel.name')
                                    ->label(__('Model'))
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-m-rectangle-stack')
                                    ->placeholder(__('No Vehicle')),

                                // Plate Number
                                Infolists\Components\TextEntry::make('vehicle.plate_number')
                                    ->label(__('Plate Number'))
                                    ->copyable()
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-m-identification')
                                    ->placeholder(__('No Vehicle')),

                                // Vehicle Type
                                Infolists\Components\TextEntry::make('vehicle.type')
                                    ->label(__('Vehicle Type'))
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'public_bus' => '🚌 ' . __('Public Bus'),
                                        'private_bus' => '🚐 ' . __('Private Bus'),
                                        'school_bus' => '🚍 ' . __('School Bus'),
                                        default => $state ?? __('Not Defined'),
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        'public_bus' => 'primary',
                                        'private_bus' => 'success',
                                        'school_bus' => 'warning',
                                        default => 'gray',
                                    })
                                    ->placeholder(__('No Vehicle')),

                                // Seat Count
                                Infolists\Components\TextEntry::make('vehicle.seat_count')
                                    ->label(__('Seat Count'))
                                    ->suffix(' ' . __('Seats'))
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-m-user-group')
                                    ->placeholder(__('No Vehicle')),

                                // Active Status
                                Infolists\Components\IconEntry::make('vehicle.is_active')
                                    ->label(__('Vehicle Status'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ])
                            ->hidden(fn ($record) => !$record->vehicle),

                        // No Vehicle Message
                        Infolists\Components\TextEntry::make('no_vehicle')
                            ->label('')
                            ->state(__('No Vehicle Registered'))
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->hidden(fn ($record) => $record->vehicle !== null),

                        // ✅ Amenities Sub-Section
                        Infolists\Components\Section::make(__('Amenities'))
                            ->schema([
                                // Amenities List
                                Infolists\Components\RepeatableEntry::make('vehicle.amenities')
                                    ->label('')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label(__('Amenity'))
                                                    ->icon('heroicon-m-star')
                                                    ->badge()
                                                    ->color('info'),

                                                Infolists\Components\TextEntry::make('pivot.price')
                                                    ->label(__('Price'))
                                                    ->money('SAR')
                                                    ->badge()
                                                    ->color('success'),
                                            ]),
                                    ])
                                    ->contained(false)
                                    ->hidden(fn ($record) => !$record->vehicle || $record->vehicle->amenities->isEmpty()),

                                // No Amenities Message
                                Infolists\Components\TextEntry::make('no_amenities')
                                    ->label('')
                                    ->state(__('No Amenities Available'))
                                    ->color('gray')
                                    ->icon('heroicon-o-information-circle')
                                    ->hidden(fn ($record) => !$record->vehicle || !$record->vehicle->amenities->isEmpty()),
                            ])
                            ->hidden(fn ($record) => !$record->vehicle)
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->icon('heroicon-o-truck')
                    ->collapsible()
                    ->collapsed(),

                // System Info
                Infolists\Components\Section::make(__('System Info'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('Created At'))
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label(__('Last Updated'))
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-m-arrow-path'),
                            ]),
                    ])
                    ->collapsed()
                    ->icon('heroicon-o-information-circle'),
            ]);
    }
}
