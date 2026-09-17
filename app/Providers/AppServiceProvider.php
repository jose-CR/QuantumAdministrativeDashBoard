<?php

namespace App\Providers;

use App\Models\Installment;
use App\Models\User;
use App\Observers\InstallmentObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Filament\Actions\Exports\Downloaders\XlsxDownloader;
use App\Filament\Exports\Downloaders\XlsxDownloader as CustomXlsxDownloader;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            XlsxDownloader::class,
            CustomXlsxDownloader::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Installment::observe(
            InstallmentObserver::class
        );

        Gate::define('use-translation-manager', function (?User $user) {
        // Your authorization logic
        //return $user !== null && $user->hasRole('admin');
            return true;
        });
    }
}
