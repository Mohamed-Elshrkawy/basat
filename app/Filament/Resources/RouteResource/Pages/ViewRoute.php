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

    protected static ?string $title = 'عرض المسار';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات المسار')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name_ar')
                                    ->label('اسم المسار (عربي)'),

                                Infolists\Components\TextEntry::make('name_en')
                                    ->label('اسم المسار (English)'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('startCity.name')
                                    ->label('مدينة البداية')
                                    ->getStateUsing(fn ($record) => $record->startCity?->getTranslation('name', 'ar'))
                                    ->badge()
                                    ->color('success'),

                                Infolists\Components\TextEntry::make('endCity.name')
                                    ->label('مدينة النهاية')
                                    ->getStateUsing(fn ($record) => $record->endCity?->getTranslation('name', 'ar'))
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('range_km')
                                    ->label('المسافة')
                                    ->suffix(' كم')
                                    ->badge()
                                    ->color('warning'),
                            ]),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label('الحالة')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),

                Infolists\Components\Section::make('محطات المسار')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('routeStops')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('stop.name')
                                            ->label('المحطة')
                                            ->getStateUsing(fn ($record) => $record->stop?->getTranslation('name', 'ar'))
                                            ->badge()
                                            ->color('primary')
                                            ->columnSpan(2),

                                        Infolists\Components\TextEntry::make('arrival_time')
                                            ->label('⏰ وقت الوصول')
                                            ->time('H:i')
                                            ->badge()
                                            ->color('success'),

                                        Infolists\Components\TextEntry::make('departure_time')
                                            ->label('🚀 وقت المغادرة')
                                            ->time('H:i')
                                            ->badge()
                                            ->color('info'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('معلومات إضافية')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime('Y-m-d H:i')
                                    ->since(),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
