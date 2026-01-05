<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OccupancyChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Okupansi';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = Auth::user();
        $roomQuery = Room::query();

        // Apply role-based filters
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $roomQuery->whereHas('block', fn($q) => $q->whereIn('dorm_id', $dormIds));
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $roomQuery->whereIn('block_id', $blockIds);
        }

        $totalRooms = $roomQuery->count();
        $occupiedRooms = $roomQuery->whereHas('activeRoomResidents')->count();
        $emptyRooms = $totalRooms - $occupiedRooms;

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        return [
            'datasets' => [
                [
                    'label' => 'Kamar',
                    'data' => [$occupiedRooms, $emptyRooms],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)', // green untuk terisi
                        'rgb(229, 231, 235)', // gray untuk kosong
                    ],
                ],
            ],
            'labels' => ['Terisi', 'Kosong'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'display' => false,
                ],
                'x' => [
                    'display' => false,
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }

    public function getDescription(): ?string
    {
        $user = Auth::user();
        $roomQuery = Room::query();

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $roomQuery->whereHas('block', fn($q) => $q->whereIn('dorm_id', $dormIds));
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $roomQuery->whereIn('block_id', $blockIds);
        }

        $totalRooms = $roomQuery->count();
        $occupiedRooms = $roomQuery->whereHas('activeRoomResidents')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        return "Tingkat okupansi kamar: {$occupancyRate}%";
    }
}
