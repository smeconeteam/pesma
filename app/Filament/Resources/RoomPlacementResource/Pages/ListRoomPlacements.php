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
            // ✅ Tidak filter is_active, karena user tetap aktif meski sudah keluar
            ->whereHas('residentProfile', fn(Builder $q) => 
                $q->whereIn('status', ['registered', 'active'])
            );
            
        return static::getResource()::applyBranchScope($query);
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
                    // ✅ Query penghuni yang sudah keluar
                    $query = User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        // ✅ User TETAP AKTIF (tidak filter is_active)
                        ->whereHas('residentProfile', function (Builder $q) {
                            $q->where('status', 'inactive'); // ✅ Status nonaktif
                        })
                        ->whereHas('roomResidents', function (Builder $q) {
                            // ✅ Pernah punya kamar
                            $q->whereNotNull('check_out_date');
                        })
                        ->whereDoesntHave('roomResidents', function (Builder $q) {
                            // ✅ Tidak ada kamar aktif
                            $q->whereNull('check_out_date');
                        })
                        ->with([
                            'residentProfile.residentCategory',
                            'residentProfile.country',
                            'roomResidents' => fn($q) => $q->latest('check_out_date')->limit(1)
                        ]);
                        
                    return static::getResource()::applyBranchScope($query);
                })
                ->badge(function () {
                    $query = User::query()
                        ->whereHas('roles', fn($q) => $q->where('name', 'resident'))
                        // ✅ Tidak filter is_active
                        ->whereHas('residentProfile', fn($q) => $q->where('status', 'inactive'))
                        ->whereHas('roomResidents', fn($q) => $q->whereNotNull('check_out_date'))
                        ->whereDoesntHave('roomResidents', fn($q) => $q->whereNull('check_out_date'));

                    return static::getResource()::applyBranchScope($query)->count();
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