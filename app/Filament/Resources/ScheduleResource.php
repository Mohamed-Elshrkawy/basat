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


    protected static ?int $navigationSort = 13;


    public static function getNavigationLabel(): string
    {
        return __('Schedule Trips');
    }

    public static function getModelLabel(): string
    {
        return __('Trip Schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Schedule Trips');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Locations Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make(__('Trip Information'))
                        ->schema([
                            Forms\Components\Select::make('route_id')
                                ->label(__('Route'))
                                ->options(fn () => Route::active()->get()->mapWithKeys(fn ($route) => [
                                    $route->id => $route->getFullRouteName()
                                ]))
                                ->searchable()
                                ->required()
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('driver_id')
                                ->label(__('Driver'))
                                ->options(User::where('user_type', 'driver')->pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->helperText(__('Driver Can Be Assigned Later'))
                                ->columnSpanFull(),

                            Forms\Components\Radio::make('trip_type')
                                ->label(__('TripType'))
                                ->options([
                                    'one_way' => __('OneWay'),
                                    'round_trip' => __('RoundTrip'),
                                ])
                                ->required()
                                ->default('one_way')
                                ->live()
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Wizard\Step::make(__('Outbound Information'))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TimePicker::make('departure_time')
                                        ->label(__('Departure Time First Stop'))
                                        ->seconds(false)
                                        ->required(),

                                    Forms\Components\TimePicker::make('arrival_time')
                                        ->label(__('Arrival Time Last Stop'))
                                        ->seconds(false)
                                        ->required()
                                        ->after('departure_time'),
                                ]),

                            Forms\Components\TextInput::make('fare')
                                ->label(__('Fare Price'))
                                ->numeric()
                                ->prefix(__('SAR'))
                                ->required()
                                ->minValue(0)
                                ->maxValue(9999.99),
                        ]),

                    Forms\Components\Wizard\Step::make(__('Return Information'))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TimePicker::make('return_departure_time')
                                        ->label(__('Return Departure Time'))
                                        ->seconds(false)
                                        ->required(fn (Forms\Get $get) => $get('trip_type') === 'round_trip'),

                                    Forms\Components\TimePicker::make('return_arrival_time')
                                        ->label(__('Return Arrival Time'))
                                        ->seconds(false)
                                        ->required(fn (Forms\Get $get) => $get('trip_type') === 'round_trip')
                                        ->after('return_departure_time'),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('return_fare')
                                        ->label(__('Return Fare'))
                                        ->numeric()
                                        ->prefix(__('SAR'))
                                        ->required(fn (Forms\Get $get) => $get('trip_type') === 'round_trip')
                                        ->minValue(0)
                                        ->maxValue(9999.99),

                                    Forms\Components\TextInput::make('round_trip_discount')
                                        ->label(__('Round Trip Discount'))
                                        ->numeric()
                                        ->prefix(__('SAR'))
                                        ->helperText(__('Discount When Buying Round Trip'))
                                        ->minValue(0)
                                        ->maxValue(9999.99),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('trip_type') === 'round_trip'),

                    Forms\Components\Wizard\Step::make(__('Outbound Stops'))
                        ->schema([
                            Forms\Components\Repeater::make('outbound Stops')
                                ->relationship('scheduleStops', fn ($query) => $query->where('direction', 'outbound'))
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Select::make('stop_id')
                                                ->label(__('Stop'))
                                                ->options(fn () => Stop::active()->get()->mapWithKeys(fn ($stop) => [
                                                    $stop->id => $stop->getTranslation('name', 'ar')
                                                ]))
                                                ->searchable()
                                                ->required()
                                                ->distinct()
                                                ->columnSpan(2),

                                            Forms\Components\TimePicker::make('arrival_time')
                                                ->label(__('Arrival Time'))
                                                ->seconds(false)
                                                ->required(),

                                            Forms\Components\TimePicker::make('departure_time')
                                                ->label(__('Departure Time'))
                                                ->seconds(false)
                                                ->required()
                                                ->after('arrival_time'),

                                            Forms\Components\Hidden::make('direction')->default('outbound'),
                                        ]),
                                ])
                                ->orderColumn('order')
                                ->reorderable(true)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    Stop::find($state['stop_id'])?->getTranslation('name', 'ar') ?? __('NewStop')
                                )
                                ->addActionLabel(__('AddStop'))
                                ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation())
                                ->columnSpanFull()
                                ->minItems(1)
                                ->defaultItems(1),
                        ]),

                    Forms\Components\Wizard\Step::make(__('Return Stops'))
                        ->schema([
                            Forms\Components\Repeater::make('returnStops')
                                ->relationship('scheduleStops', fn ($query) => $query->where('direction', 'return'))
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Select::make('stop_id')
                                                ->label(__('Stop'))
                                                ->options(fn () => Stop::active()->get()->mapWithKeys(fn ($stop) => [
                                                    $stop->id => $stop->getTranslation('name', 'ar')
                                                ]))
                                                ->searchable()
                                                ->required()
                                                ->distinct()
                                                ->columnSpan(2),

                                            Forms\Components\TimePicker::make('arrival_time')
                                                ->label(__('Arrival Time'))
                                                ->seconds(false)
                                                ->required(),

                                            Forms\Components\TimePicker::make('departure_time')
                                                ->label(__('Departure Time'))
                                                ->seconds(false)
                                                ->required()
                                                ->after('arrival_time'),

                                            Forms\Components\Hidden::make('direction')->default('return'),
                                        ]),
                                ])
                                ->orderColumn('order')
                                ->reorderable(true)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    Stop::find($state['stop_id'])?->getTranslation('name', 'ar') ?? __('New Stop')
                                )
                                ->addActionLabel(__('Add Stop'))
                                ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation())
                                ->columnSpanFull()
                                ->minItems(1)
                                ->defaultItems(1),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('trip_type') === 'round_trip'),

                    Forms\Components\Wizard\Step::make(__('Scheduling'))
                        ->schema([
                            Forms\Components\CheckboxList::make('days_of_week')
                                ->label(__('Trip Days'))
                                ->options([
                                    'Monday' => __('Monday'),
                                    'Tuesday' => __('Tuesday'),
                                    'Wednesday' => __('Wednesday'),
                                    'Thursday' => __('Thursday'),
                                    'Friday' => __('Friday'),
                                    'Saturday' => __('Saturday'),
                                    'Sunday' => __('Sunday'),
                                ])
                                ->columns(4)
                                ->required()
                                ->minItems(1)
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('available_seats')
                                        ->label(__('Available Seats'))
                                        ->numeric()
                                        ->default(50)
                                        ->required()
                                        ->minValue(1)
                                        ->maxValue(100),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('Active Trip'))
                                        ->default(true)
                                        ->required(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

        public static function getPages(): array
        { return [
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
