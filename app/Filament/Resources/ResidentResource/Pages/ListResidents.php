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

        $tabs = [
            'aktif' => Tab::make('Aktif')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->withoutTrashed()
                        // HAPUS filter is_active agar penghuni yang dinonaktifkan tetap muncul
                        ->where(function (Builder $q) {
                            // Tampilkan penghuni yang:
                            // 1. Belum pernah punya kamar (registered)
                            $q->whereDoesntHave('roomResidents')
                            // 2. ATAU punya kamar aktif saat ini (active)
                              ->orWhereHas('roomResidents', function (Builder $rr) {
                                  $rr->whereNull('check_out_date');
                              });
                        });
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        // HAPUS filter is_active
                        ->withoutTrashed()
                        ->where(function (Builder $q) {
                            $q->whereDoesntHave('roomResidents')
                              ->orWhereHas('roomResidents', function (Builder $rr) {
                                  $rr->whereNull('check_out_date');
                              });
                        });

                    // Filter berdasarkan role
                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $query->where(function (Builder $q) use ($dormIds) {
                            $q->whereDoesntHave('roomResidents')
                              ->orWhereHas('roomResidents', function (Builder $rr) use ($dormIds) {
                                  $rr->whereNull('check_out_date')
                                     ->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                              });
                        });
                    } elseif ($user->hasRole('block_admin')) {
                        $blockIds = $user->blockIds()->toArray();
                        $query->where(function (Builder $q) use ($blockIds) {
                            $q->whereDoesntHave('roomResidents')
                              ->orWhereHas('roomResidents', function (Builder $rr) use ($blockIds) {
                                  $rr->whereNull('check_out_date')
                                     ->whereHas('room', fn(Builder $room) => $room->whereIn('block_id', $blockIds));
                              });
                        });
                    }

                    return $query->count();
                })
                ->badgeColor('success'),

            'keluar' => Tab::make('Keluar')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->withoutTrashed()
                        // HAPUS filter is_active
                        ->whereHas('roomResidents', function (Builder $q) {
                            // Pernah punya kamar (check_out_date ada)
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
                        // HAPUS filter is_active
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
                ->badgeColor('danger'),
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
                ->badgeColor('gray');
        }

        return $tabs;
    }

    public function updatedActiveTab(): void
    {
        $this->deselectAllTableRecords();
        
        // Reset filter status saat pindah tab
        $this->tableFilters['status']['value'] = null;
        
        // Refresh halaman untuk update options filter
        $this->dispatch('$refresh');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResidentResource\Widgets\ResidentStatsOverview::class,
        ];
    }
}