<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EarningsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // حساب الأرباح من الحجوزات المكتملة
        $totalEarnings = $this->calculateTotalEarnings();
        $todayEarnings = $this->calculateTodayEarnings();
        $weekEarnings = $this->calculateWeekEarnings();
        $monthEarnings = $this->calculateMonthEarnings();

        // حساب نسبة النمو مقارنة بالشهر الماضي
        $lastMonthEarnings = $this->calculateLastMonthEarnings();
        $growthPercentage = $lastMonthEarnings > 0
            ? (($monthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100
            : 0;

        return [
            Stat::make(__('Today Earnings'), number_format($todayEarnings, 2) . ' ' . __('SAR'))
                ->description(__('Earnings from completed bookings today'))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->chart($this->getTodayEarningsChart()),

            Stat::make(__('Week Earnings'), number_format($weekEarnings, 2) . ' ' . __('SAR'))
                ->description(__('Last 7 days'))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info')
                ->chart($this->getWeekEarningsChart()),

            Stat::make(__('Month Earnings'), number_format($monthEarnings, 2) . ' ' . __('SAR'))
                ->description(
                    ($growthPercentage >= 0 ? '+' : '') .
                    number_format($growthPercentage, 1) . '% ' .
                    __('from last month')
                )
                ->descriptionIcon($growthPercentage >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($growthPercentage >= 0 ? 'success' : 'danger')
                ->chart($this->getMonthEarningsChart()),

            Stat::make(__('Total Platform Earnings'), number_format($totalEarnings, 2) . ' ' . __('SAR'))
                ->description(__('All time platform earnings'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }

    /**
     * حساب إجمالي أرباح المنصة
     */
    private function calculateTotalEarnings(): float
    {
        return (float) Booking::where('status', 'completed')
            ->whereNotNull('app_fees')
            ->sum('app_fees');
    }

    /**
     * حساب أرباح اليوم
     */
    private function calculateTodayEarnings(): float
    {
        return (float) Booking::where('status', 'completed')
            ->whereNotNull('app_fees')
            ->whereDate('updated_at', today())
            ->sum('app_fees');
    }

    /**
     * حساب أرباح الأسبوع
     */
    private function calculateWeekEarnings(): float
    {
        return (float) Booking::where('status', 'completed')
            ->whereNotNull('app_fees')
            ->where('updated_at', '>=', now()->subDays(7))
            ->sum('app_fees');
    }

    /**
     * حساب أرباح الشهر
     */
    private function calculateMonthEarnings(): float
    {
        return (float) Booking::where('status', 'completed')
            ->whereNotNull('app_fees')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('app_fees');
    }

    /**
     * حساب أرباح الشهر الماضي
     */
    private function calculateLastMonthEarnings(): float
    {
        return (float) Booking::where('status', 'completed')
            ->whereNotNull('app_fees')
            ->whereMonth('updated_at', now()->subMonth()->month)
            ->whereYear('updated_at', now()->subMonth()->year)
            ->sum('app_fees');
    }

    /**
     * رسم بياني لأرباح اليوم (آخر 24 ساعة)
     */
    private function getTodayEarningsChart(): array
    {
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $earnings = Booking::where('status', 'completed')
                ->whereNotNull('app_fees')
                ->whereBetween('updated_at', [$hour->startOfHour(), $hour->endOfHour()])
                ->sum('app_fees');
            $data[] = round($earnings, 2);
        }
        return $data;
    }

    /**
     * رسم بياني لأرباح الأسبوع (آخر 7 أيام)
     */
    private function getWeekEarningsChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $earnings = Booking::where('status', 'completed')
                ->whereNotNull('app_fees')
                ->whereDate('updated_at', $date)
                ->sum('app_fees');
            $data[] = round($earnings, 2);
        }
        return $data;
    }

    /**
     * رسم بياني لأرباح الشهر (آخر 30 يوم)
     */
    private function getMonthEarningsChart(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $earnings = Booking::where('status', 'completed')
                ->whereNotNull('app_fees')
                ->whereDate('updated_at', $date)
                ->sum('app_fees');
            $data[] = round($earnings, 2);
        }
        return $data;
    }
}
