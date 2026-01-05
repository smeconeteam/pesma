<?php

namespace App\Filament\Widgets;

use App\Models\ResidentProfile;
use App\Models\RoomResident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ResidentStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Status Penempatan Penghuni';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $user = Auth::user();

        // Total resident profiles (registered users)
        $totalResidentsQuery = ResidentProfile::query()
            ->where('status', '!=', 'inactive');

        // Residents yang sudah punya kamar
        $withRoomQuery = RoomResident::query()
            ->whereNull('room_residents.check_out_date')
            ->distinct('user_id');

        // Apply role-based filters
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();

            // Filter residents di dorm tertentu
            $withRoomQuery->whereHas('room.block', fn($q) => $q->whereIn('dorm_id', $dormIds));

            // Filter total residents yang check in di dorm tertentu
            $userIdsWithRoom = (clone $withRoomQuery)->pluck('user_id');
            $totalResidentsQuery->whereIn('user_id', $userIdsWithRoom);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();

            $withRoomQuery->whereHas('room', fn($q) => $q->whereIn('block_id', $blockIds));

            $userIdsWithRoom = (clone $withRoomQuery)->pluck('user_id');
            $totalResidentsQuery->whereIn('user_id', $userIdsWithRoom);
        }

        $totalResidents = $totalResidentsQuery->count();
        $residentsWithRoom = $withRoomQuery->count();
        $residentsWithoutRoom = max(0, $totalResidents - $residentsWithRoom);

        return [
            'datasets' => [
                [
                    'label' => 'Penghuni',
                    'data' => [$residentsWithRoom, $residentsWithoutRoom],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)', // green untuk sudah ada kamar
                        'rgb(251, 146, 60)', // orange untuk belum ada kamar
                    ],
                ],
            ],
            'labels' => ['Sudah Ada Kamar', 'Belum Ada Kamar'],
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

        $totalResidentsQuery = ResidentProfile::query()
            ->where('status', '!=', 'inactive');

        $withRoomQuery = RoomResident::query()
            ->whereNull('room_residents.check_out_date')
            ->distinct('user_id');

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $withRoomQuery->whereHas('room.block', fn($q) => $q->whereIn('dorm_id', $dormIds));
            $userIdsWithRoom = (clone $withRoomQuery)->pluck('user_id');
            $totalResidentsQuery->whereIn('user_id', $userIdsWithRoom);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $withRoomQuery->whereHas('room', fn($q) => $q->whereIn('block_id', $blockIds));
            $userIdsWithRoom = (clone $withRoomQuery)->pluck('user_id');
            $totalResidentsQuery->whereIn('user_id', $userIdsWithRoom);
        }

        $totalResidents = $totalResidentsQuery->count();
        $residentsWithRoom = $withRoomQuery->count();

        $percentage = $totalResidents > 0 ? round(($residentsWithRoom / $totalResidents) * 100, 1) : 0;

        return "{$percentage}% penghuni sudah mendapat kamar";
    }
}
