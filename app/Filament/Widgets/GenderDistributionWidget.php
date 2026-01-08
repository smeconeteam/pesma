<?php

namespace App\Filament\Widgets;

use App\Models\ResidentProfile;
use App\Models\RoomResident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GenderDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Gender';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $user = Auth::user();

        // Query untuk mendapatkan gender dari active residents
        $query = RoomResident::query()
            ->whereNull('room_residents.check_out_date')
            ->join('resident_profiles', 'room_residents.user_id', '=', 'resident_profiles.user_id');

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

        $genderStats = $query
            ->select('resident_profiles.gender', DB::raw('count(distinct room_residents.id) as total'))
            ->groupBy('resident_profiles.gender')
            ->get();

        $male = $genderStats->where('gender', 'M')->first()->total ?? 0;
        $female = $genderStats->where('gender', 'F')->first()->total ?? 0;

        return [
            'datasets' => [
                [
                    'label' => 'Penghuni',
                    'data' => [$male, $female],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)', // blue untuk laki-laki
                        'rgb(236, 72, 153)', // pink untuk perempuan
                    ],
                ],
            ],
            'labels' => ['Laki-laki', 'Perempuan'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
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
        return 'Distribusi gender penghuni';
    }
}
