<?php

namespace App\Filament\Resources\BlockResource\Widgets;

use App\Models\Block;
use App\Models\RoomResident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BlockStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        // Base query untuk blocks
        $query = Block::query()->whereHas('dorm');

        // Role-based filtering
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Bisa lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            if ($dormIds && $dormIds->isNotEmpty()) {
                $query->whereIn('dorm_id', $dormIds);
            } else {
                return [];
            }
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            if ($blockIds && $blockIds->isNotEmpty()) {
                $query->whereIn('id', $blockIds);
            } else {
                return [];
            }
        } else {
            return [];
        }

        // Total komplek
        $totalBlocks = (clone $query)->count();

        // Komplek aktif
        $activeBlocks = (clone $query)->where('is_active', true)->count();

        // Hitung kapasitas dan okupansi
        $capacityData = $this->calculateCapacityStats($query);

        $occupancyPercentage = $capacityData['total_capacity'] > 0
            ? round(($capacityData['occupied'] / $capacityData['total_capacity']) * 100, 1)
            : 0;

        return [
            Stat::make('Total Komplek', $totalBlocks)
                ->description('Total seluruh komplek')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary'),

            Stat::make('Komplek Aktif', $activeBlocks)
                ->description(sprintf(
                    '%.1f%% dari total komplek',
                    $totalBlocks > 0 ? ($activeBlocks / $totalBlocks) * 100 : 0
                ))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Kapasitas Terisi', "{$occupancyPercentage}%")
                ->description("{$capacityData['occupied']} dari {$capacityData['total_capacity']} kapasitas")
                ->descriptionIcon('heroicon-o-users')
                ->color($occupancyPercentage >= 80 ? 'warning' : 'info'),
        ];
    }

    /**
     * Hitung total kapasitas dan penghuni di komplek-komplek yang sudah difilter
     */
    protected function calculateCapacityStats($blockQuery): array
    {
        // Ambil block IDs dari query yang sudah difilter
        $blockIds = (clone $blockQuery)->pluck('id');

        if ($blockIds->isEmpty()) {
            return [
                'total_capacity' => 0,
                'occupied' => 0,
            ];
        }

        // Hitung total kapasitas dari semua kamar di blocks tersebut
        $totalCapacity = DB::table('rooms')
            ->whereIn('block_id', $blockIds)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->sum(DB::raw('COALESCE(capacity, (SELECT default_capacity FROM room_types WHERE room_types.id = rooms.room_type_id))'));

        // Hitung jumlah penghuni aktif di kamar-kamar tersebut
        $occupied = DB::table('room_residents')
            ->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
            ->whereIn('rooms.block_id', $blockIds)
            ->whereNull('rooms.deleted_at')
            ->whereNull('room_residents.check_out_date')
            ->count();

        return [
            'total_capacity' => (int) $totalCapacity,
            'occupied' => (int) $occupied,
        ];
    }
}
