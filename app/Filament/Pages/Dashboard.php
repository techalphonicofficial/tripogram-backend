<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Resources\BookingsResource\Widgets\BookingsChart;
use App\Filament\Resources\BookingsResource\Widgets\RevenueRatioChart;
use App\Filament\Resources\BookingsResource\Widgets\RevenueTrendChart;
use App\Filament\Resources\RequestCallBacksResource\Widgets\RequestCallBacksChart;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';


public function callOptimizeRoute()
{
    Artisan::call('optimize:clear');
Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    Notification::make()
        ->title('Cache cleared successfully!')
        ->success()
        ->send();
}
    public function getHeaderWidgets(): array
    {
        return [
            BookingsChart::class,
            RevenueTrendChart::class,
            RevenueRatioChart::class,
            RequestCallBacksChart::class,
        ];
    }
}
