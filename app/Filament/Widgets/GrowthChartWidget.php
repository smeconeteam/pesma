<?php

namespace App\Filament\Widgets;

use App\Models\RoomResident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pertumbuhan Penghuni (6 Bulan Terakhir)';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = Auth::user();

        // Get data untuk 6 bulan terakhir
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
            ]);
        }

        $data = [];

        foreach ($months as $month) {
            $query = RoomResident::query()
                ->where('check_in_date', '<=', $month['month'] . '-31')
                ->where(function ($q) use ($month) {
                    $q->whereNull('check_out_date')
                        ->orWhere('check_out_date', '>', $month['month'] . '-31');
                });

            // Apply role-based filters
            if ($user->hasRole(['super_admin', 'main_admin'])) {
                // Lihat semua
            } elseif ($user->hasRole('branch_admin')) {
                $dormIds = $user->branchDormIds();
                $query->whereHas('room.block', fn($q) => $q->whereIn('dorm_id', $dormIds));
            } elseif ($user->hasRole('block_admin')) {
                $blockIds = $user->blockIds();
                $query->whereHas('room', fn($q) => $q->whereIn('block_id', $blockIds));
            }

            $data[] = $query->distinct('user_id')->count('user_id');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penghuni',
                    'data' => $data,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
