<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Rental::observe(\App\Observers\RentalObserver::class);

        // Fix for shared hosting where public folder is htdocs or root
        if (app()->environment('production')) {
            $this->app->bind('path.public', function () {
                return base_path();
            });
            
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
