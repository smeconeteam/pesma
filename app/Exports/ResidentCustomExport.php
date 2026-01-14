<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ResidentCustomExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping,
    WithStyles, 
    WithColumnWidths
{
    protected $residentIds;
    protected $filters;
    protected $activeTab;
    protected $rowNumber = 0;

    public function __construct(array $residentIds, array $filters = [], string $activeTab = 'aktif')
    {
        $this->residentIds = $residentIds;
        $this->filters = $filters;
        $this->activeTab = $activeTab;
    }

    public function collection()
    {
        return User::query()
            ->whereIn('id', $this->residentIds)
            ->with([
                'residentProfile.residentCategory',
                'residentProfile.country',
            ])
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Email',
            'Status Akun',
            'Nama Lengkap',
            'Kategori',
            'Kewarganegaraan',
            'Asal Negara',
            'NIK',
            'NIM',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Universitas/Sekolah',
            'No. HP',
            'Nama Wali',
            'No. HP Wali',
            'Status Penghuni',
            'Tanggal Masuk',
            'Tanggal Keluar',
            'Tanggal Dibuat',
        ];
    }

    public function map($resident): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $resident->email ?? '-',
            $resident->is_active ? 'Aktif' : 'Nonaktif',
            $resident->residentProfile?->full_name ?? '-',
            $resident->residentProfile?->residentCategory?->name ?? '-',
            match($resident->residentProfile?->citizenship_status) {
                'WNI' => 'WNI (Warga Negara Indonesia)',
                'WNA' => 'WNA (Warga Negara Asing)',
                default => '-'
            },
            $resident->residentProfile?->country?->name ?? '-',
            $resident->residentProfile?->national_id ? "'" . $resident->residentProfile->national_id : '-',
            $resident->residentProfile?->student_id ? "'" . $resident->residentProfile->student_id : '-',
            match($resident->residentProfile?->gender) {
                'M' => 'Laki-laki',
                'F' => 'Perempuan',
                default => '-'
            },
            $resident->residentProfile?->birth_place ?? '-',
            $resident->residentProfile?->birth_date ? \Carbon\Carbon::parse($resident->residentProfile->birth_date)->format('d/m/Y') : '-',
            $resident->residentProfile?->university_school ?? '-',
            $resident->residentProfile?->phone_number ? "'" . $resident->residentProfile->phone_number : '-',
            $resident->residentProfile?->guardian_name ?? '-',
            $resident->residentProfile?->guardian_phone_number ? "'" . $resident->residentProfile->guardian_phone_number : '-',
            match($resident->residentProfile?->status) {
                'registered' => 'Terdaftar',
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                default => '-'
            },
            $resident->residentProfile?->check_in_date ? \Carbon\Carbon::parse($resident->residentProfile->check_in_date)->format('d/m/Y') : '-',
            $resident->residentProfile?->check_out_date ? \Carbon\Carbon::parse($resident->residentProfile->check_out_date)->format('d/m/Y') : '-',
            $resident->created_at ? \Carbon\Carbon::parse($resident->created_at)->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk header
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 25,  // Email
            'C' => 12,  // Status Akun
            'D' => 25,  // Nama Lengkap
            'E' => 15,  // Kategori
            'F' => 18,  // Kewarganegaraan
            'G' => 15,  // Asal Negara
            'H' => 18,  // NIK
            'I' => 15,  // NIM
            'J' => 15,  // Jenis Kelamin
            'K' => 15,  // Tempat Lahir
            'L' => 15,  // Tanggal Lahir
            'M' => 25,  // Universitas
            'N' => 15,  // No. HP
            'O' => 20,  // Nama Wali
            'P' => 15,  // No. HP Wali
            'Q' => 15,  // Status Penghuni
            'R' => 15,  // Tanggal Masuk
            'S' => 15,  // Tanggal Keluar
            'T' => 18,  // Tanggal Dibuat
        ];
    }
}