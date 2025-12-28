<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListResidents extends ListRecords
{
    protected static string $resource = ResidentResource::class;

    public function getModel(): string
    {
        return User::class;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereNull('users.deleted_at');
                })
                ->badge(
                    fn() => User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->whereNull('users.deleted_at')
                        ->count()
                ),
            'terhapus' => Tab::make('Data Terhapus')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereNotNull('users.deleted_at');
                })
                ->badge(
                    fn() => User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->onlyTrashed()
                        ->count()
                )
                ->badgeColor('danger'),
        ];
    }
}
