<?php

namespace App\Filament\Widgets;

use App\Models\RoomResident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResidentCategoryWidget extends ChartWidget
{
    protected static ?string $heading = 'Penghuni per Kategori';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $user = Auth::user();

        $query = RoomResident::query()
            ->whereNull('room_residents.check_out_date')
            ->join('resident_profiles', 'room_residents.user_id', '=', 'resident_profiles.user_id')
            ->join('resident_categories', 'resident_profiles.resident_category_id', '=', 'resident_categories.id');

        // Apply role-based filters
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $query->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
                ->whereIn('blocks.dorm_id', $dormIds);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $query->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->whereIn('rooms.block_id', $blockIds);
        }

        $categories = $query
            ->select('resident_categories.name', DB::raw('count(distinct room_residents.id) as total'))
            ->groupBy('resident_categories.id', 'resident_categories.name')
            ->orderBy('total', 'desc')
            ->get();

        $colors = [
            'rgb(99, 102, 241)',   // indigo
            'rgb(16, 185, 129)',   // emerald
            'rgb(245, 158, 11)',   // amber
            'rgb(139, 92, 246)',   // violet
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Penghuni',
                    'data' => $categories->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $categories->count()),
                ],
            ],
            'labels' => $categories->pluck('name')->toArray(),
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
        return 'Distribusi penghuni berdasarkan kategori';
    }
}
