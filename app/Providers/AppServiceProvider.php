<?php

namespace App\Providers;

use App\Models\Installment;
use App\Models\User;
use App\Observers\InstallmentObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Filament\Actions\Exports\Downloaders\XlsxDownloader;
use App\Filament\Exports\Downloaders\XlsxDownloader as CustomXlsxDownloader;
use App\Models\Attachment;
use Storage;

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

        Storage::disk('attachments')->buildTemporaryUrlsUsing(
        function (string $path, \DateTimeInterface $expiration) {
            $attachment = Attachment::where('disk', 'attachments')
                ->where('path', $path)
                ->first();

            return $attachment?->url ?? '#';
        }
    );
    }
}
