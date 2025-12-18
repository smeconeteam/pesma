<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
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
        ]);
    }
}
