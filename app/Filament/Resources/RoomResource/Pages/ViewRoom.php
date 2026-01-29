<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Models\Room;
use Filament\Actions;
use App\Models\RoomResident;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use App\Filament\Resources\RoomResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $record = $this->record;

        return [
            Actions\EditAction::make()
                ->visible(
                    fn() => ($user?->hasRole(['super_admin', 'main_admin']) ?? false)
                        && !$record->trashed()
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Lokasi')
                    ->schema([
                        TextEntry::make('block.dorm.name')->label('Cabang')->placeholder('-'),
                        TextEntry::make('block.name')->label('Komplek')->placeholder('-'),
                        TextEntry::make('roomType.name')->label('Tipe Kamar')->placeholder('-'),
                    ])
                    ->columns(3),

                InfoSection::make('Detail Kamar')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Kamar')
                            ->copyable()
                            ->copyMessage('Kode disalin'),

                        TextEntry::make('number')->label('Nomor Kamar')->placeholder('-'),

                        TextEntry::make('capacity')
                            ->label('Kapasitas')
                            ->formatStateUsing(fn($state) => $state ? "{$state} orang" : '-'),

                        TextEntry::make('monthly_rate')
                            ->label('Tarif Bulanan')
                            ->money('IDR', true),

                        TextEntry::make('residentCategory.name')
                            ->label('Kategori Kamar')
                            ->placeholder('-'),

                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),

                        TextEntry::make('penghuni_aktif')
                            ->label('Penghuni Aktif')
                            ->state(
                                fn(Room $record) => RoomResident::query()
                                    ->where('room_id', $record->id)
                                    ->whereNull('check_out_date')
                                    ->count()
                            )
                            ->suffix(' orang'),

                        TextEntry::make('available_capacity')
                            ->label('Kapasitas Tersedia')
                            ->state(fn(Room $record) => $record->getAvailableCapacityAttribute())
                            ->suffix(' orang'),
                    ])
                    ->columns(3),

                InfoSection::make('Daftar Penghuni Aktif')
                    ->schema([
                        RepeatableEntry::make('activeRoomResidents')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('user.residentProfile.full_name')
                                            ->label('Nama Lengkap')
                                            ->placeholder('-'),

                                        TextEntry::make('user.residentProfile.student_id')
                                            ->label('NIM/NIS')
                                            ->placeholder('-'),

                                        TextEntry::make('check_in_date')
                                            ->label('Tanggal Masuk')
                                            ->date('d M Y')
                                            ->placeholder('-'),

                                        IconEntry::make('is_pic')
                                            ->label('PIC Kamar')
                                            ->boolean(),
                                    ])
                            ])
                            ->placeholder('Belum ada penghuni aktif')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn(Room $record) => $record->activeRoomResidents()->exists()),

                InfoSection::make('Riwayat Penghuni')
                    ->schema([
                        RepeatableEntry::make('roomResidents')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('user.residentProfile.full_name')
                                            ->label('Nama Lengkap')
                                            ->placeholder('-'),

                                        TextEntry::make('user.residentProfile.student_id')
                                            ->label('NIM/NIS')
                                            ->placeholder('-'),

                                        TextEntry::make('check_in_date')
                                            ->label('Tanggal Masuk')
                                            ->date('d M Y')
                                            ->placeholder('-'),

                                        TextEntry::make('check_out_date')
                                            ->label('Tanggal Keluar')
                                            ->date('d M Y')
                                            ->placeholder('Masih aktif')
                                            ->color('danger'),
                                    ])
                            ])
                            ->placeholder('Belum ada riwayat penghuni')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                InfoSection::make('Waktu')
                    ->schema([
                        TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                        TextEntry::make('updated_at')->label('Diubah')->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
