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
            $baseQuery->where(function (Builder $q) use ($dormIds) {
                // Penghuni yang belum punya kamar ATAU punya kamar aktif di cabang ini
                $q->whereDoesntHave('roomResidents')
                  ->orWhereHas('roomResidents', function (Builder $rr) use ($dormIds) {
                      $rr->whereNull('check_out_date')
                         ->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                  });
            });
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds()->toArray();
            $baseQuery->where(function (Builder $q) use ($blockIds) {
                // Penghuni yang belum punya kamar ATAU punya kamar aktif di komplek ini
                $q->whereDoesntHave('roomResidents')
                  ->orWhereHas('roomResidents', function (Builder $rr) use ($blockIds) {
                      $rr->whereNull('check_out_date')
                         ->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                  });
            });
        }

        // Total Penghuni Aktif (akun aktif + belum checkout)
        $totalActive = (clone $baseQuery)
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereDoesntHave('roomResidents')
                  ->orWhereHas('roomResidents', fn(Builder $rr) => $rr->whereNull('check_out_date'));
            })
            ->count();

        // Penghuni dengan Kamar Aktif
        $withRoom = (clone $baseQuery)
            ->where('is_active', true)
            ->whereHas('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date'))
            ->count();

        // ✅ PERBAIKAN: Penghuni Baru 30 hari berdasarkan created_at di users table
        $newInLast30 = (clone $baseQuery)
            ->where('is_active', true)
            ->where('users.created_at', '>=', now()->subDays(30)->startOfDay())
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

            // Hitung penghuni yang:
            // 1. Akun dibuat sebelum atau pada akhir bulan ini
            // 2. Akun aktif
            // 3. Belum checkout atau checkout setelah awal bulan ini
            $count = (clone $baseQuery)
                ->where('is_active', true)
                ->where('users.created_at', '<=', $date->copy()->endOfMonth())
                ->where(function (Builder $q) use ($date) {
                    // Belum pernah punya kamar ATAU
                    $q->whereDoesntHave('roomResidents')
                      // Punya kamar dan belum checkout sampai akhir bulan ini
                      ->orWhereHas('roomResidents', function (Builder $rr) use ($date) {
                          $rr->where(function (Builder $checkout) use ($date) {
                              $checkout->whereNull('check_out_date')
                                       ->orWhere('check_out_date', '>', $date->copy()->endOfMonth());
                          });
                      });
                })
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}