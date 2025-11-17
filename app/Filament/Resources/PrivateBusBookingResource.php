<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivateBusBookingResource\Pages;
use App\Filament\Resources\PrivateBusBookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrivateBusBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('Private Bus Bookings');
    }

    public static function getModelLabel(): string
    {
        return __('Private Bus Booking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Private Bus Bookings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transportation');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('type', 'private_bus')
            ->where('status', 'pending')
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'private_bus');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Booking Information'))
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label(__('Booking Number'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('user_id')
                            ->label(__('Customer'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('driver_id')
                            ->label(__('Driver'))
                            ->relationship('driver', 'name')
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('vehicle_id')
                            ->label(__('Vehicle'))
                            ->relationship('vehicle', 'plate_number')
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('Trip Details'))
                    ->schema([
                        Forms\Components\Select::make('start_city_id')
                            ->label(__('From City'))
                            ->relationship('startCity', 'name')
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('end_city_id')
                            ->label(__('To City'))
                            ->relationship('endCity', 'name')
                            ->searchable()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('travel_date')
                            ->label(__('Travel Date'))
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('return_date')
                            ->label(__('Return Date'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($get) => $get('trip_type') === 'round_trip'),

                        Forms\Components\Select::make('trip_type')
                            ->label(__('Trip Type'))
                            ->options([
                                'one_way' => __('One Way'),
                                'round_trip' => __('Round Trip'),
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('number_of_seats')
                            ->label(__('Number of Passengers'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('distance_km')
                            ->label(__('Distance (KM)'))
                            ->numeric()
                            ->suffix('km')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('total_days')
                            ->label(__('Total Days'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('Payment Information'))
                    ->schema([
                        Forms\Components\TextInput::make('base_fare')
                            ->label(__('Base Fare'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('amenities_cost')
                            ->label(__('Amenities Cost'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('discount')
                            ->label(__('Discount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('total_amount')
                            ->label(__('Total Amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method'))
                            ->options([
                                'wallet' => __('Wallet'),
                                'card' => __('Card'),
                                'cash' => __('Cash'),
                                'bank_transfer' => __('Bank Transfer'),
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('payment_status')
                            ->label(__('Payment Status'))
                            ->options([
                                'pending' => __('Pending'),
                                'paid' => __('Paid'),
                                'failed' => __('Failed'),
                                'refunded' => __('Refunded'),
                            ])
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('Booking Status'))
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('Booking Status'))
                            ->options([
                                'pending' => __('Pending'),
                                'confirmed' => __('Confirmed'),
                                'cancelled' => __('Cancelled'),
                                'in_progress' => __('In Progress'),
                                'completed' => __('Completed'),
                                'refunded' => __('Refunded'),
                            ])
                            ->required(),

                        Forms\Components\Select::make('trip_status')
                            ->label(__('Trip Status'))
                            ->options([
                                'pending' => __('Pending'),
                                'started' => __('Started'),
                                'completed' => __('Completed'),
                                'cancelled' => __('Cancelled'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('driver_notes')
                            ->label(__('Driver Notes'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label(__('Cancellation Reason'))
                            ->rows(3)
                            ->visible(fn ($get) => $get('status') === 'cancelled')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('booking_number')
                    ->label(__('Booking Number'))
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label(__('Phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('driver.name')
                    ->label(__('Driver'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label(__('Vehicle'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('startCity.name')
                    ->label(__('From'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('endCity.name')
                    ->label(__('To'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('travel_date')
                    ->label(__('Travel Date'))
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('trip_type')
                    ->label(__('Trip Type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'one_way' => __('One Way'),
                        'round_trip' => __('Round Trip'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'one_way' => 'info',
                        'round_trip' => 'success',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('number_of_seats')
                    ->label(__('Passengers'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('distance_km')
                    ->label(__('Distance'))
                    ->suffix(' km')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total Amount'))
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Payment Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => __('Pending'),
                        'paid' => __('Paid'),
                        'failed' => __('Failed'),
                        'refunded' => __('Refunded'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Booking Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => __('Pending'),
                        'confirmed' => __('Confirmed'),
                        'cancelled' => __('Cancelled'),
                        'in_progress' => __('In Progress'),
                        'completed' => __('Completed'),
                        'refunded' => __('Refunded'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'in_progress' => 'info',
                        'completed' => 'primary',
                        'refunded' => 'gray',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('trip_status')
                    ->label(__('Trip Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => __('Pending'),
                        'started' => __('Started'),
                        'completed' => __('Completed'),
                        'cancelled' => __('Cancelled'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'gray',
                        'started' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray'
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Booking Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'confirmed' => __('Confirmed'),
                        'cancelled' => __('Cancelled'),
                        'in_progress' => __('In Progress'),
                        'completed' => __('Completed'),
                        'refunded' => __('Refunded'),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('Payment Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'paid' => __('Paid'),
                        'failed' => __('Failed'),
                        'refunded' => __('Refunded'),
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('trip_type')
                    ->label(__('Trip Type'))
                    ->options([
                        'one_way' => __('One Way'),
                        'round_trip' => __('Round Trip'),
                    ]),

                Tables\Filters\SelectFilter::make('trip_status')
                    ->label(__('Trip Status'))
                    ->options([
                        'pending' => __('Pending'),
                        'started' => __('Started'),
                        'completed' => __('Completed'),
                        'cancelled' => __('Cancelled'),
                    ]),

                Tables\Filters\Filter::make('travel_date')
                    ->form([
                        Forms\Components\DatePicker::make('travel_from')
                            ->label(__('Travel From')),
                        Forms\Components\DatePicker::make('travel_until')
                            ->label(__('Travel Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['travel_from'], fn ($q, $date) => $q->whereDate('travel_date', '>=', $date))
                            ->when($data['travel_until'], fn ($q, $date) => $q->whereDate('travel_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('cancel')
                    ->label(__('Cancel'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Booking $record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label(__('Cancellation Reason'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Booking $record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'trip_status' => 'cancelled',
                            'cancellation_reason' => $data['cancellation_reason'],
                            'cancelled_at' => now(),
                        ]);

                        // Refund if paid
                        if ($record->isPaid()) {
                            $record->user->deposit(
                                (float)$record->total_amount,
                                [
                                    'ar' => 'استرجاع مبلغ الحجز رقم ' . $record->booking_number,
                                    'en' => 'Refund for booking ' . $record->booking_number
                                ]
                            );
                            $record->update(['payment_status' => 'refunded']);
                        }

                        Notification::make()
                            ->title(__('Booking Cancelled'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListPrivateBusBookings::route('/'),
            'create' => Pages\CreatePrivateBusBooking::route('/create'),
            'view' => Pages\ViewPrivateBusBooking::route('/{record}'),
            'edit' => Pages\EditPrivateBusBooking::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Bookings are created via API only
    }
}
