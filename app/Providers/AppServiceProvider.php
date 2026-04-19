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
        // Fix for shared hosting where public folder is htdocs or root
        if (app()->environment('production')) {
            $this->app->bind('path.public', function () {
                return base_path();
            });
        }
    }
}
