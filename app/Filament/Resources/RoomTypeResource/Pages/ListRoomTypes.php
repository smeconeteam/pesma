<?php

namespace App\Filament\Resources\RoomTypeResource\Pages;

use App\Filament\Resources\RoomTypeResource;
use App\Models\RoomType;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRoomTypes extends ListRecords
{
    protected static string $resource = RoomTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // Tab hanya untuk super_admin (sesuai logika yang sudah ada)
        if (! auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->badge(fn () => RoomType::query()->withoutTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),

            'terhapus' => Tab::make('Data Terhapus')
                ->badge(fn () => RoomType::query()->onlyTrashed()->count())
                ->badgeColor('danger') // ✅ count merah di tab data terhapus
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    /**
     * ✅ Jika pindah tab, reset checkbox/selection yang sebelumnya dipilih.
     */
    public function updatedActiveTab($tab = null): void
    {
        if (method_exists($this, 'deselectAllTableRecords')) {
            $this->deselectAllTableRecords();
        }

        if (method_exists($this, 'resetTableSelection')) {
            $this->resetTableSelection();
        }

        if (property_exists($this, 'selectedTableRecords')) {
            $this->selectedTableRecords = [];
        }
    }
}
