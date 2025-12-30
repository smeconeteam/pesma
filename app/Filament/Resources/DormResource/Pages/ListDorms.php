<?php

namespace App\Filament\Resources\DormResource\Pages;

use App\Filament\Resources\DormResource;
use App\Models\Dorm;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDorms extends ListRecords
{
    protected static string $resource = DormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * ✅ Saat pindah tab, hilangkan selection agar bulk action ikut refresh.
     */
    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }

    public function getTabs(): array
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->badge(fn () => Dorm::query()->withoutTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),

            'terhapus' => Tab::make('Data Terhapus')
                ->badge(fn () => Dorm::query()->onlyTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }
}
