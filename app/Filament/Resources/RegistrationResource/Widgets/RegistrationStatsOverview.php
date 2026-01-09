<?php

namespace App\Filament\Resources\RegistrationResource\Widgets;

use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RegistrationStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Total pendaftar
        $totalCount = Registration::count();

        // Pendaftaran hari ini
        $todayCount = Registration::whereDate('created_at', Carbon::today())->count();
        $yesterdayCount = Registration::whereDate('created_at', Carbon::yesterday())->count();
        $todayDiff = $todayCount - $yesterdayCount;

        // Pendaftaran minggu ini
        $weekCount = Registration::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();

        $lastWeekCount = Registration::whereBetween('created_at', [
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek()
        ])->count();

        $weekDiff = $weekCount - $lastWeekCount;

        return [
            Stat::make('Total Pendaftar', $totalCount)
                ->description('Keseluruhan pendaftaran')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pendaftar Hari Ini', $todayCount)
                ->description(($todayDiff > 0 ? '+' : '') . $todayDiff . ' dari kemarin')
                ->descriptionIcon($todayDiff > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayDiff > 0 ? 'success' : ($todayDiff < 0 ? 'danger' : 'gray'))
                ->chart($this->getDailyChart()),

            Stat::make('Pendaftar Minggu Ini', $weekCount)
                ->description(($weekDiff > 0 ? '+' : '') . $weekDiff . ' dari minggu lalu')
                ->descriptionIcon($weekDiff > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($weekDiff > 0 ? 'success' : ($weekDiff < 0 ? 'danger' : 'gray'))
                ->chart($this->getWeeklyChart()),
        ];
    }

    /**
     * Generate chart data untuk 7 hari terakhir
     */
    protected function getDailyChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->startOfDay();
            $count = Registration::whereDate('created_at', $date)->count();
            $data[] = $count;
        }

        return $data;
    }

    /**
     * Generate chart data untuk 7 minggu terakhir
     */
    protected function getWeeklyChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();

            $count = Registration::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            $data[] = $count;
        }

        return $data;
    }
}
