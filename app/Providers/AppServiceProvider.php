<?php

namespace App\Providers;

use App\Models\Institution;
use App\Models\RoomResident;
use App\Observers\RoomResidentObserver;
use App\Observers\RoomResidentRevokeAdminObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Jangan akses DB saat CLI (migrate, db:seed, dll)
        if (app()->runningInConsole()) {
            return;
        }

        // Pastikan tabelnya ada dulu
        // if (Schema::hasTable('institutions')) {
        //     $institution = Institution::first();

        //     if ($institution) {
        //         config(['app.name' => $institution->dormitory_name]);
        //     }
        // }
        View::share('institution', Institution::query()->first());


        RoomResident::observe(RoomResidentObserver::class);
        RoomResident::observe(RoomResidentRevokeAdminObserver::class);
    }
}
