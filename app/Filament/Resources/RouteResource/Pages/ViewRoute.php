<?php

namespace App\Filament\Resources\RouteResource\Pages;

use App\Filament\Resources\RouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRoute extends ViewRecord
{
    protected static string $resource = RouteResource::class;

    public  function getTitle(): string
    {
        return __('View route');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('Edit')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Route information'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name_ar')
                                    ->label(__('Route name (Arabic)')),

                                Infolists\Components\TextEntry::make('name_en')
                                    ->label(__('Route name (English)')),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('startCity.name')
                                    ->label(__('Start city'))
                                    ->getStateUsing(fn ($record) => $record->startCity?->getTranslation('name', 'ar'))
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('endCity.name')
                                    ->label(__('End city'))
                                    ->getStateUsing(fn ($record) => $record->endCity?->getTranslation('name', 'ar'))
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('range_km')
                                    ->label(__('Distance'))
                                    ->suffix(__(' km'))
                                    ->badge()
                                    ->color('warning'),
                            ]),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label(__('Status'))
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),

                Infolists\Components\Section::make(__('Route stops'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('routeStops')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('stop.name')
                                            ->label(__('Stop'))
                                            ->getStateUsing(fn ($record) => $record->stop?->getTranslation('name', 'ar'))
                                            ->badge()
                                            ->color('primary')
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
                                            ->color('info'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make(__('Additional information'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('Created at'))
                                    ->dateTime('Y-m-d H:i'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label(__('Last updated'))
                                    ->dateTime('Y-m-d H:i')
                                    ->since(),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
