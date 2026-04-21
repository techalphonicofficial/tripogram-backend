<?php

namespace App\Filament\Resources\RequestCallBacksResource\Widgets;

use App\Models\RequestCallBack;
use App\Models\RequestCallBacks;
use Filament\Widgets\ChartWidget;

class RequestCallBacksChart extends ChartWidget
{
    protected static ?string $heading = 'Request Call Backs';

    public ?string $filter = 'this_month';

    protected function getFilters(): ?array
    {
        return [
            'today'       => 'Today',
            'this_week'   => 'This Week',
            'this_month'  => 'This Month',
            'last_month'  => 'Last Month',
            'this_year'   => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $query = RequestCallBacks::query();

        // Apply filter using match
        match ($this->filter) {
            'today' => $query->whereDate('created_at', today()),
            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year),
            'this_year' => $query->whereYear('created_at', now()->year),
            default => null,
        };

        // Count grouped by day
        $data = $query->get()
            ->groupBy(fn ($item) => $item->created_at->format('d M'))
            ->map->count();

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => array_values($data->toArray()),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => array_keys($data->toArray()),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
