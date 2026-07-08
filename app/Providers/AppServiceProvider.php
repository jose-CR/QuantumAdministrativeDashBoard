<?php

namespace App\Providers;

use App\Models\Installment;
use App\Observers\InstallmentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Installment::observe(
            InstallmentObserver::class
        );
    }
}
