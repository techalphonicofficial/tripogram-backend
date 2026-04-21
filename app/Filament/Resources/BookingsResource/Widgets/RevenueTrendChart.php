<?php

namespace App\Filament\Resources\BookingsResource\Widgets;

use App\Models\Bookings;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';

    protected static string $color = 'success';

    public ?string $filter = 'this_year';

    protected function getFilters(): ?array
    {
        return [
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year'  => 'This Year',
            'last_year'  => 'Last Year',
        ];
    }

    protected function getData(): array
    {
        $query = Bookings::query()->where('status', 'confirmed');

        match ($this->filter) {
            'this_month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year),
            'this_year'  => $query->whereYear('created_at', now()->year),
            'last_year'  => $query->whereYear('created_at', now()->subYear()->year),
            default      => null,
        };

        if (in_array($this->filter, ['this_month','last_month'])) {
            $revenues = $query->select(
                DB::raw('DAY(created_at) as day'),
                DB::raw('SUM(final_amount) as total_revenue'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(due_amount) as total_due')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

            $labels = $revenues->pluck('day')->map(fn($d) => $d . ' ' . now()->format('M'))->toArray();
        } else {
            $revenues = $query->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(final_amount) as total_revenue'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(due_amount) as total_due')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

            $labels = $revenues->pluck('month')->map(fn($m) => date('M', mktime(0,0,0,$m,1)))->toArray();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data'  => $revenues->pluck('total_revenue')->toArray(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37,99,235,0.3)',
                ],
                [
                    'label' => 'Paid Amount',
                    'data'  => $revenues->pluck('total_paid')->toArray(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22,163,74,0.3)',
                ],
                [
                    'label' => 'Due Amount',
                    'data'  => $revenues->pluck('total_due')->toArray(),
                    'borderColor' => '#f87171',
                    'backgroundColor' => 'rgba(248,113,113,0.3)',
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
