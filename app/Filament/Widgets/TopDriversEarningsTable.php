<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopDriversEarningsTable extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('user_type', 'driver')
                    ->whereHas('driverBookings', function ($query) {
                        $query->where('status', 'completed');
                    })
                    ->withSum(['driverBookings as total_revenue' => function ($query) {
                        $query->where('status', 'completed');
                    }], 'total_amount')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Driver Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->size('sm'),

                Tables\Columns\TextColumn::make('driverBookings')
                    ->label(__('Completed Trips'))
                    ->badge()
                    ->color('success')
                    ->size('sm')
                    ->counts([
                        'driverBookings' => fn (Builder $query) => $query->where('status', 'completed')
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label(__('Total Revenue'))
                    ->money('SAR')
                    ->size('sm')
                    ->sortable(),

                Tables\Columns\TextColumn::make('platform_earnings')
                    ->label(__('Platform Earnings'))
                    ->money('SAR')
                    ->color('primary')
                    ->size('sm')
                    ->getStateUsing(function ($record) {
                        return $this->calculatePlatformEarnings($record);
                    }),

                Tables\Columns\TextColumn::make('driver_earnings')
                    ->label(__('Driver Net Earnings'))
                    ->money('SAR')
                    ->color('success')
                    ->size('sm')
                    ->getStateUsing(function ($record) {
                        return $this->calculateDriverEarnings($record);
                    }),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getTableHeading(): string
    {
        return __('Top Drivers by Earnings');
    }

    /**
     * حساب أرباح المنصة من سائق معين
     */
    protected function calculatePlatformEarnings(User $driver): float
    {
        return (float) $driver->driverBookings()
            ->where('status', 'completed')
            ->whereNotNull('app_fees')
            ->sum('app_fees');
    }

    /**
     * حساب صافي أرباح السائق
     */
    protected function calculateDriverEarnings(User $driver): float
    {
        return (float) $driver->driverBookings()
            ->where('status', 'completed')
            ->whereNotNull('driver_earnings')
            ->sum('driver_earnings');
    }
}
