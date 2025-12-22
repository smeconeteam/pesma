<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
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
        $isSuperAdmin = auth()->user()?->hasRole('super_admin');

        $tabs = [
            'aktif' => Tab::make('Data Aktif')
                ->badge(
                    fn() =>
                    User::whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->count()
                ),
        ];

        if ($isSuperAdmin) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(function (Builder $query) {
                    // Query sudah include soft deleted karena withoutGlobalScopes
                    // Kita hanya filter yang deleted_at tidak null
                    return $query->whereNotNull('users.deleted_at');
                })
                ->badge(
                    fn() =>
                    User::whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->onlyTrashed()
                        ->count()
                )
                ->badgeColor('danger');
        }

        return $tabs;
    }
}
