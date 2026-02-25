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
        $authUser = auth()->user();
        $isBranchAdmin = $authUser?->hasRole('branch_admin') ?? false;
        $dormIds = $isBranchAdmin ? $authUser->branchDormIds()->toArray() : [];

        // Penghuni yang terdaftar/aktif tapi belum ada kamar aktif
        $pendingQuery = User::query()
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
            );

        // Penghuni yang sudah ditempatkan (punya kamar aktif)
        $placedQuery = User::query()
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
            );

        // Total kamar tersedia (tidak penuh)
        $availableRoomsQuery = Room::query()
            ->where('is_active', true)
            ->whereRaw('
                (capacity - (
                    SELECT COUNT(*) 
                    FROM room_residents 
                    WHERE room_residents.room_id = rooms.id 
                    AND room_residents.check_out_date IS NULL
                )) > 0
            ');

        // Total kamar penuh
        $fullRoomsQuery = Room::query()
            ->where('is_active', true)
            ->whereRaw('
                capacity <= (
                    SELECT COUNT(*) 
                    FROM room_residents 
                    WHERE room_residents.room_id = rooms.id 
                    AND room_residents.check_out_date IS NULL
                )
            ');

        // Scope ke cabang untuk branch_admin
        if ($isBranchAdmin) {
            $placedQuery->whereHas('roomResidents', function (Builder $rr) use ($dormIds) {
                $rr->whereNull('check_out_date')
                    ->whereHas('room.block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
            });

            $availableRoomsQuery->whereHas('block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
            $fullRoomsQuery->whereHas('block', fn(Builder $b) => $b->whereIn('dorm_id', $dormIds));
        }

        $pendingPlacement = $pendingQuery->count();
        $placed = $placedQuery->count();
        $availableRooms = $availableRoomsQuery->count();
        $fullRooms = $fullRoomsQuery->count();

        $branchLabel = $isBranchAdmin ? ' (cabang Anda)' : '';

        return [
            Stat::make('Menunggu Penempatan', $pendingPlacement)
                ->description('Penghuni belum ditempatkan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPlacement > 0 ? 'warning' : 'success'),

            Stat::make('Sudah Ditempatkan', $placed)
                ->description('Penghuni sudah berkamar' . $branchLabel)
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Kamar Tersedia', $availableRooms)
                ->description("{$fullRooms} kamar penuh" . $branchLabel)
                ->descriptionIcon('heroicon-m-home-modern')
                ->color($availableRooms > 0 ? 'info' : 'danger'),
        ];
    }
}
