<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use Illuminate\Support\Facades\DB;

class TransactionStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListTransactions::class;
    }

    protected function getStats(): array
    {
        // Get filtered table query
        $query = $this->getPageTableQuery();

        // Calculate totals based on filtered data
        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        return [
            Stat::make('Total Pemasukan', 'Rp ' . number_format($income, 0, ',', '.'))
                ->description('Total pemasukan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getChartData('income')),

            Stat::make('Total Pengeluaran', 'Rp ' . number_format($expense, 0, ',', '.'))
                ->description('Total pengeluaran')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart($this->getChartData('expense')),

            Stat::make('Total Saldo', 'Rp ' . number_format($balance, 0, ',', '.'))
                ->description($balance >= 0 ? 'Surplus' : 'Defisit')
                ->descriptionIcon($balance >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($balance >= 0 ? 'success' : 'warning'),
        ];
    }

    /**
     * Get chart data for the stat card
     */
    protected function getChartData(string $type): array
    {
        // Get last 7 days data for mini chart
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        
        $data = Transaction::query()
            ->where('type', $type)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Create array with all 7 days (fill missing with 0)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = $data[$date] ?? 0;
        }

        return $chartData;
    }
}