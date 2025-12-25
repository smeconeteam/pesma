<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRoomPlacements extends ListRecords
{
    protected static string $resource = RoomPlacementResource::class;

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Resident'),

            'belum_kamar' => Tab::make('Belum Ada Kamar')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->whereDoesntHave(
                        'roomResidents',
                        fn(Builder $rr) =>
                        $rr->whereNull('check_out_date')
                    )
                )
                ->badge(
                    fn() => \App\Models\User::query()
                        ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
                        ->whereHas('residentProfile', fn(Builder $q) => $q->where('status', 'registered'))
                        ->whereDoesntHave(
                            'roomResidents',
                            fn(Builder $rr) =>
                            $rr->whereNull('check_out_date')
                        )
                        ->count()
                )
                ->badgeColor('warning'),

            'sudah_kamar' => Tab::make('Sudah Ada Kamar')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->whereHas(
                        'roomResidents',
                        fn(Builder $rr) =>
                        $rr->whereNull('check_out_date')
                    )
                ),
        ];
    }
}
