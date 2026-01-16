<?php

namespace App\Filament\Resources\BillResource\Widgets;

use App\Models\Bill;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class BillStatsOverview extends BaseWidget
{
    
    protected function getStats(): array
    {
        $user = auth()->user();

        // Query dasar untuk filter berdasarkan role
        $query = Bill::query();

        // Filter berdasarkan role
        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $query->whereHas('room.block.dorm', function ($q) use ($dormIds) {
                $q->whereIn('dorms.id', $dormIds);
            });
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $query->whereHas('room.block', function ($q) use ($blockIds) {
                $q->whereIn('blocks.id', $blockIds);
            });
        }

        // Total Tagihan Bulan Ini
        $totalThisMonth = (clone $query)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        // Total Belum Lunas
        $totalUnpaid = (clone $query)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->sum('remaining_amount');

        // Total Sudah Lunas Bulan Ini
        $totalPaidThisMonth = (clone $query)
            ->where('status', 'paid')
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->sum('total_amount');

        // Hitung trend bulan lalu vs bulan ini untuk tagihan yang dibuat
        $lastMonthTotal = (clone $query)
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('total_amount');

        $trendThisMonth = $lastMonthTotal > 0
            ? (($totalThisMonth - $lastMonthTotal) / $lastMonthTotal) * 100
            : 0;

        // Hitung trend pembayaran bulan lalu vs bulan ini
        $lastMonthPaid = (clone $query)
            ->where('status', 'paid')
            ->whereYear('updated_at', now()->subMonth()->year)
            ->whereMonth('updated_at', now()->subMonth()->month)
            ->sum('total_amount');

        $trendPaid = $lastMonthPaid > 0
            ? (($totalPaidThisMonth - $lastMonthPaid) / $lastMonthPaid) * 100
            : 0;

        return [
            Stat::make('Total Tagihan Bulan Ini', 'Rp ' . number_format($totalThisMonth, 0, ',', '.'))
                ->description($trendThisMonth > 0
                    ? number_format(abs($trendThisMonth), 1) . '% lebih tinggi dari bulan lalu'
                    : ($trendThisMonth < 0
                        ? number_format(abs($trendThisMonth), 1) . '% lebih rendah dari bulan lalu'
                        : 'Sama dengan bulan lalu'))
                ->color($trendThisMonth > 0 ? 'success' : ($trendThisMonth < 0 ? 'danger' : 'gray'))
                ->chart($this->getMonthlyChart($query, 'created_at')),

            Stat::make('Total Belum Lunas', 'Rp ' . number_format($totalUnpaid, 0, ',', '.'))
                ->description($this->getUnpaidCount($query) . ' tagihan menunggu pembayaran')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart($this->getUnpaidChart($query)),

            Stat::make('Total Lunas Bulan Ini', 'Rp ' . number_format($totalPaidThisMonth, 0, ',', '.'))
                ->description($trendPaid > 0
                    ? number_format(abs($trendPaid), 1) . '% lebih tinggi dari bulan lalu'
                    : ($trendPaid < 0
                        ? number_format(abs($trendPaid), 1) . '% lebih rendah dari bulan lalu'
                        : 'Sama dengan bulan lalu'))
                ->color($trendPaid > 0 ? 'success' : ($trendPaid < 0 ? 'danger' : 'gray'))
                ->chart($this->getMonthlyChart($query, 'updated_at', 'paid')),
        ];
    }

    /**
     * Ambil data chart untuk 7 bulan terakhir
     */
    private function getMonthlyChart($query, $dateColumn = 'created_at', $status = null): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $monthQuery = (clone $query)
                ->whereYear($dateColumn, $date->year)
                ->whereMonth($dateColumn, $date->month);

            if ($status) {
                $monthQuery->where('status', $status);
            }

            $data[] = $monthQuery->sum('total_amount') / 1000000;
        }

        return $data;
    }

    /**
     * Ambil jumlah tagihan belum lunas
     */
    private function getUnpaidCount($query): int
    {
        return (clone $query)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->count();
    }

    /**
     * Ambil data chart untuk tagihan belum lunas (7 bulan terakhir)
     */
    private function getUnpaidChart($query): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $amount = (clone $query)
                ->whereIn('status', ['issued', 'partial', 'overdue'])
                ->whereYear('created_at', '<=', $date->year)
                ->whereMonth('created_at', '<=', $date->month)
                ->sum('remaining_amount');

            $data[] = $amount / 1000000;
        }

        return $data;
    }
}
