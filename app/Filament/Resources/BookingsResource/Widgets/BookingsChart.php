<?php

namespace App\Filament\Resources\BookingsResource\Widgets;

use App\Models\Booking;
use App\Models\Bookings;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BookingsChart extends ChartWidget
{
    protected static ?string $heading = 'Bookings Overview';


    protected static string $color = 'info';

    public ?string $filter = 'this_month';
    // public function getColumnSpan(): int|string|array
    // {
    //     return 'full'; // full width lega
    // }
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $query = Bookings::query();

        match ($this->filter) {
            'today' => $query->whereDate('created_at', today()),
            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year),
            'this_year' => $query->whereYear('created_at', now()->year),
            default => null,
        };

        $bookings = $query
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = $bookings->pluck('date')->map(
            fn($date) =>
            \Carbon\Carbon::parse($date)->format('d M')
        )->toArray();

        $confirmed = $bookings->pluck('confirmed')->toArray();
        $cancelled = $bookings->pluck('cancelled')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Confirmed',
                    'data' => $confirmed,
                    'backgroundColor' => '#16a34a', // green
                    'borderColor' => '#16a34a',
                ],
                [
                    'label' => 'Cancelled',
                    'data' => $cancelled,
                    'backgroundColor' => '#d97706', // red
                    'borderColor' => '#d97706',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // line | bar | pie | doughnut
    }
}
