<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use App\Models\ResidentProfile;
use App\Models\Room;
use App\Models\RoomResident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ResidentResource;
use App\Filament\Resources\RoomResource;
use App\Filament\Resources\RegistrationResource;
use App\Filament\Resources\RoomPlacementResource;

class StatsOverviewWidget extends BaseWidget
{

    protected int | string | array $columnSpan = 'full';
    
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
        $occupiedRooms = (clone $roomQuery)->whereHas('activeRoomResidents')->count();
        
        // Hitung total kapasitas dari semua kamar
        $totalCapacity = (clone $roomQuery)->sum('capacity');

        // Total active residents (semua yang status active, tidak harus punya kamar)
        $activeResidentsQuery = ResidentProfile::query()->where('status', 'active');

        // Residents yang punya kamar
        $residentsWithRoomQuery = RoomResident::query()->whereNull('room_residents.check_out_date');

        // Apply role-based filters untuk residents
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            // Filter residents yang ada di dorm tertentu
            $userIdsInDorm = RoomResident::query()
                ->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
                ->whereIn('blocks.dorm_id', $dormIds)
                ->pluck('room_residents.user_id')
                ->unique();
            $activeResidentsQuery->whereIn('user_id', $userIdsInDorm);
            $residentsWithRoomQuery->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
                ->whereIn('blocks.dorm_id', $dormIds);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $userIdsInBlock = RoomResident::query()
                ->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->whereIn('rooms.block_id', $blockIds)
                ->pluck('room_residents.user_id')
                ->unique();
            $activeResidentsQuery->whereIn('user_id', $userIdsInBlock);
            $residentsWithRoomQuery->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->whereIn('rooms.block_id', $blockIds);
        }

        $totalActiveResidents = $activeResidentsQuery->count();
        $residentsWithRoom = $residentsWithRoomQuery->distinct('user_id')->count('user_id');
        $pendingRegistrations = $registrationQuery->count();

        // Hitung residents without room untuk status penempatan
        $totalResidentsQuery = ResidentProfile::query()->where('status', '!=', 'inactive');
        
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $userIdsWithRoom = RoomResident::query()
                ->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
                ->whereIn('blocks.dorm_id', $dormIds)
                ->whereNull('room_residents.check_out_date')
                ->pluck('room_residents.user_id')
                ->unique();
            $totalResidentsQuery->whereIn('user_id', $userIdsWithRoom);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $userIdsWithRoom = RoomResident::query()
                ->join('rooms', 'room_residents.room_id', '=', 'rooms.id')
                ->whereIn('rooms.block_id', $blockIds)
                ->whereNull('room_residents.check_out_date')
                ->pluck('room_residents.user_id')
                ->unique();
            $totalResidentsQuery->whereIn('user_id', $userIdsWithRoom);
        }

        $totalResidents = $totalResidentsQuery->count();
        $residentsWithoutRoom = max(0, $totalResidents - $residentsWithRoom);
        $placementPercentage = $totalResidents > 0 ? round(($residentsWithRoom / $totalResidents) * 100, 1) : 0;
        
        // Hitung okupansi kapasitas
        $occupancyPercentage = $totalCapacity > 0 ? round(($residentsWithRoom / $totalCapacity) * 100, 1) : 0;
        $availableCapacity = max(0, $totalCapacity - $residentsWithRoom);

        return [
            // Baris pertama
            Stat::make('Penghuni Aktif', $totalActiveResidents)
                ->description("Penghuni memiliki kamar: {$residentsWithRoom}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->url(ResidentResource::getUrl('index')),

            Stat::make('Kamar', $totalRooms)
                ->description("dan {$occupiedRooms} kamar terisi")
                ->descriptionIcon('heroicon-m-home')
                ->color('info')
                ->url(RoomResource::getUrl('index')),

            Stat::make('Pendaftaran Pending', $pendingRegistrations)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingRegistrations > 0 ? 'warning' : 'success')
                ->url(RegistrationResource::getUrl('index')),

            // Baris kedua
            Stat::make('Kapasitas', $totalCapacity)
                ->description("Terisi: {$residentsWithRoom} | Tersedia: {$availableCapacity}")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->url(RoomResource::getUrl('index')),

            Stat::make('Okupansi Kapasitas', "{$occupancyPercentage}%")
                ->description("Penghuni: {$residentsWithRoom} dari {$totalCapacity} kapasitas")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($occupancyPercentage >= 80 ? 'danger' : ($occupancyPercentage >= 60 ? 'warning' : 'success')),

            Stat::make('Status Penempatan', "{$placementPercentage}%")
                ->description("Sudah ada kamar: {$residentsWithRoom} | Belum: {$residentsWithoutRoom}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($placementPercentage >= 80 ? 'success' : ($placementPercentage >= 50 ? 'warning' : 'danger'))
                ->url(RoomPlacementResource::getUrl('index')),
        ];
    }

    protected static ?int $sort = 1;
}