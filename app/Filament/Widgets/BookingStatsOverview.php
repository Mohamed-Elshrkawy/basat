<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BookingStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // إجمالي الحجوزات
        $totalBookings = Booking::count();

        // الحجوزات المؤكدة
        $confirmedBookings = Booking::where('status', 'confirmed')->count();

        // الحجوزات المعلقة (في انتظار الدفع)
        $pendingBookings = Booking::where('status', 'pending')->count();

        // إجمالي الإيرادات من الحجوزات المدفوعة
        $totalRevenue = Booking::whereIn('payment_status', ['paid', 'completed'])
            ->sum('total_amount');

        // الإيرادات هذا الشهر
        $monthlyRevenue = Booking::whereIn('payment_status', ['paid', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // معدل نمو الحجوزات (مقارنة بالشهر الماضي)
        $lastMonthBookings = Booking::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $thisMonthBookings = Booking::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $growthRate = $lastMonthBookings > 0
            ? (($thisMonthBookings - $lastMonthBookings) / $lastMonthBookings) * 100
            : 0;

        return [
            Stat::make(__('Total Bookings'), number_format($totalBookings))
                ->description(__('All time'))
                ->descriptionIcon('heroicon-o-ticket')
                ->color('primary')
                ->chart($this->getBookingTrend()),

            Stat::make(__('Confirmed'), number_format($confirmedBookings))
                ->description(__('Active'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('Pending'), number_format($pendingBookings))
                ->description(__('Awaiting payment'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(__('Total Revenue'), number_format($totalRevenue, 0) . ' ' . __('SAR'))
                ->description(__('All time'))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make(__('Month Revenue'), number_format($monthlyRevenue, 0) . ' ' . __('SAR'))
                ->description(now()->format('M Y'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make(__('Growth'), number_format($growthRate, 1) . '%')
                ->description($growthRate >= 0 ? __('vs last month') : __('vs last month'))
                ->descriptionIcon($growthRate >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($growthRate >= 0 ? 'success' : 'danger'),
        ];
    }

    /**
     * Get booking trend for the last 7 days
     */
    protected function getBookingTrend(): array
    {
        $bookings = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        // Fill missing days with 0
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[] = $bookings[array_search($date, array_keys($bookings))] ?? 0;
        }

        return $trend;
    }
}
