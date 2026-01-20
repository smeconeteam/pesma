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
                        ->where('is_active', true)
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'active'));
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->where('is_active', true)
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
                        // ✅ User TETAP AKTIF (tidak perlu filter is_active)
                        ->whereHas('residentProfile', function (Builder $q) {
                            $q->where('status', 'inactive'); // ✅ Status penghuni nonaktif
                        })
                        ->whereHas('roomResidents', function (Builder $q) {
                            // ✅ Pernah punya kamar (check_out_date ada)
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            // ✅ Tidak punya kamar aktif saat ini
                            $q->whereNull('check_out_date');
                        });
                })
                ->badge(function () use ($user) {
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->withoutTrashed()
                        // ✅ Tidak filter is_active, karena user tetap aktif
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'inactive'))
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
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResidentResource\Widgets\ResidentStatsOverview::class,
        ];
    }
}