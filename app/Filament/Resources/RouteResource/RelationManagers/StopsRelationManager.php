<?php

namespace App\Filament\Resources\PublicBusRouteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class StopsRelationManager extends RelationManager
{
    protected static string $relationship = 'stops';

    protected static ?string $title = 'المحطات';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المحطة')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('نوع المحطة')
                            ->options([
                                'city' => 'مدينة',
                                'custom' => 'محطة مخصصة',
                            ])
                            ->default('city')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'custom') {
                                    $set('city_id', null);
                                }
                            }),

                        Forms\Components\Select::make('city_id')
                            ->label('المدينة')
                            ->relationship('city', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('type') === 'city')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $city = \App\Models\City::find($state);
                                    if ($city) {
                                        $set('name.ar', $city->getTranslation('name', 'ar'));
                                        $set('name.en', $city->getTranslation('name', 'en'));
                                        $set('lat', $city->lat);
                                        $set('lng', $city->lng);
                                    }
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label('اسم المحطة (عربي)')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.en')
                                    ->label('اسم المحطة (English)')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('lat')
                                    ->label('خط العرض')
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->minValue(-90)
                                    ->maxValue(90),

                                Forms\Components\TextInput::make('lng')
                                    ->label('خط الطول')
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->minValue(-180)
                                    ->maxValue(180),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->label('الترتيب')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(fn () => $this->getOwnerRecord()->stops()->max('order') + 1 ?? 1),

                                Forms\Components\TextInput::make('distance_from_previous_km')
                                    ->label('المسافة من السابقة (كم)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('كم')
                                    ->default(0),

                                Forms\Components\TextInput::make('range_meters')
                                    ->label('نطاق المحطة')
                                    ->numeric()
                                    ->minValue(100)
                                    ->suffix('متر')
                                    ->default(2000)
                                    ->helperText('المسافة المسموحة للركوب/النزول حول المحطة'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_time_minutes')
                                    ->label('الوقت المقدر من السابقة')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('دقيقة')
                                    ->default(0),

                                Forms\Components\TextInput::make('cumulative_time_minutes')
                                    ->label('الوقت التراكمي من البداية')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('دقيقة')
                                    ->default(0)
                                    ->helperText('الوقت الإجمالي من محطة البداية'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('order')
            ->defaultSort('order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->weight('bold')
                    ->badge()
                    ->color(fn ($record, $rowLoop) => match (true) {
                        $rowLoop->first => 'success',
                        $rowLoop->last => 'danger',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المحطة')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'city' => 'info',
                        'custom' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'city' => 'مدينة',
                        'custom' => 'محطة مخصصة',
                    }),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('المدينة')
                    ->getStateUsing(fn ($record) => $record->city ? $record->city->getTranslation('name', 'ar') : '-')
                    ->badge()
                    ->color('success')
                    ->limit(20),

                Tables\Columns\TextColumn::make('lat')
                    ->label('الموقع')
                    ->getStateUsing(fn ($record) => number_format($record->lat, 4) . ', ' . number_format($record->lng, 4))
                    ->copyable()
                    ->copyMessage('تم نسخ الإحداثيات')
                    ->size('xs')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('distance_from_previous_km')
                    ->label('المسافة')
                    ->numeric(2)
                    ->suffix(' كم')
                    ->alignCenter()
                    ->sortable()
                    ->color('primary')
                    ->tooltip('المسافة من المحطة السابقة'),

                Tables\Columns\TextColumn::make('estimated_time_minutes')
                    ->label('الوقت')
                    ->getStateUsing(function ($record) {
                        if ($record->estimated_time_minutes < 60) {
                            return $record->estimated_time_minutes . ' د';
                        }
                        $hours = floor($record->estimated_time_minutes / 60);
                        $minutes = $record->estimated_time_minutes % 60;
                        return $minutes > 0 ? "{$hours} س {$minutes} د" : "{$hours} س";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->tooltip('الوقت المقدر من المحطة السابقة'),

                Tables\Columns\TextColumn::make('cumulative_time_minutes')
                    ->label('الوقت التراكمي')
                    ->getStateUsing(function ($record) {
                        if ($record->cumulative_time_minutes < 60) {
                            return $record->cumulative_time_minutes . ' د';
                        }
                        $hours = floor($record->cumulative_time_minutes / 60);
                        $minutes = $record->cumulative_time_minutes % 60;
                        return $minutes > 0 ? "{$hours} س {$minutes} د" : "{$hours} س";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->tooltip('الوقت التراكمي من البداية'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع المحطة')
                    ->options([
                        'city' => 'مدينة',
                        'custom' => 'محطة مخصصة',
                    ]),

                Tables\Filters\SelectFilter::make('city_id')
                    ->label('المدينة')
                    ->relationship('city', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة محطة')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('إضافة محطة جديدة')
                    ->successNotificationTitle('تم إضافة المحطة بنجاح'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->modalHeading('تعديل المحطة')
                        ->successNotificationTitle('تم تحديث المحطة بنجاح'),

                    Tables\Actions\Action::make('view_on_map')
                        ->label('عرض على الخريطة')
                        ->icon('heroicon-o-map')
                        ->color('success')
                        ->url(fn ($record) => "https://www.google.com/maps/@{$record->lat},{$record->lng},15z")
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading('حذف المحطة')
                        ->successNotificationTitle('تم حذف المحطة بنجاح')
                        ->before(function ($record) {
                            // إعادة ترتيب المحطات المتبقية
                            $route = $record->route;
                            $order = $record->order;

                            $route->stops()
                                ->where('order', '>', $order)
                                ->each(function ($stop) {
                                    $stop->decrement('order');
                                });
                        }),
                ])
                    ->label('إجراءات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('حذف المحطات المحددة'),
                ]),
            ])
            ->emptyStateHeading('لا توجد محطات')
            ->emptyStateDescription('قم بإنشاء محطات للمسار')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة محطة')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
