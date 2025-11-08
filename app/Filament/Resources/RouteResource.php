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

    protected static ?int $navigationSort = 12;


    public static function getNavigationLabel(): string
    {
        return __('Routes');
    }

    public static function getModelLabel(): string
    {
        return __('Route');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Routes');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Locations Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Route Information'))
                    ->description(__('Enter the basic information for the route'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.ar')
                                    ->label(__('Route Name (Arabic)'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('Example: Riyadh - Jeddah Express Route (Arabic)')),

                                Forms\Components\TextInput::make('name.en')
                                    ->label(__('Route Name (English)'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('Example: Riyadh - Jeddah Express Route')),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('start_city_id')
                                    ->label(__('Start City'))
                                    ->options(fn() => City::active()->get()->mapWithKeys(fn($city) => [
                                        $city->id => $city->getTranslation('name', 'ar')
                                    ]))
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('end_city_id')
                                    ->label(__('End City'))
                                    ->options(fn() => City::active()->get()->mapWithKeys(fn($city) => [
                                        $city->id => $city->getTranslation('name', 'ar')
                                    ]))
                                    ->searchable()
                                    ->required()
                                    ->different('start_city_id'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('range_km')
                                    ->label(__('Distance (Km)'))
                                    ->numeric()
                                    ->suffix(__('Km'))
                                    ->minValue(0)
                                    ->maxValue(9999.99)
                                    ->placeholder(__('Example: 950')),

                                Forms\Components\Toggle::make('is_active')
                                    ->label(__('Active Route'))
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make(__('Route Stops'))
                    ->description(__('Add the stops in the order the bus passes through them (times are defined in schedules)'))
                    ->schema([
                        Forms\Components\Repeater::make('routeStops')
                            ->relationship('routeStops')
                            ->schema([
                                Forms\Components\Select::make('stop_id')
                                    ->label(__('Stop'))
                                    ->options(fn() => Stop::active()->get()->mapWithKeys(fn($stop) => [
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
                            ->itemLabel(fn(array $state): ?string =>
                                Stop::find($state['stop_id'])?->getTranslation('name', 'ar') ?? __('New Stop')
                            )
                            ->addActionLabel(__('Add Stop'))
                            ->deleteAction(
                                fn(Forms\Components\Actions\Action $action) => $action->requiresConfirmation()
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
                    ->label(__('#'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Route Name'))
                    ->searchable(query: function ($query, $search) {
                        $query->where('name->ar', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('startCity.name')
                    ->label(__('From'))
                    ->getStateUsing(fn($record) => $record->startCity?->getTranslation('name', 'ar'))
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('arrow')
                    ->label('')
                    ->icon('heroicon-o-arrow-long-right')
                    ->size('lg')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('endCity.name')
                    ->label(__('To'))
                    ->getStateUsing(fn($record) => $record->endCity?->getTranslation('name', 'ar'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stops_count')
                    ->label(__('Stops Count'))
                    ->getStateUsing(fn($record) => $record->stops()->count())
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->label(__('Trips Count'))
                    ->getStateUsing(fn($record) => $record->schedules()->count())
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('range_km')
                    ->label(__('Distance'))
                    ->suffix(__(' Km'))
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),

                Tables\Filters\SelectFilter::make('start_city_id')
                    ->label(__('Start City'))
                    ->options(fn() => City::active()->get()->mapWithKeys(fn($city) => [
                        $city->id => $city->getTranslation('name', 'ar')
                    ]))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('end_city_id')
                    ->label(__('End City'))
                    ->options(fn() => City::active()->get()->mapWithKeys(fn($city) => [
                        $city->id => $city->getTranslation('name', 'ar')
                    ]))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('View')),
                Tables\Actions\EditAction::make()->label(__('Edit')),
                Tables\Actions\DeleteAction::make()->label(__('Delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(__('Delete Selected')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
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
