<?php

namespace App\Providers\Filament;

use App\Filament\Admin\pages\Apps;
use App\Filament\Admin\pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Http\Middleware\EnsureModuleEnabled;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kenepa\TranslationManager\TranslationManagerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            /*
             * Sin sidebar: la navegación vive en el lanzador de apps. Apagar el
             * "navigation builder" no borra la navegación (getNavigation() sigue
             * armando los grupos), solo deja de dibujar el menú lateral, así que
             * las tarjetas de /admin/apps se siguen alimentando de los recursos.
             */
            ->navigation(false)
            // El inicio del panel es el lanzador de apps (estilo Odoo), no el dashboard.
            ->homeUrl(fn (): string => Apps::getUrl())
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Violet,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            // El directorio real es "pages" en minúscula; en Linux el path tiene que coincidir.
            ->discoverPages(in: app_path('Filament/Admin/pages'), for: 'App\Filament\Admin\pages')
            ->pages([
                Dashboard::class
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                // AccountWidget::class,
                // FilamentInfoWidget::class,
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
                EnsureModuleEnabled::class,
            ])
            // Cada grupo es una "app" del lanzador (ver App\Support\Apps\AppCatalog).
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('models.others.clients'))
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label(__('models.others.cash'))
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label(__('models.others.inventary'))
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label(__('models.others.credits'))
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label(__('models.others.administration'))
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label(__('models.others.settings'))
                    ->collapsible(false),
            ])
            // Botón de aplicaciones en la barra superior.
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): View => view('filament.admin.topbar.app-switcher'),
            )
            ->databaseNotifications()
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(__('models.others.settings')),
                TranslationManagerPlugin::make()
                    ->navigationGroup(__('models.others.settings')),
            ])
        ;
    }
}
