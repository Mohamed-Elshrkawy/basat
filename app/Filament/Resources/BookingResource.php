<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 4;

    // Hide this resource from navigation - split into PublicBusBookingResource and PrivateBusBookingResource
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Bookings');
    }

    public static function getModelLabel(): string
    {
        return __('Booking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Bookings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transportation');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('schedule_id')
                    ->relationship('schedule', 'id')
                    ->required(),
                Forms\Components\DatePicker::make('travel_date')
                    ->required(),
                Forms\Components\TextInput::make('trip_type')
                    ->required(),
                Forms\Components\TextInput::make('number_of_seats')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('seat_numbers')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('outbound_boarding_stop_id')
                    ->relationship('outboundBoardingStop', 'id')
                    ->default(null),
                Forms\Components\Select::make('outbound_dropping_stop_id')
                    ->relationship('outboundDroppingStop', 'id')
                    ->default(null),
                Forms\Components\Select::make('return_boarding_stop_id')
                    ->relationship('returnBoardingStop', 'id')
                    ->default(null),
                Forms\Components\Select::make('return_dropping_stop_id')
                    ->relationship('returnDroppingStop', 'id')
                    ->default(null),
                Forms\Components\TextInput::make('outbound_fare')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('return_fare')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('payment_method')
                    ->required(),
                Forms\Components\TextInput::make('payment_status')
                    ->required(),
                Forms\Components\TextInput::make('transaction_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DateTimePicker::make('paid_at'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('passenger_status')
                    ->required(),
                Forms\Components\DateTimePicker::make('checked_in_at'),
                Forms\Components\DateTimePicker::make('boarded_at'),
                Forms\Components\DateTimePicker::make('arrived_at'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('cancellation_reason')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('cancelled_at'),
                Forms\Components\TextInput::make('boarding_stop_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('driver_notes')
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('schedule.route.name')
                    ->label(__('Route'))
                    ->searchable()
                    ->sortable(),

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
                    ->label(__('Seats'))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total Amount'))
                    ->money('SAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Payment Method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __(ucfirst($state)))
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        'completed' => __('Completed'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('passenger_status')
                    ->label(__('Passenger Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => __('Pending'),
                        'checked_in' => __('Checked In'),
                        'boarded' => __('Boarded'),
                        'completed' => __('Completed'),
                        'no_show' => __('No Show'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'gray',
                        'checked_in' => 'info',
                        'boarded' => 'warning',
                        'completed' => 'success',
                        'no_show' => 'danger',
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
                        'completed' => __('Completed'),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Bookings are created via API only
    }
}
