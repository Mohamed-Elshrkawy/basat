<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;
use App\Models\Route;
use App\Models\City;
use App\Models\Stop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-long-right';

    protected static ?string $navigationLabel = 'المسارات';

    protected static ?string $modelLabel = 'مسار';

    protected static ?string $pluralModelLabel = 'المسارات';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationGroup = 'إدارة المواقع';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المسار')
                    ->description('أدخل المعلومات الأساسية للمسار')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label('اسم المسار (عربي)')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('مثال: مسار الرياض - جدة السريع'),

                                Forms\Components\TextInput::make('name.en')
                                    ->label('اسم المسار (English)')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Example: Riyadh - Jeddah Express Route'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('start_city_id')
                                    ->label('مدينة البداية')
                                    ->options(fn () => City::active()->get()->mapWithKeys(fn ($city) => [
                                        $city->id => $city->getTranslation('name', 'ar')
                                    ]))
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('end_city_id')
                                    ->label('مدينة النهاية')
                                    ->options(fn () => City::active()->get()->mapWithKeys(fn ($city) => [
                                        $city->id => $city->getTranslation('name', 'ar')
                                    ]))
                                    ->searchable()
                                    ->required()
                                    ->different('start_city_id'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('range_km')
                                    ->label('المسافة (كم)')
                                    ->numeric()
                                    ->suffix('كم')
                                    ->minValue(0)
                                    ->maxValue(9999.99)
                                    ->placeholder('مثال: 950'),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('مسار نشط')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('محطات المسار')
                    ->description('أضف المحطات بترتيب مرور الحافلة بها (الأوقات تُحدد في الرحلات)')
                    ->schema([
                        Forms\Components\Repeater::make('routeStops')
                            ->relationship('routeStops')
                            ->schema([
                                Forms\Components\Select::make('stop_id')
                                    ->label('المحطة')
                                    ->options(fn () => Stop::active()->get()->mapWithKeys(fn ($stop) => [
                                        $stop->id => $stop->getTranslation('name', 'ar')
                                    ]))
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->columnSpanFull(),
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
                            ->minItems(2)
                            ->defaultItems(1),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المسار')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('name->ar', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('startCity.name')
                    ->label('من')
                    ->getStateUsing(fn ($record) => $record->startCity?->getTranslation('name', 'ar'))
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('arrow')
                    ->label('')
                    ->icon('heroicon-o-arrow-long-right')
                    ->size('lg')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('endCity.name')
                    ->label('إلى')
                    ->getStateUsing(fn ($record) => $record->endCity?->getTranslation('name', 'ar'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stops_count')
                    ->label('عدد المحطات')
                    ->getStateUsing(fn ($record) => $record->stops()->count())
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->label('عدد الرحلات')
                    ->getStateUsing(fn ($record) => $record->schedules()->count())
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('range_km')
                    ->label('المسافة')
                    ->suffix(' كم')
                    ->numeric(1)
                    ->sortable()
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),

                Tables\Filters\SelectFilter::make('start_city_id')
                    ->label('مدينة البداية')
                    ->options(fn () => City::active()->get()->mapWithKeys(fn ($city) => [
                        $city->id => $city->getTranslation('name', 'ar')
                    ]))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('end_city_id')
                    ->label('مدينة النهاية')
                    ->options(fn () => City::active()->get()->mapWithKeys(fn ($city) => [
                        $city->id => $city->getTranslation('name', 'ar')
                    ]))
                    ->searchable(),
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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'view' => Pages\ViewRoute::route('/{record}'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? 'success' : 'gray';
    }
}
