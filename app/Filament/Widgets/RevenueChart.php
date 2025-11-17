<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'week' => __('Last 7 Days'),
            'month' => __('Last 30 Days'),
            'year' => __('Last 12 Months'),
        ];
    }

    protected function getData(): array
    {
        $data = match ($this->filter) {
            'week' => $this->getWeeklyData(),
            'year' => $this->getYearlyData(),
            default => $this->getMonthlyData(),
        };

        return [
            'datasets' => [
                [
                    'label' => __('Revenue (SAR)'),
                    'data' => $data['values'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getWeeklyData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('D');

            $revenue = Booking::whereDate('created_at', $date)
                ->whereIn('payment_status', ['paid', 'completed'])
                ->sum('total_amount');

            $values[] = (float) $revenue;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function getMonthlyData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $revenue = Booking::whereDate('created_at', $date)
                ->whereIn('payment_status', ['paid', 'completed'])
                ->sum('total_amount');

            $values[] = (float) $revenue;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function getYearlyData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $revenue = Booking::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereIn('payment_status', ['paid', 'completed'])
                ->sum('total_amount');

            $values[] = (float) $revenue;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string
    {
        return __('Revenue Trend');
    }
}
