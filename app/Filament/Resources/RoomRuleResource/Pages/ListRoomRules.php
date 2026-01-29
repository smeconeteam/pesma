<?php

namespace App\Filament\Resources\RoomRuleResource\Pages;

use App\Filament\Resources\RoomRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRoomRules extends ListRecords
{
    protected static string $resource = RoomRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Peraturan'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('deleted_at'))
                ->badge(fn () => \App\Models\RoomRule::query()->whereNull('deleted_at')->count()),
        ];

        // Tab data terhapus hanya untuk super_admin
        if (auth()->user()->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => \App\Models\RoomRule::query()->onlyTrashed()->count())
                ->badgeColor('danger');
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}
