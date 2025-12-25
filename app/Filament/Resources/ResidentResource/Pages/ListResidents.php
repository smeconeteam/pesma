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
        $isSuperAdmin = auth()->user()?->hasRole('super_admin');

        $tabs = [
            'aktif' => Tab::make('Data Aktif')
                ->modifyQueryUsing(function (Builder $query) {
                    // ✅ pastikan hanya data yang belum terhapus
                    return $query->whereNull('users.deleted_at');
                })
                ->badge(fn () => User::query()
                    ->whereHas('roles', fn (Builder $q) => $q->where('name', 'resident'))
                    ->whereNull('users.deleted_at')
                    ->count()
                ),
        ];

        if ($isSuperAdmin) {
            $tabs['terhapus'] = Tab::make('Data Terhapus')
                ->modifyQueryUsing(function (Builder $query) {
                    // ✅ hanya data yang terhapus
                    return $query->whereNotNull('users.deleted_at');
                })
                ->badge(fn () => User::query()
                    ->whereHas('roles', fn (Builder $q) => $q->where('name', 'resident'))
                    ->onlyTrashed()
                    ->count()
                )
                ->badgeColor('danger');
        }

        return $tabs;
    }
}
