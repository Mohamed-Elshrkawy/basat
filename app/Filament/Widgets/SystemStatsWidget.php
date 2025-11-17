<?php

namespace App\Filament\Widgets;

use App\Enums\UserTypeEnum;
use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Route;
use App\Models\City;
use App\Models\School;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // إحصائيات المستخدمين
        $totalUsers = User::where('user_type', UserTypeEnum::Customer->value)->count();

        // إحصائيات السائقين
        $totalDrivers = User::where('user_type', 'driver')->count();
        $availableDrivers = Driver::where('availability_status', 'available')->count();

        // إحصائيات المركبات
        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('is_active', true)->count();

        // إحصائيات المسارات
        $totalRoutes = Route::count();
        $activeRoutes = Route::where('is_active', true)->count();

        return [
            Stat::make(__('Users'), number_format($totalUsers))
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make(__('Drivers'), number_format($totalDrivers))
                ->description($availableDrivers . ' ' . __('available'))
                ->descriptionIcon('heroicon-o-identification')
                ->color('success'),

            Stat::make(__('Vehicles'), number_format($totalVehicles))
                ->description($activeVehicles . ' ' . __('active'))
                ->descriptionIcon('heroicon-o-truck')
                ->color('info'),

            Stat::make(__('Routes'), number_format($totalRoutes))
                ->description($activeRoutes . ' ' . __('active'))
                ->descriptionIcon('heroicon-o-map')
                ->color('warning'),
        ];
    }
}
