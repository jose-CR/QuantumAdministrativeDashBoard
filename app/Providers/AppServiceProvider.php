<?php

namespace App\Providers;

use App\Models\Installment;
use App\Models\User;
use App\Observers\InstallmentObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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

        Gate::define('use-translation-manager', function (?User $user) {
        // Your authorization logic
        //return $user !== null && $user->hasRole('admin');
            return true;
        });
    }
}
