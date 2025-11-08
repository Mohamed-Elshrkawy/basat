<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\Route;
use App\Models\Stop;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'جدولة الرحلات';

    protected static ?string $modelLabel = 'جدول رحلة';

    protected static ?string $pluralModelLabel = 'جدولة الرحلات';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationGroup = 'إدارة المواقع';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // خطوة 1: معلومات أساسية
                    Forms\Components\Wizard\Step::make('معلومات الرحلة')
                        ->schema([
                            Forms\Components\Select::make('route_id')
                                ->label('المسار')
                                ->options(fn () => Route::active()->get()->mapWithKeys(fn ($route) => [
                                    $route->id => $route->getFullRouteName()
                                ]))
                                ->searchable()
                                ->required()
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('driver_id')
                                ->label('السائق')
                                ->options(User::where('user_type', 'driver')->pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->helperText('يمكن تعيين السائق لاحقاً')
                                ->columnSpanFull(),

                            Forms\Components\Radio::make('trip_type')
                                ->label('نوع الرحلة')
                                ->options([
                                    'one_way' => 'ذهاب فقط',
                                    'round_trip' => 'ذهاب وعودة',
                                ])
                                ->required()
                                ->default('one_way')
                                ->live()
                                ->columnSpanFull(),
                        ]),

                    // خطوة 2: أوقات وأسعار الذهاب
                    Forms\Components\Wizard\Step::make('معلومات الذهاب')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TimePicker::make('departure_time')
                                        ->label('⏰ وقت الانطلاق (أول محطة)')
                                        ->seconds(false)
                                        ->required(),

                                    Forms\Components\TimePicker::make('arrival_time')
                                        ->label('🏁 وقت الوصول (آخر محطة)')
                                        ->seconds(false)
                                        ->required()
                                        ->after('departure_time'),
                                ]),

                            Forms\Components\TextInput::make('fare')
                                ->label('💰 سعر تذكرة الذهاب')
                                ->numeric()
                                ->prefix('ر.س')
                                ->required()
                                ->minValue(0)
                                ->maxValue(9999.99),
                        ]),

                    // خطوة 3: أوقات وأسعار العودة
                    Forms\Components\Wizard\Step::make('معلومات العودة')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TimePicker::make('return_departure_time')
                                        ->label('⏰ وقت انطلاق العودة (من آخر محطة)')
                                        ->seconds(false)
                                        ->required(fn (Forms\Get $get) => $get('trip_type') === 'round_trip'),

                                    Forms\Components\TimePicker::make('return_arrival_time')
                                        ->label('🏁 وقت وصول العودة (لأول محطة)')
                                        ->seconds(false)
                                        ->required(fn (Forms\Get $get) => $get('trip_type') === 'round_trip')
                                        ->after('return_departure_time'),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('return_fare')
                                        ->label('💰 سعر تذكرة العودة')
                                        ->numeric()
                                        ->prefix('ر.س')
                                        ->required(fn (Forms\Get $get) => $get('trip_type') === 'round_trip')
                                        ->minValue(0)
                                        ->maxValue(9999.99),

                                    Forms\Components\TextInput::make('round_trip_discount')
                                        ->label('🎁 قيمة الخصم (ذهاب وعودة)')
                                        ->numeric()
                                        ->prefix('ر.س')
                                        ->helperText('الخصم عند شراء ذهاب وعودة معاً')
                                        ->minValue(0)
                                        ->maxValue(9999.99),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('trip_type') === 'round_trip'),

                    // خطوة 4: محطات الذهاب
                    Forms\Components\Wizard\Step::make('محطات الذهاب')
                        ->schema([
                            Forms\Components\Repeater::make('outboundStops')
                                ->relationship('scheduleStops', function ($query) {
                                    return $query->where('direction', 'outbound');
                                })
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Select::make('stop_id')
                                                ->label('المحطة')
                                                ->options(fn () => Stop::active()->get()->mapWithKeys(fn ($stop) => [
                                                    $stop->id => $stop->getTranslation('name', 'ar')
                                                ]))
                                                ->searchable()
                                                ->required()
                                                ->distinct()
                                                ->columnSpan(2),

                                            Forms\Components\TimePicker::make('arrival_time')
                                                ->label('⏰ وقت الوصول')
                                                ->seconds(false)
                                                ->required(),

                                            Forms\Components\TimePicker::make('departure_time')
                                                ->label('🚀 وقت المغادرة')
                                                ->seconds(false)
                                                ->required()
                                                ->after('arrival_time'),

                                            Forms\Components\Hidden::make('direction')
                                                ->default('outbound'),
                                        ]),
                                ])
                                ->orderColumn('order')
                                ->reorderable(true)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    Stop::find($state['stop_id'])?->getTranslation('name', 'ar') ?? 'محطة جديدة'
                                )
                                ->addActionLabel('➕ إضافة محطة')
                                ->deleteAction(
                                    fn (Forms\Components\Actions\Action $action) => $action
                                        ->requiresConfirmation()
                                )
                                ->columnSpanFull()
                                ->minItems(1)
                                ->defaultItems(1),
                        ]),

                    // خطوة 5: محطات العودة
                    Forms\Components\Wizard\Step::make('محطات العودة')
                        ->schema([
                            Forms\Components\Repeater::make('returnStops')
                                ->relationship('scheduleStops', function ($query) {
                                    return $query->where('direction', 'return');
                                })
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Select::make('stop_id')
                                                ->label('المحطة')
                                                ->options(fn () => Stop::active()->get()->mapWithKeys(fn ($stop) => [
                                                    $stop->id => $stop->getTranslation('name', 'ar')
                                                ]))
                                                ->searchable()
                                                ->required()
                                                ->distinct()
                                                ->columnSpan(2),

                                            Forms\Components\TimePicker::make('arrival_time')
                                                ->label('⏰ وقت الوصول')
                                                ->seconds(false)
                                                ->required(),

                                            Forms\Components\TimePicker::make('departure_time')
                                                ->label('🚀 وقت المغادرة')
                                                ->seconds(false)
                                                ->required()
                                                ->after('arrival_time'),

                                            Forms\Components\Hidden::make('direction')
                                                ->default('return'),
                                        ]),
                                ])
                                ->orderColumn('order')
                                ->reorderable(true)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    Stop::find($state['stop_id'])?->getTranslation('name', 'ar') ?? 'محطة جديدة'
                                )
                                ->addActionLabel('➕ إضافة محطة')
                                ->deleteAction(
                                    fn (Forms\Components\Actions\Action $action) => $action
                                        ->requiresConfirmation()
                                )
                                ->columnSpanFull()
                                ->minItems(1)
                                ->defaultItems(1),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('trip_type') === 'round_trip'),

                    // خطوة 6: الجدولة
                    Forms\Components\Wizard\Step::make('الجدولة')
                        ->schema([
                            Forms\Components\CheckboxList::make('days_of_week')
                                ->label('أيام تشغيل الرحلة')
                                ->options([
                                    'Monday' => 'الاثنين',
                                    'Tuesday' => 'الثلاثاء',
                                    'Wednesday' => 'الأربعاء',
                                    'Thursday' => 'الخميس',
                                    'Friday' => 'الجمعة',
                                    'Saturday' => 'السبت',
                                    'Sunday' => 'الأحد',
                                ])
                                ->columns(4)
                                ->required()
                                ->minItems(1)
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('available_seats')
                                        ->label('عدد المقاعد المتاحة')
                                        ->numeric()
                                        ->default(50)
                                        ->required()
                                        ->minValue(1)
                                        ->maxValue(100),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('رحلة نشطة')
                                        ->default(true)
                                        ->required(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('route.name')
                    ->label('المسار')
                    ->getStateUsing(fn ($record) => $record->route?->getTranslation('name', 'ar'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('trip_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string =>
                    $state === 'one_way' ? 'ذهاب' : 'ذهاب وعودة'
                    )
                    ->colors([
                        'info' => 'one_way',
                        'success' => 'round_trip',
                    ]),

                Tables\Columns\TextColumn::make('departure_time')
                    ->label('⏰ الانطلاق')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fare')
                    ->label('💰 السعر')
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('driver.name')
                    ->label('السائق')
                    ->searchable()
                    ->default('لم يعين')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_seats')
                    ->label('المقاعد')
                    ->badge()
                    ->color(fn ($state) => $state > 20 ? 'success' : ($state > 10 ? 'warning' : 'danger'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_of_week')
                    ->label('الأيام')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $days = [
                            'Monday' => 'إثنين',
                            'Tuesday' => 'ثلاثاء',
                            'Wednesday' => 'أربعاء',
                            'Thursday' => 'خميس',
                            'Friday' => 'جمعة',
                            'Saturday' => 'سبت',
                            'Sunday' => 'أحد',
                        ];
                        return collect($state)->map(fn($d) => $days[$d] ?? $d)->implode('، ');
                    })
                    ->wrap()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trip_type')
                    ->label('نوع الرحلة')
                    ->options([
                        'one_way' => 'ذهاب فقط',
                        'round_trip' => 'ذهاب وعودة',
                    ]),

                Tables\Filters\SelectFilter::make('route_id')
                    ->label('المسار')
                    ->options(fn () => Route::active()->get()->mapWithKeys(fn ($route) => [
                        $route->id => $route->getTranslation('name', 'ar')
                    ]))
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),

                Tables\Filters\Filter::make('has_seats')
                    ->label('توفر المقاعد')
                    ->query(fn ($query) => $query->where('available_seats', '>', 0)),

                Tables\Filters\TernaryFilter::make('has_driver')
                    ->label('السائق')
                    ->placeholder('الكل')
                    ->trueLabel('معين')
                    ->falseLabel('غير معين')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('driver_id'),
                        false: fn ($query) => $query->whereNull('driver_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('departure_time', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'view' => Pages\ViewSchedule::route('/{record}'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::active()->count();
        return $count > 0 ? 'success' : 'gray';
    }
}
