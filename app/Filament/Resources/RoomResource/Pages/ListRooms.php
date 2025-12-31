<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\Room;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RoomResource\Widgets\RoomStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed())
                ->badge(fn () => Room::query()
                    ->whereHas('block.dorm')
                    ->withoutTrashed()
                    ->count()
                ),

            'terhapus' => Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => Room::query()
                    ->whereHas('block.dorm')
                    ->onlyTrashed()
                    ->count()
                )
                ->badgeColor('danger'),
        ];
    }

    /**
     * ✅ reset selection saat pindah tab
     */
    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}
