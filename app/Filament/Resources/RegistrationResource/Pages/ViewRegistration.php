<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistration extends ViewRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->status === 'pending'),

            Actions\Action::make('approve')
                ->label('Setujui Pendaftaran')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->status === 'pending')
                ->url(fn() => RegistrationResource::getUrl('approve', ['record' => $this->record])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Status Pendaftaran')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary',
                        })
                        ->formatStateUsing(fn($state) => match ($state) {
                            'pending' => 'Menunggu Persetujuan',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => '-',
                        }),

                    TextEntry::make('created_at')
                        ->label('Tanggal Daftar')
                        ->dateTime('d M Y H:i'),

                    TextEntry::make('approved_at')
                        ->label('Disetujui Pada')
                        ->dateTime('d M Y H:i')
                        ->visible(fn($record) => $record->status === 'approved'),

                    TextEntry::make('approvedBy.name')
                        ->label('Disetujui Oleh')
                        ->visible(fn($record) => $record->status === 'approved'),

                    TextEntry::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->columnSpanFull()
                        ->visible(fn($record) => $record->status === 'rejected'),
                ]),

            InfoSection::make('Akun')
                ->columns(2)
                ->schema([
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('name')->label('Nama Panggilan'),
                ]),

            InfoSection::make('Profil')
                ->columns(2)
                ->schema([
                    \Filament\Infolists\Components\ImageEntry::make('photo_path')
                        ->label('Foto')
                        ->circular()
                        ->defaultImageUrl(url('/images/default-avatar.png'))
                        ->columnSpanFull(),

                    TextEntry::make('full_name')->label('Nama Lengkap'),
                    TextEntry::make('residentCategory.name')->label('Kategori')->badge(),
                    TextEntry::make('gender')
                        ->label('Jenis Kelamin')
                        ->formatStateUsing(fn($state) => $state === 'M' ? 'Laki-laki' : 'Perempuan'),
                    TextEntry::make('national_id')->label('NIK')->placeholder('-'),
                    TextEntry::make('student_id')->label('NIM')->placeholder('-'),
                    TextEntry::make('citizenship_status')->label('Kewarganegaraan')->badge(),
                    TextEntry::make('country.name')->label('Negara'),
                    TextEntry::make('birth_place')->label('Tempat Lahir')->placeholder('-'),
                    TextEntry::make('birth_date')->label('Tanggal Lahir')->date('d M Y')->placeholder('-'),
                    TextEntry::make('university_school')->label('Universitas/Sekolah')->placeholder('-'),
                    TextEntry::make('phone_number')->label('No. HP')->placeholder('-'),
                    TextEntry::make('guardian_name')->label('Nama Wali')->placeholder('-'),
                    TextEntry::make('guardian_phone_number')->label('No. HP Wali')->placeholder('-'),
                    TextEntry::make('address')
                        ->label('Alamat')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

            InfoSection::make('Preferensi Kamar')
                ->columns(2)
                ->schema([
                    TextEntry::make('preferredDorm.name')
                        ->label('Cabang Pilihan')
                        ->placeholder('-'),

                    TextEntry::make('preferredRoomType.name')
                        ->label('Tipe Kamar Pilihan')
                        ->placeholder('-'),

                    TextEntry::make('preferredRoom.number')
                        ->label('Nomor Kamar Pilihan')
                        ->placeholder('-')
                        ->formatStateUsing(function ($state, $record) {
                            if (!$state) return '-';
                            $room = $record->preferredRoom;
                            return "No. {$room->number} (sisa {$room->available_capacity} tempat)";
                        }),

                    TextEntry::make('planned_check_in_date')
                        ->label('Rencana Tanggal Masuk')
                        ->date('d M Y')
                        ->placeholder('-'),
                ]),
        ]);
    }
}
