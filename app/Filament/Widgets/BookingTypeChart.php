<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Bookings by Type';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '250px';

    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'today' => __('Today'),
            'week' => __('Last Week'),
            'month' => __('Last Month'),
            'year' => __('This Year'),
            'all' => __('All Time'),
        ];
    }

    protected function getData(): array
    {
        $query = Booking::query();

        // Apply filter
        match ($this->filter) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->subWeek()),
            'month' => $query->where('created_at', '>=', now()->subMonth()),
            'year' => $query->whereYear('created_at', now()->year),
            default => $query,
        };

        $publicBus = $query->clone()->where('type', 'public_bus')->count();
        $privateBus = $query->clone()->where('type', 'private_bus')->count();
        $schoolBus = $query->clone()->where('type', 'school_bus')->count();

        return [
            'datasets' => [
                [
                    'label' => __('Bookings'),
                    'data' => [$publicBus, $privateBus, $schoolBus],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)', // Blue for public bus
                        'rgb(34, 197, 94)',  // Green for private bus
                        'rgb(251, 146, 60)', // Orange for school bus
                    ],
                ],
            ],
            'labels' => [
                __('Public Bus'),
                __('Private Bus'),
                __('School Bus'),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getHeading(): string
    {
        return __('Bookings by Type');
    }
}
