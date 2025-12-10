<?php

namespace App\Providers;

use App\Models\Institution;
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
        $institution = Institution::first();

        if ($institution) {
            config(['app.name' => $institution->dormitory_name]);
        }
    }
}
