<?php

namespace App\Filament\Resources\BookingsResource\Widgets;

use App\Models\Bookings;
use Filament\Widgets\ChartWidget;

class RevenueRatioChart extends ChartWidget
{
    protected static ?string $heading = 'Paid vs Due Revenue';

    protected static string $color = 'danger';

    public ?string $filter = 'this_month';

    protected function getFilters(): ?array
    {
        return [
            'today'      => 'Today',
            'this_month' => 'This Month',
            'this_year'  => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $query = Bookings::query()->where('status', 'confirmed');

        match ($this->filter) {
            'today'      => $query->whereDate('created_at', today()),
            'this_month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'this_year'  => $query->whereYear('created_at', now()->year),
            default      => null,
        };

        $totalPaid = $query->sum('paid_amount');
        $totalDue  = $query->sum('due_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue Split',
                    'data'  => [$totalPaid, $totalDue],
                    'backgroundColor' => ['#16a34a', '#f87171'],
                ],
            ],
            'labels' => ['Paid', 'Due'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
