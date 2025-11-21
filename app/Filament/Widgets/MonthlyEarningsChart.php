<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class MonthlyEarningsChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    public ?string $filter = 'year';

    public function getHeading(): string
    {
        return __('Monthly Earnings Trend');
    }

    protected function getFilters(): ?array
    {
        return [
            'year' => __('This Year'),
            'last_year' => __('Last Year'),
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // تحديد السنة
        $year = $activeFilter === 'last_year' ? now()->subYear()->year : now()->year;

        $labels = [];
        $data = [];

        // جلب البيانات لكل شهر
        for ($month = 1; $month <= 12; $month++) {
            $labels[] = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('M');

            $earnings = Booking::where('status', 'completed')
                ->whereNotNull('app_fees')
                ->whereMonth('updated_at', $month)
                ->whereYear('updated_at', $year)
                ->sum('app_fees');

            $data[] = round($earnings, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => __('Earnings (SAR)'),
                    'data' => $data,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
