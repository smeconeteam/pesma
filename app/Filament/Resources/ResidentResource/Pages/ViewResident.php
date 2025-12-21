<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewResident extends ViewRecord
{
    protected static string $resource = ResidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    // ✅ CRITICAL: Eager load untuk mencegah N+1 dan memory leak
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Eager load semua relasi yang dibutuhkan
        $this->record->load([
            'residentProfile.residentCategory',
            'residentProfile.country',
            'activeRoomResident.room.block.dorm',
            'roomHistories.room.block.dorm',
            'roomHistories.recordedBy',
        ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Akun')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Nama Akun')->placeholder('-'),
                    TextEntry::make('email')->label('Email')->placeholder('-'),
                    IconEntry::make('is_active')->label('Aktif')->boolean(),
                ]),

            InfoSection::make('Profil')
                ->columns(2)
                ->schema([
                    \Filament\Infolists\Components\ImageEntry::make('residentProfile.photo_path')
                        ->label('Foto')
                        ->circular()
                        ->defaultImageUrl(url('/images/default-avatar.png'))
                        ->columnSpanFull(),

                    TextEntry::make('residentProfile.full_name')->label('Nama Lengkap')->placeholder('-'),

                    TextEntry::make('residentProfile.status')
                        ->label('Status Resident')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'registered' => 'warning',
                            'active' => 'success',
                            'inactive' => 'danger',
                            default => 'secondary',
                        })
                        ->formatStateUsing(fn($state) => match ($state) {
                            'registered' => 'Terdaftar',
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            default => '-',
                        }),

                    TextEntry::make('residentProfile.residentCategory.name')->label('Kategori')->placeholder('-')->badge(),
                    TextEntry::make('residentProfile.gender')
                        ->label('Gender')
                        ->placeholder('-')
                        ->formatStateUsing(fn($state) => $state === 'M' ? 'Laki-laki' : ($state === 'F' ? 'Perempuan' : '-')),
                    TextEntry::make('residentProfile.national_id')->label('NIK')->placeholder('-'),
                    TextEntry::make('residentProfile.student_id')->label('NIM')->placeholder('-'),
                    TextEntry::make('residentProfile.citizenship_status')->label('Kewarganegaraan')->placeholder('-')->badge(),
                    TextEntry::make('residentProfile.country.name')->label('Negara')->placeholder('-'),
                    TextEntry::make('residentProfile.birth_place')->label('Tempat Lahir')->placeholder('-'),
                    TextEntry::make('residentProfile.birth_date')->label('Tanggal Lahir')->date('d M Y')->placeholder('-'),
                    TextEntry::make('residentProfile.university_school')->label('Universitas/Sekolah')->placeholder('-'),
                    TextEntry::make('residentProfile.phone_number')->label('No. HP')->placeholder('-'),
                    TextEntry::make('residentProfile.guardian_name')->label('Nama Wali')->placeholder('-'),
                    TextEntry::make('residentProfile.guardian_phone_number')->label('No. HP Wali')->placeholder('-'),
                ]),

            InfoSection::make('Kamar Saat Ini')
                ->columns(2)
                ->schema([
                    TextEntry::make('current_room_info')
                        ->label('Informasi Kamar')
                        ->state(function ($record) {
                            $active = $record->activeRoomResident;
                            if (!$active) return 'Tidak ada kamar aktif';

                            $room = $active->room;
                            $block = $room->block;
                            $dorm = $block->dorm;

                            return "{$dorm->name} - {$block->name} - {$room->code}";
                        })
                        ->badge()
                        ->color(fn($record) => $record->activeRoomResident ? 'success' : 'warning'),

                    TextEntry::make('activeRoomResident.check_in_date')
                        ->label('Tanggal Masuk')
                        ->date('d M Y')
                        ->placeholder('-'),

                    IconEntry::make('activeRoomResident.is_pic')
                        ->label('PIC Kamar')
                        ->boolean()
                        ->placeholder('-'),
                ])
                ->visible(fn($record) => $record->activeRoomResident !== null),

            InfoSection::make('Riwayat Kamar')
                ->description('Riwayat perpindahan kamar resident')
                ->schema([
                    RepeatableEntry::make('roomHistories')
                        ->label('')
                        ->schema([
                            TextEntry::make('room.code')
                                ->label('Kamar')
                                ->formatStateUsing(function ($record, $state) {
                                    // Hindari closure kompleks, gunakan data yang sudah di-eager load
                                    $room = $record->room;
                                    if (!$room) return '-';

                                    $block = $room->block;
                                    $dorm = $block?->dorm;

                                    if (!$dorm || !$block) return $room->code;

                                    return "{$dorm->name} - {$block->name} - {$room->code}";
                                })
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('movement_type')
                                ->label('Tipe')
                                ->badge()
                                ->color(fn($state) => match ($state) {
                                    'new' => 'success',
                                    'transfer' => 'warning',
                                    'checkout' => 'danger',
                                    default => 'secondary',
                                })
                                ->formatStateUsing(fn($state) => match ($state) {
                                    'new' => 'Masuk Baru',
                                    'transfer' => 'Pindah',
                                    'checkout' => 'Keluar',
                                    default => '-',
                                }),

                            TextEntry::make('check_in_date')
                                ->label('Masuk')
                                ->date('d M Y'),

                            TextEntry::make('check_out_date')
                                ->label('Keluar')
                                ->date('d M Y')
                                ->placeholder('Masih tinggal')
                                ->default(null),

                            TextEntry::make('duration_display')
                                ->label('Durasi')
                                ->state(function ($record) {
                                    // Gunakan cara sederhana tanpa nested closure
                                    $checkIn = $record->check_in_date;
                                    $checkOut = $record->check_out_date;

                                    if (!$checkIn) return '-';

                                    if (!$checkOut) {
                                        $days = now()->diffInDays($checkIn);
                                    } else {
                                        $days = $checkIn->diffInDays($checkOut);
                                    }

                                    if ($days === 0) return '< 1 hari';

                                    $months = floor($days / 30);
                                    $remainingDays = $days % 30;

                                    if ($months > 0) {
                                        return $months . ' bulan ' . $remainingDays . ' hari';
                                    }

                                    return $days . ' hari';
                                }),

                            IconEntry::make('is_pic')
                                ->label('PIC')
                                ->boolean(),

                            TextEntry::make('notes')
                                ->label('Catatan')
                                ->placeholder('-')
                                ->columnSpanFull()
                                ->limit(100),

                            TextEntry::make('recordedBy.name')
                                ->label('Dicatat oleh')
                                ->placeholder('-'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(false),
        ]);
    }
}
