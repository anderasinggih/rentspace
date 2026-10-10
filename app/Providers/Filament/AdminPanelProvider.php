<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('RentSpace Admin')
            ->authGuard('web')
            ->homeUrl('/admin')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->darkMode(true)
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Dashboard Operasional')
                    ->url('/admin/dashboard')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedChartBar)
                    ->group('Menu Kasir')
                    ->sort(1),
                \Filament\Navigation\NavigationItem::make('Transaksi Sewa')
                    ->url('/admin/transactions')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedBanknotes)
                    ->group('Menu Kasir')
                    ->sort(2),
                \Filament\Navigation\NavigationItem::make('Manajemen Unit')
                    ->url('/admin/units')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedDevicePhoneMobile)
                    ->group('Menu Kasir')
                    ->sort(3),
                \Filament\Navigation\NavigationItem::make('Pengaturan Sistem')
                    ->url('/admin/settings')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedCog6Tooth)
                    ->group('Menu Kasir')
                    ->sort(4),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
            ]);
    }
}
