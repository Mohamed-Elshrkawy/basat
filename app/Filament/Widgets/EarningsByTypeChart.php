<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class EarningsByTypeChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    public ?string $filter = 'month';

    public function getHeading(): string
    {
        return __('Earnings by Bus Type');
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => __('Today'),
            'week' => __('Last Week'),
            'month' => __('Last Month'),
            'year' => __('This Year'),
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        // تحديد الفترة الزمنية
        $query = Booking::where('status', 'completed');

        match($activeFilter) {
            'today' => $query->whereDate('updated_at', today()),
            'week' => $query->where('updated_at', '>=', now()->subDays(7)),
            'month' => $query->where('updated_at', '>=', now()->subDays(30)),
            'year' => $query->whereYear('updated_at', now()->year),
            default => null,
        };

        // حساب الأرباح لكل نوع من قاعدة البيانات
        $publicBusEarnings = (clone $query)
            ->where('type', 'public_bus')
            ->whereNotNull('app_fees')
            ->sum('app_fees');

        $privateBusEarnings = (clone $query)
            ->where('type', 'private_bus')
            ->whereNotNull('app_fees')
            ->sum('app_fees');

        return [
            'datasets' => [
                [
                    'label' => __('Earnings'),
                    'data' => [
                        round($publicBusEarnings, 2),
                        round($privateBusEarnings, 2),
                    ],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)', // أزرق للباص العام
                        'rgb(16, 185, 129)', // أخضر للباص الخاص
                    ],
                ],
            ],
            'labels' => [
                __('Public Bus'),
                __('Private Bus'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
