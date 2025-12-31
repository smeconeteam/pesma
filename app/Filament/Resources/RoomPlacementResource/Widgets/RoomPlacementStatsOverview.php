<?php

namespace App\Filament\Resources\RoomPlacementResource\Widgets;

use App\Models\User;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class RoomPlacementStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Penghuni yang terdaftar/aktif tapi belum ada kamar
        $pendingPlacement = User::query()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->whereHas(
                'residentProfile',
                fn(Builder $q) =>
                $q->whereIn('status', ['registered', 'active'])
            )
            ->whereDoesntHave(
                'roomResidents',
                fn(Builder $q) =>
                $q->whereNull('check_out_date')
            )
            ->count();

        // Penghuni yang sudah ditempatkan
        $placed = User::query()
            ->whereHas('roles', fn(Builder $q) => $q->where('name', 'resident'))
            ->whereHas(
                'residentProfile',
                fn(Builder $q) =>
                $q->whereIn('status', ['registered', 'active'])
            )
            ->whereHas(
                'roomResidents',
                fn(Builder $q) =>
                $q->whereNull('check_out_date')
            )
            ->count();

        // Total kamar tersedia (tidak penuh)
        $availableRooms = Room::query()
            ->where('is_active', true)
            ->whereRaw('
                (capacity - (
                    SELECT COUNT(*) 
                    FROM room_residents 
                    WHERE room_residents.room_id = rooms.id 
                    AND room_residents.check_out_date IS NULL
                )) > 0
            ')
            ->count();

        // Total kamar penuh
        $fullRooms = Room::query()
            ->where('is_active', true)
            ->whereRaw('
                capacity <= (
                    SELECT COUNT(*) 
                    FROM room_residents 
                    WHERE room_residents.room_id = rooms.id 
                    AND room_residents.check_out_date IS NULL
                )
            ')
            ->count();

        return [
            Stat::make('Menunggu Penempatan', $pendingPlacement)
                ->description('Penghuni belum ditempatkan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPlacement > 0 ? 'warning' : 'success'),

            Stat::make('Sudah Ditempatkan', $placed)
                ->description('Penghuni sudah berkamar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Kamar Tersedia', $availableRooms)
                ->description("{$fullRooms} kamar penuh")
                ->descriptionIcon('heroicon-m-home-modern')
                ->color($availableRooms > 0 ? 'info' : 'danger'),
        ];
    }
}
