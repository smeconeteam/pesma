<?php

namespace App\Filament\Resources\ResidentResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ResidentStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        // Base query dengan scope sesuai role
        $baseQuery = User::query()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->with('residentProfile');

        // Apply role-based filtering
        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds()->toArray();
            $baseQuery->whereHas('roomResidents', function (Builder $q) use ($dormIds) {
                $q->whereNull('check_out_date')
                    ->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
            });
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds()->toArray();
            $baseQuery->whereHas('roomResidents', function (Builder $q) use ($blockIds) {
                $q->whereNull('check_out_date')
                    ->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
            });
        }

        // Total Penghuni Aktif
        $totalActive = (clone $baseQuery)
            ->where('is_active', true)
            ->count();

        // Penghuni dengan Kamar
        $withRoom = (clone $baseQuery)
            ->whereHas('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date'))
            ->count();

        // Penghuni Baru 30 hari
        $newInLast30 = (clone $baseQuery)
            ->where('is_active', true)
            ->whereHas('residentProfile', fn(Builder $q) => $q->where('check_in_date', '>=', now()->subDays(30)->startOfDay()))
            ->count();

        return [
            Stat::make('Total Penghuni Aktif', $totalActive)
                ->description('Penghuni dengan status aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart($this->getMonthlyTrend($baseQuery)),

            Stat::make('Penghuni Berkamar', $withRoom)
                ->description(($totalActive - $withRoom) . ' belum ditempatkan')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),

            Stat::make('Penghuni Baru', $newInLast30)
                ->description('Dalam 30 hari terakhir')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

    protected function getMonthlyTrend(Builder $baseQuery): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $count = (clone $baseQuery)
                ->where('is_active', true)
                ->whereHas('residentProfile', function (Builder $q) use ($date) {
                    $q->where('check_in_date', '<=', $date->endOfMonth());
                })
                ->whereDoesntHave('residentProfile', function (Builder $q) use ($date) {
                    $q->whereNotNull('check_out_date')
                        ->where('check_out_date', '<', $date->startOfMonth());
                })
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}
