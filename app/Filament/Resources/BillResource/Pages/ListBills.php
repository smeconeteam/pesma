<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\Bill;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    public function getTabs(): array
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->badge(fn() => Bill::query()->withoutTrashed()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->withoutTrashed()),

            'terhapus' => Tab::make('Data Terhapus')
                ->badge(fn() => Bill::query()->onlyTrashed()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->onlyTrashed())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Tagihan')
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BillResource\Widgets\BillStatsOverview::class
        ];
    }

    // Reset selection saat pindah tab
    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}