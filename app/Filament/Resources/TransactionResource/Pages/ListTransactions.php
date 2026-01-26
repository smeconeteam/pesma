<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListTransactions extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionResource\Widgets\TransactionStatsWidget::class,
        ];
    }

    /**
     * Customize table query to add running balance
     */
    public function getTableQuery(): ?Builder
    {
        return static::getResource()::getEloquentQuery()
            ->select([
                'transactions.*',
                DB::raw('(
                    SELECT COALESCE(SUM(
                        CASE 
                            WHEN t2.type = "income" THEN t2.amount
                            WHEN t2.type = "expense" THEN -t2.amount
                            ELSE 0
                        END
                    ), 0)
                    FROM transactions t2
                    WHERE t2.deleted_at IS NULL
                    AND (
                        t2.created_at < transactions.created_at
                        OR (
                            t2.created_at = transactions.created_at 
                            AND t2.id <= transactions.id
                        )
                    )
                ) as running_balance')
            ]);
    }
}