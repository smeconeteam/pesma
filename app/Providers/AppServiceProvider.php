<?php

namespace App\Providers;

use App\Models\Institution;
use App\Models\RoomResident;
use App\Observers\RoomResidentObserver;
use App\Observers\RoomResidentRevokeAdminObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use App\Filament\Widgets\DormSummaryTableWidget;
use App\Filament\Widgets\LatestRegistrationsWidget;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        View::share('institution', Institution::query()->first());

        RoomResident::observe(RoomResidentObserver::class);
        RoomResident::observe(RoomResidentRevokeAdminObserver::class);

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_START,
            fn() => view('filament.tables.toolbar-heading', [
                'heading' => 'Ringkasan per Asrama',
            ]),
            DormSummaryTableWidget::class,
        );

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_START,
            fn() => view('filament.tables.toolbar-heading', [
                'heading' => 'Pendaftaran Terbaru',
            ]),
            LatestRegistrationsWidget::class,
        );
    }
}
