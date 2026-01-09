<?php

namespace App\Filament\Resources\RoomResource\Widgets;

use App\Models\Room;
use App\Models\RoomResident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RoomStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        // Base query untuk rooms
        $query = Room::query()->whereHas('block.dorm');

        // Role-based filtering
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Bisa lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            if ($dormIds && $dormIds->isNotEmpty()) {
                $query->whereHas('block', function ($q) use ($dormIds) {
                    $q->whereIn('dorm_id', $dormIds);
                });
            } else {
                return [];
            }
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            if ($blockIds && $blockIds->isNotEmpty()) {
                $query->whereIn('block_id', $blockIds);
            } else {
                return [];
            }
        } else {
            return [];
        }

        // Total kamar (hanya yang aktif/tidak soft deleted)
        $totalRooms = (clone $query)->count();

        // Ambil room IDs untuk menghitung penghuni
        $roomIds = (clone $query)->pluck('id');

        // Kamar terisi (memiliki penghuni aktif)
        $occupiedRooms = RoomResident::query()
            ->whereIn('room_id', $roomIds)
            ->whereNull('check_out_date')
            ->distinct('room_id')
            ->count('room_id');

        // Kamar kosong
        $emptyRooms = $totalRooms - $occupiedRooms;

        // Persentase okupansi
        $occupancyPercentage = $totalRooms > 0
            ? round(($occupiedRooms / $totalRooms) * 100, 1)
            : 0;

        return [
            Stat::make('Total Kamar', $totalRooms)
                ->description('Total seluruh kamar')
                ->descriptionIcon('heroicon-o-home')
                ->color('primary'),

            Stat::make('Kamar Terisi', $occupiedRooms)
                ->description("{$occupancyPercentage}% okupansi")
                ->descriptionIcon('heroicon-o-user-group')
                ->color($occupancyPercentage >= 80 ? 'success' : 'warning'),

            Stat::make('Kamar Kosong', $emptyRooms)
                ->description('Siap dihuni')
                ->descriptionIcon('heroicon-o-home-modern')
                ->color('info'),
        ];
    }
}
