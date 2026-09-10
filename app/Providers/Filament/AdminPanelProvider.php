<?php

namespace App\Providers\Filament;

use App\Filament\Resources\BookingsResource\Widgets\BookingsChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rupadana\ApiService\ApiServicePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('assets/images/logo.png'))
            ->brandLogoHeight('60px')
            ->login()
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->colors([
                'primary' => Color::hex('#329d01'),
            ])
            ->font('Poppins')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => '
                    <style>

                        /* =====================================================
                           TABLE CONTAINER
                        ===================================================== */

                        .fi-ta-ctn {
                            position: relative !important;
                            border-radius: 10px !important;
                        }

                        /* =====================================================
                           ONLY TABLE BODY SCROLL
                        ===================================================== */

                        .fi-ta-content {
                            max-height: 70vh !important;

                            overflow-y: auto !important;
                            overflow-x: auto !important;

                            scrollbar-width: thin !important;
                        }

                        /* =====================================================
                           STICKY HEADER
                        ===================================================== */

                        .fi-ta-table thead th {
                            position: sticky !important;
                            top: 0 !important;

                            z-index: 20 !important;

                            background: #ffffff !important;

                            border-bottom: 1px solid #e5e7eb !important;

                            white-space: nowrap !important;
                        }

                        .dark .fi-ta-table thead th {
                            background: #18181b !important;
                        }

                        /* =====================================================
                           FIX OVERLAP ISSUE
                        ===================================================== */

                        .fi-ta-table tbody tr {
                            position: relative !important;
                            z-index: 1 !important;
                        }

                        .fi-ta-table tbody td {
                            background: inherit !important;
                        }

                        /* =====================================================
                           STICKY PAGINATION
                        ===================================================== */

                        .fi-ta-pagination {
                            position: sticky !important;
                            bottom: 0 !important;

                            background: #ffffff !important;

                            z-index: 10 !important;

                            border-top: 1px solid #e5e7eb !important;

                            padding: 10px !important;
                        }

                        .dark .fi-ta-pagination {
                            background: #18181b !important;
                        }

                        /* =====================================================
                           CUSTOM SCROLLBAR
                        ===================================================== */

                        .fi-ta-content::-webkit-scrollbar {
                            width: 8px;
                            height: 8px;
                        }

                        .fi-ta-content::-webkit-scrollbar-thumb {
                            background: #cbd5e1;
                            border-radius: 999px;
                        }

                        .fi-ta-content::-webkit-scrollbar-track {
                            background: transparent;
                        }

                    </style>
                '
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\UpdateCouponByDuration::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\CheckRolePermission::class,
            ]);
    }
}
