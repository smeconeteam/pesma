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
use Illuminate\Support\Carbon;

class ViewResident extends ViewRecord
{
    protected static string $resource = ResidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load([
            // ✅ penting: load profile yang trashed
            'residentProfile' => function ($q) {
                $q->withTrashed()->with(['residentCategory', 'country']);
            },
            'activeRoomResident.room.roomType',
            'activeRoomResident.room.block.dorm',
        ]);

        $this->record->setRelation(
            'roomHistories',
            $this->record->roomHistories()
                ->with(['room.roomType', 'room.block.dorm', 'recordedBy'])
                ->orderBy('check_in_date', 'desc')
                ->get()
                ->unique('id')
        );
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
                        ->label('Status Penghuni')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'registered' => 'warning',
                            'active' => 'success',
                            'inactive' => 'danger',
                            default => 'secondary',
                        })
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'registered' => 'Terdaftar',
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            default => '-',
                        }),

                    TextEntry::make('residentProfile.residentCategory.name')->label('Kategori')->placeholder('-')->badge(),
                    TextEntry::make('residentProfile.gender')
                        ->label('Gender')
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state) => $state === 'M' ? 'Laki-laki' : ($state === 'F' ? 'Perempuan' : '-')),
                    TextEntry::make('residentProfile.national_id')->label('NIK')->placeholder('-'),
                    TextEntry::make('residentProfile.student_id')->label('NIM')->placeholder('-'),
                    TextEntry::make('residentProfile.citizenship_status')->label('Kewarganegaraan')->placeholder('-')->badge(),
                    TextEntry::make('residentProfile.country.name')->label('Asal Negara')->placeholder('-'),
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
                    TextEntry::make('activeRoomResident.room.block.dorm.name')->label('Cabang')->placeholder('-'),
                    TextEntry::make('activeRoomResident.room.block.name')->label('Komplek')->placeholder('-'),
                    TextEntry::make('activeRoomResident.room.roomType.name')->label('Tipe Kamar')->placeholder('-'),
                    TextEntry::make('activeRoomResident.room.number')->label('Nomor Kamar')->placeholder('-'),
                    TextEntry::make('activeRoomResident.room.code')->label('Kode Kamar')->placeholder('-'),
                    TextEntry::make('activeRoomResident.check_in_date')->label('Tanggal Masuk')->date('d M Y')->placeholder('-'),
                    IconEntry::make('activeRoomResident.is_pic')->label('PIC Kamar')->boolean()->placeholder('-'),
                ])
                ->visible(fn ($record) => $record->activeRoomResident !== null),

            InfoSection::make('Riwayat Kamar')
                ->description('Riwayat perpindahan kamar penghuni')
                ->schema([
                    RepeatableEntry::make('roomHistories')
                        ->label('')
                        ->schema([
                            InfoSection::make('')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('room.block.dorm.name')->label('Cabang')->placeholder('-'),
                                    TextEntry::make('room.block.name')->label('Komplek')->placeholder('-'),
                                    TextEntry::make('room.roomType.name')->label('Tipe Kamar')->placeholder('-'),
                                    TextEntry::make('room.number')->label('Nomor Kamar')->placeholder('-'),
                                    TextEntry::make('room.code')->label('Kode Kamar')->placeholder('-'),

                                    TextEntry::make('movement_type')
                                        ->label('Tipe Perpindahan')
                                        ->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'new' => 'success',
                                            'transfer' => 'warning',
                                            'checkout' => 'danger',
                                            default => 'secondary',
                                        })
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'new' => 'Masuk',
                                            'transfer' => 'Pindah',
                                            'checkout' => 'Keluar',
                                            default => '-',
                                        }),

                                    TextEntry::make('check_in_date')->label('Tanggal Masuk')->date('d M Y'),
                                    TextEntry::make('check_out_date')->label('Tanggal Keluar')->date('d M Y')->placeholder('Masih tinggal')->default(null),

                                    TextEntry::make('duration_display')
                                        ->label('Durasi Tinggal')
                                        ->state(function ($record) {
                                            $checkIn  = $record->check_in_date;
                                            $checkOut = $record->check_out_date;

                                            if (! $checkIn) return '-';

                                            $checkInDate = Carbon::parse($checkIn)->startOfDay();
                                            $now         = now()->startOfDay();

                                            if ($now->lt($checkInDate)) {
                                                return '0 hari (belum melewati tanggal masuk)';
                                            }

                                            if (! $checkOut) {
                                                $days = (int) $checkInDate->diffInDays($now);
                                            } else {
                                                $checkOutDate = Carbon::parse($checkOut)->startOfDay();

                                                if ($checkOutDate->lt($checkInDate)) {
                                                    return '0 hari (data tidak valid)';
                                                }

                                                $days = (int) $checkInDate->diffInDays($checkOutDate);
                                            }

                                            if ($days === 0) return '0 hari';
                                            if ($days === 1) return '1 hari';

                                            $months        = (int) floor($days / 30);
                                            $remainingDays = (int) ($days % 30);

                                            if ($months > 0 && $remainingDays > 0) {
                                                return $months . ' bulan ' . $remainingDays . ' hari';
                                            } elseif ($months > 0) {
                                                return $months . ' bulan';
                                            }

                                            return $days . ' hari';
                                        }),

                                    IconEntry::make('is_pic')->label('PIC Kamar')->boolean(),
                                    TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                                    TextEntry::make('recordedBy.name')->label('Dicatat Oleh')->placeholder('-'),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(false)
                ->visible(fn ($record) => $record->roomHistories->isNotEmpty()),
        ]);
    }
}
