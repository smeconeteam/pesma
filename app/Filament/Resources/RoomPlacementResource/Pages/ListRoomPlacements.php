<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\User;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRoomPlacements extends ListRecords
{
    protected static string $resource = RoomPlacementResource::class;

    protected function baseCountQuery(): Builder
    {
        $query = User::query()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->whereHas(
                'residentProfile',
                fn(Builder $q) =>
                $q->whereIn('status', ['registered', 'active'])
            );

        return static::getResource()::applyBranchScope($query);
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        return [
            // Semua penghuni dalam scope 
            'semua' => Tab::make('Semua Penghuni')
                ->badge(fn() => $this->baseCountQuery()->count()),

            // Belum berkamar 
            'belum_kamar' => Tab::make('Belum Ada Kamar')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereDoesntHave('roomResidents', function (Builder $q) {
                        $q->whereNull('check_out_date');
                    });
                })
                ->badge(
                    fn() => $this->baseCountQuery()
                        ->whereDoesntHave('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date'))
                        ->count()
                )
                ->badgeColor('warning'),

            //  Sudah berkamar di cabang ini 
            'sudah_kamar' => Tab::make('Sudah Ada Kamar')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query = $query->whereHas('roomResidents', function (Builder $q) {
                        $q->whereNull('check_out_date');
                    });

                    // Untuk branch_admin, pastikan kamar aktifnya memang di cabangnya
                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $query = $query->whereHas('activeRoomResident.room.block', function (Builder $b) use ($dormIds) {
                            $b->whereIn('dorm_id', $dormIds);
                        });
                    }

                    return $query;
                })
                ->badge(function () use ($user) {
                    $q = $this->baseCountQuery()
                        ->whereHas('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date'));

                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $q = $q->whereHas('activeRoomResident.room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
                    }

                    return $q->count();
                })
                ->badgeColor('success'),

            //  Sudah keluar (terakhir dari cabang ini)
            'keluar' => Tab::make('Keluar')
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    // Override query supaya juga ambil status 'inactive'
                    $baseQuery = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->whereHas('residentProfile', function (Builder $q) {
                            $q->where('status', 'inactive');
                        })
                        ->whereHas('roomResidents', function (Builder $q) {
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            $q->whereNull('check_out_date');
                        })
                        ->with([
                            'residentProfile.residentCategory',
                            'residentProfile.country',
                            'roomResidents' => fn($q) => $q->latest('check_out_date')->limit(1),
                        ]);

                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $baseQuery->whereHas('roomResidents.room.block', function (Builder $b) use ($dormIds) {
                            $b->whereIn('dorm_id', $dormIds);
                        });
                    }

                    return $baseQuery;
                })
                ->badge(function () use ($user) {
                    $q = User::query()
                        ->whereHas('roles', fn($q) => $q->where('name', 'resident'))
                        ->whereHas('residentProfile', fn($q) => $q->where('status', 'inactive'))
                        ->whereHas('roomResidents', fn($q) => $q->whereNotNull('check_out_date'))
                        ->whereDoesntHave('roomResidents', fn($q) => $q->whereNull('check_out_date'));

                    if ($user->hasRole('branch_admin')) {
                        $dormIds = $user->branchDormIds()->toArray();
                        $q->whereHas('roomResidents.room.block', fn($b) => $b->whereIn('dorm_id', $dormIds));
                    }

                    return $q->count();
                })
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RoomPlacementResource\Widgets\RoomPlacementStatsOverview::class,
        ];
    }
}
