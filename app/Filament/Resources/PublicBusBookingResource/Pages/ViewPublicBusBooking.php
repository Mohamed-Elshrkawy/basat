<?php

namespace App\Filament\Resources\PublicBusBookingResource\Pages;

use App\Filament\Resources\PublicBusBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPublicBusBooking extends ViewRecord
{
    protected static string $resource = PublicBusBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Booking Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('booking_number')
                            ->label(__('Booking Number'))
                            ->copyable()
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('qr_code_url')
                            ->label(__('QR Code'))
                            ->html()
                            ->formatStateUsing(fn ($state) => $state ? '<img src="' . $state . '" width="150">' : __('Not Available')),

                        Infolists\Components\TextEntry::make('status')
                            ->label(__('Status'))
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

                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Booking Date'))
                            ->dateTime('d/m/Y H:i A'),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Customer Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label(__('Name')),

                        Infolists\Components\TextEntry::make('user.email')
                            ->label(__('Email'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('user.phone')
                            ->label(__('Phone'))
                            ->copyable(),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Trip Details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('schedule.route.name')
                            ->label(__('Route')),

                        Infolists\Components\TextEntry::make('travel_date')
                            ->label(__('Travel Date'))
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('trip_type')
                            ->label(__('Trip Type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'one_way' => __('One Way'),
                                'round_trip' => __('Round Trip'),
                                default => $state
                            }),

                        Infolists\Components\TextEntry::make('number_of_seats')
                            ->label(__('Number of Seats'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('seat_numbers')
                            ->label(__('Seat Numbers'))
                            ->badge()
                            ->separator(','),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Stops Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('outboundBoardingStop.stop.name')
                            ->label(__('Boarding Stop (Outbound)')),

                        Infolists\Components\TextEntry::make('outboundDroppingStop.stop.name')
                            ->label(__('Dropping Stop (Outbound)')),

                        Infolists\Components\TextEntry::make('returnBoardingStop.stop.name')
                            ->label(__('Boarding Stop (Return)'))
                            ->visible(fn ($record) => $record->trip_type === 'round_trip'),

                        Infolists\Components\TextEntry::make('returnDroppingStop.stop.name')
                            ->label(__('Dropping Stop (Return)'))
                            ->visible(fn ($record) => $record->trip_type === 'round_trip'),
                    ])->columns(2),

                Infolists\Components\Section::make(__('Payment Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('outbound_fare')
                            ->label(__('Outbound Fare'))
                            ->money('SAR'),

                        Infolists\Components\TextEntry::make('return_fare')
                            ->label(__('Return Fare'))
                            ->money('SAR')
                            ->visible(fn ($record) => $record->trip_type === 'round_trip'),

                        Infolists\Components\TextEntry::make('discount')
                            ->label(__('Discount'))
                            ->money('SAR'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label(__('Total Amount'))
                            ->money('SAR')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label(__('Payment Method'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => __(ucfirst($state))),

                        Infolists\Components\TextEntry::make('payment_status')
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

                        Infolists\Components\TextEntry::make('transaction_id')
                            ->label(__('Transaction ID'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('paid_at')
                            ->label(__('Paid At'))
                            ->dateTime('d/m/Y H:i A'),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Passenger Status'))
                    ->schema([
                        Infolists\Components\TextEntry::make('passenger_status')
                            ->label(__('Status'))
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
                            }),

                        Infolists\Components\TextEntry::make('checked_in_at')
                            ->label(__('Checked In At'))
                            ->dateTime('d/m/Y H:i A'),

                        Infolists\Components\TextEntry::make('boarded_at')
                            ->label(__('Boarded At'))
                            ->dateTime('d/m/Y H:i A'),

                        Infolists\Components\TextEntry::make('arrived_at')
                            ->label(__('Arrived At'))
                            ->dateTime('d/m/Y H:i A'),
                    ])->columns(4),

                Infolists\Components\Section::make(__('Additional Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label(__('Notes'))
                            ->default(__('No notes')),

                        Infolists\Components\TextEntry::make('driver_notes')
                            ->label(__('Driver Notes'))
                            ->default(__('No notes')),

                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label(__('Cancellation Reason'))
                            ->visible(fn ($record) => $record->status === 'cancelled'),

                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->label(__('Cancelled At'))
                            ->dateTime('d/m/Y H:i A')
                            ->visible(fn ($record) => $record->status === 'cancelled'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }
}
