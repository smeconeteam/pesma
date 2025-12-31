<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\User;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListResidents extends ListRecords
{
    protected static string $resource = ResidentResource::class;

    public function getTabs(): array
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed())
                ->badge(fn () => User::query()
                    ->whereHas('roles', fn (Builder $q) => $q->where('name', 'resident'))
                    ->withoutTrashed()
                    ->count()
                ),

            'terhapus' => Tab::make('Data Terhapus')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn () => User::query()
                    ->whereHas('roles', fn (Builder $q) => $q->where('name', 'resident'))
                    ->onlyTrashed()
                    ->count()
                )
                ->badgeColor('danger'),
        ];
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }
}
