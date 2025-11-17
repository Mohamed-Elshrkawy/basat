<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBookingsTable extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label(__('Booking #'))
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'public_bus' => __('Public'),
                        'private_bus' => __('Private'),
                        'school_bus' => __('School'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'public_bus' => 'info',
                        'private_bus' => 'success',
                        'school_bus' => 'warning',
                        default => 'gray'
                    })
                    ->size('sm'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->searchable()
                    ->limit(15)
                    ->size('sm'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Amount'))
                    ->money('SAR')
                    ->sortable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => __('Pending'),
                        'confirmed' => __('Confirmed'),
                        'cancelled' => __('Cancelled'),
                        'in_progress' => __('Progress'),
                        'completed' => __('Done'),
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'in_progress' => 'info',
                        'completed' => 'primary',
                        default => 'gray'
                    })
                    ->size('sm'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('d/m H:i')
                    ->sortable()
                    ->size('sm'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    protected function getTableHeading(): string
    {
        return __('Latest Bookings');
    }
}
