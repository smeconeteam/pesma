<?php

namespace App\Filament\Widgets;

use App\Models\RoomResident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GrowthChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pertumbuhan Penghuni';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '36'; // Default 3 tahun (36 bulan)

    protected function getFilters(): ?array
    {
        return [
            '6' => '6 Bulan',
            '12' => '1 Tahun',
            '36' => '3 Tahun',
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $monthsCount = (int) $this->filter;

        // Get data untuk periode yang dipilih
        $months = collect();
        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'year_month' => $date->format('Y-m'),
                'index' => $monthsCount - 1 - $i, // Index untuk menentukan gap
            ]);
        }

        $data = [];
        $labels = [];

        foreach ($months as $month) {
            // Ambil hari terakhir dari bulan tersebut
            $lastDay = now()->parse($month['year_month'] . '-01')->endOfMonth()->format('Y-m-d');
            
            $query = RoomResident::query()
                ->where('check_in_date', '<=', $lastDay)
                ->where(function ($q) use ($lastDay) {
                    $q->whereNull('check_out_date')
                        ->orWhere('check_out_date', '>', $lastDay);
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

            // Logika label dengan gap untuk 3 tahun
            if ($monthsCount == 36) {
                // Tampilkan label setiap 4 bulan (bulan ke-0, 4, 8, 12, dst)
                // Ini akan menampilkan sekitar 9 label untuk 36 bulan
                $labels[] = ($month['index'] % 4 === 0) ? $month['label'] : '';
            } else {
                // Untuk 6 bulan dan 1 tahun, tampilkan semua label
                $labels[] = $month['label'];
            }
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
            'labels' => $labels,
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
            'maintainAspectRatio' => true,
            'responsive' => true,
        ];
    }
}