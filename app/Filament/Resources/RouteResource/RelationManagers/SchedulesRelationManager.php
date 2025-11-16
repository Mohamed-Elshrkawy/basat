<?php

namespace App\Filament\Resources\RouteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('Trip schedules');
    }

    public static function getModelLabel(): string
    {
        return __('Schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Schedules');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Trip information'))
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->label(__('Bus'))
                            ->relationship('vehicle', 'plate_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - {$record->plate_number}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('Select the bus that will run the trip')),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('departure_time')
                                    ->label(__('Departure time'))
                                    ->required()
                                    ->seconds(false)
                                    ->helperText(__('Departure time from the first stop')),

                                Forms\Components\TextInput::make('price_per_km')
                                    ->label(__('Price per kilometer'))
                                    ->numeric()
                                    ->prefix(__('SAR'))
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(2.00)
                                    ->helperText(__('The price for each stop will be calculated automatically')),
                            ]),

                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label(__('Operating days'))
                            ->options([
                                'saturday'  => __('Saturday'),
                                'sunday'    => __('Sunday'),
                                'monday'    => __('Monday'),
                                'tuesday'   => __('Tuesday'),
                                'wednesday' => __('Wednesday'),
                                'thursday'  => __('Thursday'),
                                'friday'    => __('Friday'),
                            ])
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->default(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday']),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Activate trip'))
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('departure_time')
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label(__('Bus'))
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->vehicle ? "{$record->vehicle->brand} {$record->vehicle->model}" : '-')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('departure_time')
                    ->label(__('Departure time'))
                    ->time('H:i')
                    ->sortable()
                    ->badge()
                    ->size('md')
                    ->color('success')
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('arrival_time')
                    ->label(__('Final arrival time'))
                    ->getStateUsing(function ($record) {
                        $lastStop = $record->stopSchedules()->orderBy('order', 'desc')->first();
                        return $lastStop ? \Carbon\Carbon::parse($lastStop->arrival_time)->format('H:i') : '-';
                    })
                    ->badge()
                    ->size('md')
                    ->color('danger')
                    ->icon('heroicon-o-flag'),

                Tables\Columns\TextColumn::make('duration')
                    ->label(__('Duration'))
                    ->getStateUsing(function ($record) {
                        $firstStop = $record->stopSchedules()->orderBy('order')->first();
                        $lastStop = $record->stopSchedules()->orderBy('order', 'desc')->first();

                        if (!$firstStop || !$lastStop) return '-';

                        $start = \Carbon\Carbon::parse($firstStop->departure_time);
                        $end   = \Carbon\Carbon::parse($lastStop->arrival_time);
                        $diff  = $start->diffInMinutes($end);

                        if ($diff < 60) return $diff . ' ' . __('min');
                        $hours = floor($diff / 60);
                        $mins  = $diff % 60;
                        return $mins > 0
                            ? "{$hours} " . __('hr') . " {$mins} " . __('min')
                            : "{$hours} " . __('hr');
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('stops_count')
                    ->label(__('Number of stops'))
                    ->getStateUsing(fn ($record) => $record->stopSchedules()->count())
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('days_of_week')
                    ->label(__('Working days'))
                    ->formatStateUsing(function ($record) {
                        $daysArray = $record->days_of_week;

                        if (!$daysArray || !is_array($daysArray) || count($daysArray) === 0) {
                            return '-';
                        }

                        $days = [
                            'saturday'  => __('Sat'),
                            'sunday'    => __('Sun'),
                            'monday'    => __('Mon'),
                            'tuesday'   => __('Tue'),
                            'wednesday' => __('Wed'),
                            'thursday'  => __('Thu'),
                            'friday'    => __('Fri'),
                        ];

                        $dayNames = array_map(fn($day) => $days[$day] ?? $day, $daysArray);
                        return implode(', ', $dayNames);
                    })
                    ->wrap()
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->label(__('Bus'))
                    ->relationship('vehicle', 'plate_number')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - {$record->plate_number}")
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_schedule')
                    ->label(__('Add trip'))
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading(__('Create new trip'))
                    ->modalWidth('2xl')
                    ->visible(function ($livewire) {
                        try {
                            $route = $livewire->getOwnerRecord();
                            return $route && $route->stops()->count() >= 2;
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->form([
                        Forms\Components\Select::make('vehicle_id')
                            ->label(__('Bus'))
                            ->relationship('vehicle', 'plate_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - {$record->plate_number}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(__('Select the bus that will run the trip')),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('departure_time')
                                    ->label(__('Departure time'))
                                    ->required()
                                    ->seconds(false)
                                    ->helperText(__('Departure time from the first stop')),

                                Forms\Components\TextInput::make('price_per_km')
                                    ->label(__('Price per kilometer'))
                                    ->numeric()
                                    ->prefix(__('SAR'))
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(2.00)
                                    ->helperText(__('The price for each stop will be calculated automatically')),
                            ]),

                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label(__('Operating days'))
                            ->options([
                                'saturday'  => __('Saturday'),
                                'sunday'    => __('Sunday'),
                                'monday'    => __('Monday'),
                                'tuesday'   => __('Tuesday'),
                                'wednesday' => __('Wednesday'),
                                'thursday'  => __('Thursday'),
                                'friday'    => __('Friday'),
                            ])
                            ->required()
                            ->columns(4)
                            ->gridDirection('row')
                            ->default(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday']),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Activate trip'))
                            ->default(true)
                            ->inline(false),
                    ])
                    ->action(function (array $data, $livewire) {
                        try {
                            $route = $livewire->getOwnerRecord();

                            if (!$route || $route->stops()->count() < 2) {
                                Notification::make()
                                    ->title(__('Cannot create trip'))
                                    ->body(__('The route must have at least two stops'))
                                    ->danger()
                                    ->send();
                                return;
                            }

                            \DB::beginTransaction();

                            $schedule = $route->schedules()->create([
                                'vehicle_id'     => $data['vehicle_id'],
                                'departure_time' => $data['departure_time'],
                                'days_of_week'   => $data['days_of_week'],
                                'is_active'      => $data['is_active'] ?? true,
                            ]);

                            if (!$schedule || !$schedule->id) {
                                throw new \Exception(__('Failed to create trip'));
                            }

                            $stops = $route->stops()->orderBy('order')->get();

                            if ($stops->isEmpty()) {
                                throw new \Exception(__('No stops found for this route'));
                            }

                            $pricePerKm = $data['price_per_km'] ?? 2.00;

                            foreach ($stops as $index => $stop) {
                                $stopDuration = ($stop->cumulative_time_minutes ?? 0) + ($index * 15);

                                $departureDateTime   = \Carbon\Carbon::parse($data['departure_time']);
                                $arrivalTime         = $departureDateTime->copy()->addMinutes($stopDuration);
                                $departureTimeForStop = $index === 0
                                    ? $data['departure_time']
                                    : $arrivalTime->copy()->addMinutes(15)->format('H:i:s');

                                $cumulativeDistance = 0;
                                for ($i = 1; $i <= $index; $i++) {
                                    $cumulativeDistance += $stops[$i]->distance_from_previous_km ?? 0;
                                }
                                $fare = round($cumulativeDistance * $pricePerKm, 2);

                                $schedule->stopSchedules()->create([
                                    'route_stop_id'  => $stop->id,
                                    'arrival_time'   => $arrivalTime->format('H:i:s'),
                                    'departure_time' => $departureTimeForStop,
                                    'fare'           => $fare,
                                    'order'          => $stop->order,
                                ]);
                            }

                            \DB::commit();

                            Notification::make()
                                ->title(__('Trip created successfully'))
                                ->body(__('Schedules have been created for :count stops', ['count' => $stops->count()]))
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \DB::rollBack();

                            Notification::make()
                                ->title(__('An error occurred while creating the trip'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            \Log::error('Schedule creation error: ' . $e->getMessage());
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view_stops')
                        ->label(__('View stops and times'))
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->modalHeading(__('Stop times'))
                        ->modalContent(fn ($record) => view('filament.modals.schedule-stops', [
                            'schedule'      => $record,
                            'stopSchedules' => $record->stopSchedules()->with('stop.city')->orderBy('order')->get()
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('Close'))
                        ->slideOver(),

                    Tables\Actions\EditAction::make()
                        ->modalHeading(__('Edit trip'))
                        ->modalWidth('2xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            unset($data['price_per_km']);
                            return $data;
                        })
                        ->successNotificationTitle(__('Trip updated successfully')),

                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active ? __('Deactivate') : __('Activate'))
                        ->icon(fn ($record)  => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);

                            Notification::make()
                                ->title($record->is_active ? __('Trip activated') : __('Trip deactivated'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading(__('Delete trip'))
                        ->modalDescription(__('All stop schedules associated with this trip will be deleted'))
                        ->successNotificationTitle(__('Trip deleted successfully')),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('Activate selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['is_active' => true]);

                            Notification::make()
                                ->title(__('Selected trips activated'))
                                ->body(__('Activated :count trips', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('Deactivate selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update(['is_active' => false]);

                            Notification::make()
                                ->title(__('Selected trips deactivated'))
                                ->body(__('Deactivated :count trips', ['count' => $records->count()]))
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading(__('Delete selected trips'))
                        ->modalDescription(__('All stop schedules associated with these trips will be deleted')),
                ]),
            ])
            ->defaultSort('departure_time', 'asc')
            ->emptyStateHeading(__('No trips found'))
            ->emptyStateDescription(__('Add a trip to the route using the "Add trip" button above'))
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
