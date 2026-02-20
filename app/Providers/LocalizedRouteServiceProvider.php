<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;

class LocalizedRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set locale early from cookie so routes can use __() translations
        $locale = request()->cookie('locale');
        
        if ($locale && in_array($locale, config('app.available_locales', ['id', 'en']))) {
            App::setLocale($locale);
        }
    }
}
