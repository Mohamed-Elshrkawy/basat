<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewSchedule extends ViewRecord
{
    protected static string $resource = ScheduleResource::class;

    // ✅ عنوان مترجم ديناميكيًا
    public function getTitle(): string
    {
        return __('View trip schedule');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('Edit')),
            Actions\DeleteAction::make()
                ->label(__('Delete')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Route & trip info
                Infolists\Components\Section::make(__('Trip information'))
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('route.name')
                                    ->label(__('🛣️ Route'))
                                    ->getStateUsing(fn ($record) => $record->route?->getFullRouteName())
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('trip_type')
                                    ->label(__('🎫 Trip type'))
                                    ->formatStateUsing(fn (string $state): string =>
                                    $state === 'one_way' ? __('One way') : __('Round trip')
                                    )
                                    ->badge()
                                    ->color(fn ($state) => $state === 'round_trip' ? 'success' : 'info')
                                    ->size('lg'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('driver.name')
                                    ->label(__('👤 Driver'))
                                    ->default(__('Not assigned'))
                                    ->badge()
                                    ->color(fn ($state) => $state === __('Not assigned') ? 'gray' : 'success'),

                                Infolists\Components\IconEntry::make('is_active')
                                    ->label(__('📊 Trip status'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->size('lg'),
                            ]),
                    ])
                    ->columns(1),

                // Outbound
                Infolists\Components\Section::make(__('🚀 Outbound information'))
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('departure_time')
                                    ->label(__('⏰ Departure time'))
                                    ->time('H:i')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('arrival_time')
                                    ->label(__('🏁 Arrival time'))
                                    ->time('H:i')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('duration')
                                    ->label(__('⏱️ Duration'))
                                    ->getStateUsing(fn ($record) => $record->getOutboundDuration() ?? '-')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-clock'),

                                Infolists\Components\TextEntry::make('fare')
                                    ->label(__('💰 Fare'))
                                    ->money('SAR')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                // Return (if round trip)
                Infolists\Components\Section::make(__('🔙 Return information'))
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('return_departure_time')
                                    ->label(__('⏰ Departure time'))
                                    ->time('H:i')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('return_arrival_time')
                                    ->label(__('🏁 Arrival time'))
                                    ->time('H:i')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('return_duration')
                                    ->label(__('⏱️ Duration'))
                                    ->getStateUsing(fn ($record) => $record->getReturnDuration() ?? '-')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-clock'),

                                Infolists\Components\TextEntry::make('return_fare')
                                    ->label(__('💰 Fare'))
                                    ->money('SAR')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),
                            ]),

                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(3)
                                ->schema([
                                    Infolists\Components\TextEntry::make('total_price')
                                        ->label(__('💵 Original total (separate)'))
                                        ->getStateUsing(fn ($record) => $record->getRoundTripOriginalPrice())
                                        ->money('SAR')
                                        ->badge()
                                        ->color('gray'),

                                    Infolists\Components\TextEntry::make('round_trip_discount')
                                        ->label(__('🎁 Discount value'))
                                        ->money('SAR')
                                        ->badge()
                                        ->color('danger')
                                        ->icon('heroicon-o-gift'),

                                    Infolists\Components\TextEntry::make('final_price')
                                        ->label(__('✅ Final price (round trip)'))
                                        ->getStateUsing(fn ($record) => $record->getRoundTripPrice())
                                        ->money('SAR')
                                        ->badge()
                                        ->color('success')
                                        ->size('lg')
                                        ->weight('bold'),
                                ])
                        ]),

                        Infolists\Components\TextEntry::make('discount_percentage')
                            ->label(__('📊 Discount percentage'))
                            ->getStateUsing(fn ($record) => $record->getDiscountPercentage() . '%')
                            ->badge()
                            ->color('warning')
                            ->visible(fn ($record) => $record->round_trip_discount > 0),
                    ])
                    ->visible(fn ($record) => $record->isRoundTrip())
                    ->collapsible()
                    ->collapsed(false),

                // Outbound stops
                Infolists\Components\Section::make(__('🚩 Outbound stops'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('outboundStops')
                            ->label('')
                            ->schema([
                                Infolists\Components\Split::make([
                                    Infolists\Components\Grid::make(5)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('order')
                                                ->label('#')
                                                ->badge()
                                                ->color('primary')
                                                ->size('sm'),

                                            Infolists\Components\TextEntry::make('stop.name')
                                                ->label(__('Stop'))
                                                ->getStateUsing(fn ($record) => $record->stop?->getTranslation('name', 'ar'))
                                                ->badge()
                                                ->color('info')
                                                ->size('lg')
                                                ->icon('heroicon-o-map-pin')
                                                ->columnSpan(2),

                                            Infolists\Components\TextEntry::make('arrival_time')
                                                ->label(__('⏰ Arrival time'))
                                                ->time('H:i')
                                                ->badge()
                                                ->color('success'),

                                            Infolists\Components\TextEntry::make('departure_time')
                                                ->label(__('🚀 Departure time'))
                                                ->time('H:i')
                                                ->badge()
                                                ->color('warning'),
                                        ]),
                                ]),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->icon('heroicon-o-arrow-right-circle'),

                // Return stops
                Infolists\Components\Section::make(__('🔄 Return stops'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('returnStops')
                            ->label('')
                            ->schema([
                                Infolists\Components\Split::make([
                                    Infolists\Components\Grid::make(5)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('order')
                                                ->label('#')
                                                ->badge()
                                                ->color('primary')
                                                ->size('sm'),

                                            Infolists\Components\TextEntry::make('stop.name')
                                                ->label(__('Stop'))
                                                ->getStateUsing(fn ($record) => $record->stop?->getTranslation('name', 'ar'))
                                                ->badge()
                                                ->color('info')
                                                ->size('lg')
                                                ->icon('heroicon-o-map-pin')
                                                ->columnSpan(2),

                                            Infolists\Components\TextEntry::make('arrival_time')
                                                ->label(__('⏰ Arrival time'))
                                                ->time('H:i')
                                                ->badge()
                                                ->color('success'),

                                            Infolists\Components\TextEntry::make('departure_time')
                                                ->label(__('🚀 Departure time'))
                                                ->time('H:i')
                                                ->badge()
                                                ->color('warning'),
                                        ]),
                                ]),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ])
                    ->visible(fn ($record) => $record->isRoundTrip())
                    ->collapsible()
                    ->collapsed(false)
                    ->icon('heroicon-o-arrow-left-circle'),

                // Scheduling info
                Infolists\Components\Section::make(__('📅 Scheduling information'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('days_of_week')
                                    ->label(__('📆 Operating days'))
                                    ->formatStateUsing(function ($state) {
                                        $days = [
                                            'Monday'    => __('Monday'),
                                            'Tuesday'   => __('Tuesday'),
                                            'Wednesday' => __('Wednesday'),
                                            'Thursday'  => __('Thursday'),
                                            'Friday'    => __('Friday'),
                                            'Saturday'  => __('Saturday'),
                                            'Sunday'    => __('Sunday'),
                                        ];
                                        return collect($state)->map(fn($d) => $days[$d] ?? $d)->implode('، ');
                                    })
                                    ->badge()
                                    ->separator(',')
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('available_seats')
                                    ->label(__('💺 Available seats'))
                                    ->suffix(__(' seat'))
                                    ->badge()
                                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 10 ? 'warning' : 'danger'))
                                    ->size('lg')
                                    ->icon('heroicon-o-user-group'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('📊 Availability status'))
                                    ->getStateUsing(function ($record) {
                                        if (!$record->is_active) return __('Inactive');
                                        if (!$record->hasSeatsAvailable()) return __('Full');
                                        if ($record->driver_id) return __('Ready for booking');
                                        return __('Waiting for driver');
                                    })
                                    ->badge()
                                    ->color(function ($record) {
                                        if (!$record->is_active) return 'danger';
                                        if (!$record->hasSeatsAvailable()) return 'warning';
                                        if ($record->driver_id) return 'success';
                                        return 'info';
                                    })
                                    ->size('lg')
                                    ->icon(function ($record) {
                                        if (!$record->is_active) return 'heroicon-o-x-circle';
                                        if (!$record->hasSeatsAvailable()) return 'heroicon-o-exclamation-circle';
                                        if ($record->driver_id) return 'heroicon-o-check-circle';
                                        return 'heroicon-o-clock';
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-calendar'),

                // Summary
                Infolists\Components\Section::make(__('📊 Trip summary'))
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('outbound_stops_count')
                                    ->label(__('🚩 Outbound stops count'))
                                    ->getStateUsing(fn ($record) => $record->outboundStops()->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-map-pin'),

                                Infolists\Components\TextEntry::make('return_stops_count')
                                    ->label(__('🔄 Return stops count'))
                                    ->getStateUsing(fn ($record) => $record->returnStops()->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-map-pin')
                                    ->visible(fn ($record) => $record->isRoundTrip()),

                                Infolists\Components\TextEntry::make('total_duration')
                                    ->label(__('⏱️ Total duration'))
                                    ->getStateUsing(fn ($record) => $record->getTotalDuration() ?? '-')
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Infolists\Components\TextEntry::make('full_schedule_info')
                            ->label(__('ℹ️ Full information'))
                            ->getStateUsing(fn ($record) => $record->getFullScheduleInfo())
                            ->columnSpanFull()
                            ->size('lg'),
                    ])
                    ->collapsible()
                    ->collapsed(true),

                // Additional info
                Infolists\Components\Section::make(__('📝 Additional information'))
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label(__('Schedule number'))
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('Created at'))
                                    ->dateTime('Y-m-d H:i')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label(__('Last updated'))
                                    ->dateTime('Y-m-d H:i')
                                    ->since()
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}
