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

    protected static ?string $title = 'عرض جدول الرحلة';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // معلومات المسار والرحلة
                Infolists\Components\Section::make('معلومات الرحلة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('route.name')
                                    ->label('🛣️ المسار')
                                    ->getStateUsing(fn ($record) => $record->route?->getFullRouteName())
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('trip_type')
                                    ->label('🎫 نوع الرحلة')
                                    ->formatStateUsing(fn (string $state): string =>
                                    $state === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة'
                                    )
                                    ->badge()
                                    ->color(fn ($state) => $state === 'round_trip' ? 'success' : 'info')
                                    ->size('lg'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('driver.name')
                                    ->label('👤 السائق')
                                    ->default('لم يتم التعيين')
                                    ->badge()
                                    ->color(fn ($state) => $state === 'لم يتم التعيين' ? 'gray' : 'success'),

                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('📊 حالة الرحلة')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->size('lg'),
                            ]),
                    ])
                    ->columns(1),

                // معلومات الذهاب
                Infolists\Components\Section::make('🚀 معلومات الذهاب')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('departure_time')
                                    ->label('⏰ وقت الانطلاق')
                                    ->time('H:i')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('arrival_time')
                                    ->label('🏁 وقت الوصول')
                                    ->time('H:i')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('duration')
                                    ->label('⏱️ المدة')
                                    ->getStateUsing(fn ($record) => $record->getOutboundDuration() ?? '-')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-clock'),

                                Infolists\Components\TextEntry::make('fare')
                                    ->label('💰 السعر')
                                    ->money('SAR')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                // معلومات العودة (إذا كانت رحلة ذهاب وعودة)
                Infolists\Components\Section::make('🔙 معلومات العودة')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('return_departure_time')
                                    ->label('⏰ وقت الانطلاق')
                                    ->time('H:i')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('return_arrival_time')
                                    ->label('🏁 وقت الوصول')
                                    ->time('H:i')
                                    ->badge()
                                    ->color('info')
                                    ->size('lg'),

                                Infolists\Components\TextEntry::make('return_duration')
                                    ->label('⏱️ المدة')
                                    ->getStateUsing(fn ($record) => $record->getReturnDuration() ?? '-')
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-clock'),

                                Infolists\Components\TextEntry::make('return_fare')
                                    ->label('💰 السعر')
                                    ->money('SAR')
                                    ->badge()
                                    ->color('success')
                                    ->size('lg'),
                            ]),

                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(3)
                                ->schema([
                                    Infolists\Components\TextEntry::make('total_price')
                                        ->label('💵 السعر الإجمالي (منفصل)')
                                        ->getStateUsing(fn ($record) => $record->getRoundTripOriginalPrice())
                                        ->money('SAR')
                                        ->badge()
                                        ->color('gray'),

                                    Infolists\Components\TextEntry::make('round_trip_discount')
                                        ->label('🎁 قيمة الخصم')
                                        ->money('SAR')
                                        ->badge()
                                        ->color('danger')
                                        ->icon('heroicon-o-gift'),

                                    Infolists\Components\TextEntry::make('final_price')
                                        ->label('✅ السعر النهائي (ذهاب وعودة)')
                                        ->getStateUsing(fn ($record) => $record->getRoundTripPrice())
                                        ->money('SAR')
                                        ->badge()
                                        ->color('success')
                                        ->size('lg')
                                        ->weight('bold'),
                                ])
                        ]),

                        Infolists\Components\TextEntry::make('discount_percentage')
                            ->label('📊 نسبة الخصم')
                            ->getStateUsing(fn ($record) => $record->getDiscountPercentage() . '%')
                            ->badge()
                            ->color('warning')
                            ->visible(fn ($record) => $record->round_trip_discount > 0),
                    ])
                    ->visible(fn ($record) => $record->isRoundTrip())
                    ->collapsible()
                    ->collapsed(false),

                // محطات الذهاب
                Infolists\Components\Section::make('🚩 محطات الذهاب')
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
                                                ->label('المحطة')
                                                ->getStateUsing(fn ($record) => $record->stop?->getTranslation('name', 'ar'))
                                                ->badge()
                                                ->color('info')
                                                ->size('lg')
                                                ->icon('heroicon-o-map-pin')
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

                // محطات العودة
                Infolists\Components\Section::make('🔄 محطات العودة')
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
                                                ->label('المحطة')
                                                ->getStateUsing(fn ($record) => $record->stop?->getTranslation('name', 'ar'))
                                                ->badge()
                                                ->color('info')
                                                ->size('lg')
                                                ->icon('heroicon-o-map-pin')
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

                // معلومات الجدولة
                Infolists\Components\Section::make('📅 معلومات الجدولة')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('days_of_week')
                                    ->label('📆 أيام التشغيل')
                                    ->formatStateUsing(function ($state) {
                                        $days = [
                                            'Monday' => 'الاثنين',
                                            'Tuesday' => 'الثلاثاء',
                                            'Wednesday' => 'الأربعاء',
                                            'Thursday' => 'الخميس',
                                            'Friday' => 'الجمعة',
                                            'Saturday' => 'السبت',
                                            'Sunday' => 'الأحد',
                                        ];
                                        return collect($state)->map(fn($d) => $days[$d] ?? $d)->implode('، ');
                                    })
                                    ->badge()
                                    ->separator(',')
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('available_seats')
                                    ->label('💺 المقاعد المتاحة')
                                    ->suffix(' مقعد')
                                    ->badge()
                                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 10 ? 'warning' : 'danger'))
                                    ->size('lg')
                                    ->icon('heroicon-o-user-group'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('📊 حالة التوفر')
                                    ->getStateUsing(function ($record) {
                                        if (!$record->is_active) return 'غير نشط';
                                        if (!$record->hasSeatsAvailable()) return 'مكتمل';
                                        if ($record->driver_id) return 'جاهز للحجز';
                                        return 'بانتظار السائق';
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

                // ملخص الرحلة
                Infolists\Components\Section::make('📊 ملخص الرحلة')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('outbound_stops_count')
                                    ->label('🚩 عدد محطات الذهاب')
                                    ->getStateUsing(fn ($record) => $record->outboundStops()->count())
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-map-pin'),

                                Infolists\Components\TextEntry::make('return_stops_count')
                                    ->label('🔄 عدد محطات العودة')
                                    ->getStateUsing(fn ($record) => $record->returnStops()->count())
                                    ->badge()
                                    ->color('warning')
                                    ->icon('heroicon-o-map-pin')
                                    ->visible(fn ($record) => $record->isRoundTrip()),

                                Infolists\Components\TextEntry::make('total_duration')
                                    ->label('⏱️ إجمالي المدة')
                                    ->getStateUsing(fn ($record) => $record->getTotalDuration() ?? '-')
                                    ->badge()
                                    ->color('success')
                                    ->icon('heroicon-o-clock'),
                            ]),

                        Infolists\Components\TextEntry::make('full_schedule_info')
                            ->label('ℹ️ معلومات كاملة')
                            ->getStateUsing(fn ($record) => $record->getFullScheduleInfo())
                            ->columnSpanFull()
                            ->size('lg'),
                    ])
                    ->collapsible()
                    ->collapsed(true),

                // معلومات إضافية
                Infolists\Components\Section::make('📝 معلومات إضافية')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('رقم الجدول')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
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
