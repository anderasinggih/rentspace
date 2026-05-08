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
        // Global Override for Chatbot API Key from Database
        try {
            $dbChatbotKey = \App\Models\Setting::getVal('chatbot_api_key');
            if ($dbChatbotKey) {
                config(['services.gemini.key' => $dbChatbotKey]);
            }
        } catch (\Exception $e) {
            // Silently fail if DB not ready (e.g. during migrations)
        }

        // Fix for shared hosting where public folder is htdocs or root
        if (app()->environment('production')) {
            $this->app->bind('path.public', function () {
                return base_path();
            });
            
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
