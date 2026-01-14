<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ResidentExport extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            
            ExportColumn::make('email')
                ->label('Email'),
            
            ExportColumn::make('is_active')
                ->label('Status Akun')
                ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Nonaktif'),
            
            // Profil Penghuni
            ExportColumn::make('residentProfile.full_name')
                ->label('Nama Lengkap'),
            
            ExportColumn::make('residentProfile.residentCategory.name')
                ->label('Kategori'),
            
            ExportColumn::make('residentProfile.citizenship_status')
                ->label('Kewarganegaraan')
                ->formatStateUsing(fn ($state) => match($state) {
                    'WNI' => 'WNI (Warga Negara Indonesia)',
                    'WNA' => 'WNA (Warga Negara Asing)',
                    default => '-'
                }),
            
            ExportColumn::make('residentProfile.country.name')
                ->label('Asal Negara'),
            
            // ✅ FIX: NIK sebagai string dengan prefix '
            ExportColumn::make('residentProfile.national_id')
                ->label('NIK')
                ->formatStateUsing(fn ($state) => $state ? "'" . $state : null),
            
            // ✅ FIX: NIM sebagai string dengan prefix '
            ExportColumn::make('residentProfile.student_id')
                ->label('NIM')
                ->formatStateUsing(fn ($state) => $state ? "'" . $state : null),
            
            ExportColumn::make('residentProfile.gender')
                ->label('Jenis Kelamin')
                ->formatStateUsing(fn ($state) => match($state) {
                    'M' => 'Laki-laki',
                    'F' => 'Perempuan',
                    default => '-'
                }),
            
            ExportColumn::make('residentProfile.birth_place')
                ->label('Tempat Lahir'),
            
            ExportColumn::make('residentProfile.birth_date')
                ->label('Tanggal Lahir')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : '-'),
            
            ExportColumn::make('residentProfile.university_school')
                ->label('Universitas/Sekolah'),
            
            // ✅ FIX: Phone number sebagai string dengan prefix '
            ExportColumn::make('residentProfile.phone_number')
                ->label('No. HP')
                ->formatStateUsing(fn ($state) => $state ? "'" . $state : null),
            
            ExportColumn::make('residentProfile.guardian_name')
                ->label('Nama Wali'),
            
            // ✅ FIX: Guardian phone sebagai string dengan prefix '
            ExportColumn::make('residentProfile.guardian_phone_number')
                ->label('No. HP Wali')
                ->formatStateUsing(fn ($state) => $state ? "'" . $state : null),
            
            ExportColumn::make('residentProfile.status')
                ->label('Status Penghuni')
                ->formatStateUsing(fn ($state) => match($state) {
                    'registered' => 'Terdaftar',
                    'active' => 'Aktif',
                    'inactive' => 'Nonaktif',
                    default => '-'
                }),
            
            ExportColumn::make('residentProfile.check_in_date')
                ->label('Tanggal Masuk')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : '-'),
            
            ExportColumn::make('residentProfile.check_out_date')
                ->label('Tanggal Keluar')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : '-'),
            
            ExportColumn::make('created_at')
                ->label('Tanggal Dibuat')
                ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export data penghuni selesai. ' . number_format($export->successful_rows) . ' baris berhasil di-export.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }
}