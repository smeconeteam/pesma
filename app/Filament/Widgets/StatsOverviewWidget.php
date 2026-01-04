<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use App\Models\Room;
use App\Models\RoomResident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        // Base queries dengan scope
        $roomQuery = Room::query();
        $residentQuery = RoomResident::query()->whereNull('room_residents.check_out_date');
        $registrationQuery = Registration::where('status', 'pending');

        // Apply role-based filters
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Tidak perlu filter, lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $roomQuery->whereHas('block', fn($q) => $q->whereIn('dorm_id', $dormIds));
            $residentQuery->whereHas('room.block', fn($q) => $q->whereIn('dorm_id', $dormIds));
            $registrationQuery->whereIn('preferred_dorm_id', $dormIds);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $roomQuery->whereIn('block_id', $blockIds);
            $residentQuery->whereHas('room', fn($q) => $q->whereIn('block_id', $blockIds));
            // Block admin tidak perlu lihat registrasi
            $registrationQuery->whereRaw('1 = 0'); // Return 0
        }

        // Get data
        $totalRooms = $roomQuery->count();
        $occupiedRooms = $roomQuery->whereHas('activeRoomResidents')->count();
        $activeResidents = $residentQuery->count();
        $pendingRegistrations = $registrationQuery->count();

        return [
            Stat::make('Total Penghuni Aktif', $activeResidents)
                ->description('Penghuni yang sedang menghuni')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 25, $activeResidents]),

            Stat::make('Total Kamar Terisi', $occupiedRooms)
                ->description("dari {$totalRooms} total kamar")
                ->descriptionIcon('heroicon-m-home')
                ->color('info')
                ->chart([$totalRooms - $occupiedRooms, $occupiedRooms]),

            Stat::make('Pendaftaran Pending', $pendingRegistrations)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingRegistrations > 0 ? 'warning' : 'success'),
        ];
    }

    protected static ?int $sort = 1;
}
