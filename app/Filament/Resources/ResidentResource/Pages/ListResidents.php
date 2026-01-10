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
        $user = auth()->user();

        // Tabs untuk semua admin kecuali super_admin (tidak ada tab Terhapus)
        $tabs = [
            'aktif' => Tab::make('Aktif')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->withoutTrashed()
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'));
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'))
                        ->withoutTrashed();

                    // Filter berdasarkan role
                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($dormIds) {
                            $q->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                        });
                    } elseif ($user->hasRole('block_admin')) {
                        $blockIds = $user->blockIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($blockIds) {
                            $q->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                        });
                    }

                    return $query->count();
                })
                ->badgeColor('success'),

            'keluar' => Tab::make('Keluar')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->withoutTrashed()
                        ->whereHas('roomResidents', function (Builder $q) {
                            // Pernah punya kamar
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            // Tidak punya kamar aktif saat ini
                            $q->whereNull('check_out_date');
                        });
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->withoutTrashed()
                        ->whereHas('roomResidents', function (Builder $q) {
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            $q->whereNull('check_out_date');
                        });

                    // Filter berdasarkan role
                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($dormIds) {
                            $q->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                        });
                    } elseif ($user->hasRole('block_admin')) {
                        $blockIds = $user->blockIds()->toArray();
                        $query->whereHas('roomResidents', function (Builder $q) use ($blockIds) {
                            $q->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                        });
                    }

                    return $query->count();
                })
                ->badgeColor('warning'),
        ];

        // Tab Terhapus hanya untuk super_admin
        if ($user->hasRole('super_admin')) {
            $tabs['terhapus'] = Tab::make('Terhapus')
                ->modifyQueryUsing(fn(Builder $query) => $query->onlyTrashed())
                ->badge(
                    fn() => User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->onlyTrashed()
                        ->count()
                )
                ->badgeColor('danger');
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResidentResource\Widgets\ResidentStatsOverview::class,
        ];
    }
}
