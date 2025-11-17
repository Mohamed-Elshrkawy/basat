<?php

namespace App\Filament\Resources\PrivateBusBookingResource\Pages;

use App\Filament\Resources\PrivateBusBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPrivateBusBooking extends ViewRecord
{
    protected static string $resource = PrivateBusBookingResource::class;

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

                        Infolists\Components\TextEntry::make('trip_status')
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

                Infolists\Components\Section::make(__('Driver & Vehicle Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('driver.name')
                            ->label(__('Driver Name')),

                        Infolists\Components\TextEntry::make('driver.phone')
                            ->label(__('Driver Phone'))
                            ->copyable(),

                        Infolists\Components\TextEntry::make('vehicle.plate_number')
                            ->label(__('Vehicle Plate Number')),

                        Infolists\Components\TextEntry::make('vehicle.type')
                            ->label(__('Vehicle Type'))
                            ->formatStateUsing(fn ($state) => __(ucfirst(str_replace('_', ' ', $state)))),

                        Infolists\Components\TextEntry::make('vehicle.capacity')
                            ->label(__('Vehicle Capacity'))
                            ->suffix(' ' . __('passengers')),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Trip Details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('startCity.name')
                            ->label(__('From City')),

                        Infolists\Components\TextEntry::make('endCity.name')
                            ->label(__('To City')),

                        Infolists\Components\TextEntry::make('travel_date')
                            ->label(__('Travel Date'))
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('return_date')
                            ->label(__('Return Date'))
                            ->date('d/m/Y')
                            ->visible(fn ($record) => $record->trip_type === 'round_trip'),

                        Infolists\Components\TextEntry::make('trip_type')
                            ->label(__('Trip Type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'one_way' => __('One Way'),
                                'round_trip' => __('Round Trip'),
                                default => $state
                            }),

                        Infolists\Components\TextEntry::make('number_of_seats')
                            ->label(__('Number of Passengers'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('distance_km')
                            ->label(__('Distance'))
                            ->suffix(' km'),

                        Infolists\Components\TextEntry::make('total_days')
                            ->label(__('Total Days')),

                        Infolists\Components\TextEntry::make('started_at')
                            ->label(__('Trip Started At'))
                            ->dateTime('d/m/Y H:i A')
                            ->visible(fn ($record) => $record->trip_status === 'started'),

                        Infolists\Components\TextEntry::make('completed_at')
                            ->label(__('Trip Completed At'))
                            ->dateTime('d/m/Y H:i A')
                            ->visible(fn ($record) => $record->trip_status === 'completed'),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Payment Information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('base_fare')
                            ->label(__('Base Fare'))
                            ->money('SAR'),

                        Infolists\Components\TextEntry::make('amenities_cost')
                            ->label(__('Amenities Cost'))
                            ->money('SAR'),

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
                            ->formatStateUsing(fn ($state) => __(ucfirst(str_replace('_', ' ', $state)))),

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
