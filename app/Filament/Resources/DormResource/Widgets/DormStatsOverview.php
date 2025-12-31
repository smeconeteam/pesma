<?php

namespace App\Filament\Resources\DormResource\Widgets;

use App\Models\Dorm;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DormStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        // Base query dengan role filtering
        $query = Dorm::query();

        // Role filtering - hanya super_admin dan main_admin yang bisa lihat semua
        if (!$user || !$user->hasRole(['super_admin', 'main_admin'])) {
            // Role lain tidak bisa akses resource ini, return empty
            return [];
        }

        // Hitung total cabang (tidak termasuk yang soft deleted)
        $totalDorms = $query->count();

        // Hitung cabang aktif
        $activeDorms = (clone $query)->where('is_active', true)->count();

        // Hitung persentase cabang aktif
        $activePercentage = $totalDorms > 0
            ? round(($activeDorms / $totalDorms) * 100, 1)
            : 0;

        return [
            Stat::make('Total Cabang', $totalDorms)
                ->description('Total seluruh cabang asrama')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Cabang Aktif', $activeDorms)
                ->description("{$activePercentage}% dari total cabang")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Cabang Nonaktif', $totalDorms - $activeDorms)
                ->description('Cabang yang tidak beroperasi')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
