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

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Stops');
    }

    public static function getModelLabel(): string
    {
        return __('Stop');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stops');
    }

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Stop information'))
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('Stop type'))
                            ->options([
                                'city'   => __('City'),
                                'custom' => __('Custom stop'),
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
                            ->label(__('City'))
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
                                    ->label(__('Stop name (Arabic)'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.en')
                                    ->label(__('Stop name (English)'))
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('lat')
                                    ->label(__('Latitude'))
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->minValue(-90)
                                    ->maxValue(90),

                                Forms\Components\TextInput::make('lng')
                                    ->label(__('Longitude'))
                                    ->required()
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->minValue(-180)
                                    ->maxValue(180),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->label(__('Order'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(fn () => $this->getOwnerRecord()->stops()->max('order') + 1 ?? 1),

                                Forms\Components\TextInput::make('distance_from_previous_km')
                                    ->label(__('Distance from previous (km)'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix(__(' km'))
                                    ->default(0),

                                Forms\Components\TextInput::make('range_meters')
                                    ->label(__('Stop radius'))
                                    ->numeric()
                                    ->minValue(100)
                                    ->suffix(__(' meter'))
                                    ->default(2000)
                                    ->helperText(__('Allowed distance to board or drop around the stop')),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_time_minutes')
                                    ->label(__('Estimated time from previous'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(__(' minute'))
                                    ->default(0),

                                Forms\Components\TextInput::make('cumulative_time_minutes')
                                    ->label(__('Cumulative time from start'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(__(' minute'))
                                    ->default(0)
                                    ->helperText(__('Total time from the starting stop')),
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
                    ->label(__('Order #'))
                    ->sortable()
                    ->alignCenter()
                    ->weight('bold')
                    ->badge()
                    ->color(fn ($record, $rowLoop) => match (true) {
                        $rowLoop->first => 'success',
                        $rowLoop->last  => 'danger',
                        default         => 'primary',
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Stop name'))
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'city'   => 'info',
                        'custom' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'city'   => __('City'),
                        'custom' => __('Custom stop'),
                    }),

                Tables\Columns\TextColumn::make('city.name')
                    ->label(__('City'))
                    ->getStateUsing(fn ($record) => $record->city ? $record->city->getTranslation('name', 'ar') : '-')
                    ->badge()
                    ->color('success')
                    ->limit(20),

                Tables\Columns\TextColumn::make('lat')
                    ->label(__('Location'))
                    ->getStateUsing(fn ($record) => number_format($record->lat, 4) . ', ' . number_format($record->lng, 4))
                    ->copyable()
                    ->copyMessage(__('Coordinates copied'))
                    ->size('xs')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('distance_from_previous_km')
                    ->label(__('Distance'))
                    ->numeric(2)
                    ->suffix(__(' km'))
                    ->alignCenter()
                    ->sortable()
                    ->color('primary')
                    ->tooltip(__('Distance from previous stop')),

                Tables\Columns\TextColumn::make('estimated_time_minutes')
                    ->label(__('Estimated time'))
                    ->getStateUsing(function ($record) {
                        if ($record->estimated_time_minutes < 60) {
                            return $record->estimated_time_minutes . ' ' . __('min');
                        }
                        $hours   = floor($record->estimated_time_minutes / 60);
                        $minutes = $record->estimated_time_minutes % 60;
                        return $minutes > 0
                            ? "{$hours} " . __('hr') . " {$minutes} " . __('min')
                            : "{$hours} " . __('hr');
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->tooltip(__('Estimated time from previous stop')),

                Tables\Columns\TextColumn::make('cumulative_time_minutes')
                    ->label(__('Cumulative time'))
                    ->getStateUsing(function ($record) {
                        if ($record->cumulative_time_minutes < 60) {
                            return $record->cumulative_time_minutes . ' ' . __('min');
                        }
                        $hours   = floor($record->cumulative_time_minutes / 60);
                        $minutes = $record->cumulative_time_minutes % 60;
                        return $minutes > 0
                            ? "{$hours} " . __('hr') . " {$minutes} " . __('min')
                            : "{$hours} " . __('hr');
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->tooltip(__('Cumulative time from start')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Stop type'))
                    ->options([
                        'city'   => __('City'),
                        'custom' => __('Custom stop'),
                    ]),

                Tables\Filters\SelectFilter::make('city_id')
                    ->label(__('City'))
                    ->relationship('city', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'ar'))
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add stop'))
                    ->icon('heroicon-o-plus')
                    ->modalHeading(__('Add new stop'))
                    ->successNotificationTitle(__('Stop added successfully')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->modalHeading(__('Edit stop'))
                        ->successNotificationTitle(__('Stop updated successfully')),

                    Tables\Actions\Action::make('view_on_map')
                        ->label(__('View on map'))
                        ->icon('heroicon-o-map')
                        ->color('success')
                        ->url(fn ($record) => "https://www.google.com/maps/@{$record->lat},{$record->lng},15z")
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading(__('Delete stop'))
                        ->successNotificationTitle(__('Stop deleted successfully'))
                        ->before(function ($record) {
                            $route = $record->route;
                            $order = $record->order;

                            $route->stops()
                                ->where('order', '>', $order)
                                ->each(function ($stop) {
                                    $stop->decrement('order');
                                });
                        }),
                ])
                    ->label(__('Actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading(__('Delete selected stops')),
                ]),
            ])
            ->emptyStateHeading(__('No stops found'))
            ->emptyStateDescription(__('Create stops for the route'))
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add stop'))
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
