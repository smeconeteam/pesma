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
        return User::query()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->whereHas('residentProfile', fn(Builder $q) => $q->whereIn('status', ['registered', 'active']));
    }

    protected function exitedResidentsQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->whereHas('residentProfile'); // Ada resident profile, tanpa batasan status
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Penghuni')
                ->badge(fn() => $this->baseCountQuery()->count()),

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

            'sudah_kamar' => Tab::make('Sudah Ada Kamar')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->whereHas('roomResidents', function (Builder $q) {
                        $q->whereNull('check_out_date');
                    });
                })
                ->badge(
                    fn() => $this->baseCountQuery()
                        ->whereHas('roomResidents', fn(Builder $q) => $q->whereNull('check_out_date'))
                        ->count()
                )
                ->badgeColor('success'),

            'keluar' => Tab::make('Keluar')
                ->modifyQueryUsing(function (Builder $query) {
                    // Reset query dari scratch
                    return User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->where('is_active', false) // User tidak aktif
                        ->whereHas('residentProfile', function (Builder $q) {
                            $q->where('status', 'inactive');
                        })
                        ->whereHas('roomResidents') // Pernah punya kamar
                        ->with([
                            'residentProfile.residentCategory',
                            'residentProfile.country',
                            'roomResidents' => fn($q) => $q->latest('check_out_date')->limit(1)
                        ]);
                })
                ->badge(function () {
                    return User::query()
                        ->whereHas('roles', fn($q) => $q->where('name', 'resident'))
                        ->where('is_active', false)
                        ->whereHas('residentProfile', fn($q) => $q->where('status', 'inactive'))
                        ->whereHas('roomResidents')
                        ->count();
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
