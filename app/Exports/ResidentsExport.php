<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ResidentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    // Simpan jumlah baris data untuk keperluan border
    protected $dataCount = 0;

    public function collection()
    {
        $data = User::whereHas('roles', function ($q) {
            $q->where('name', 'resident');
        })->with(['residentProfile', 'residentProfile.residentCategory', 'residentProfile.country', 'roomResidents.room'])->get();
        
        $this->dataCount = $data->count();
        return $data;
    }

    public function headings(): array
    {
        return [
            ['DATA PENGHUNI ASRAMA'], // Baris 1: Judul
            [ // Baris 2: Header
                'ID',
                'Nama Lengkap',
                'Nama Panggilan',
                'Email',
                'Nomor Telepon',
                'Status Akun',
                'Jenis Kelamin',
                'Kategori',
                'Universitas/Sekolah',
                'NIM/NIS',
                'NIK',
                'Kewarganegaraan',
                'Negara',
                'Alamat',
                'Kamar Aktif',
                'Tanggal Masuk Kamar',
                'Dibuat Pada',
            ]
        ];
    }

    public function map($user): array
    {
        $profile = $user->residentProfile;
        
        // Ambil kamar aktif
        $activeRoomResident = $user->roomResidents()
            ->whereNull('check_out_date')
            ->latest('check_in_date')
            ->first();

        $roomCode = $activeRoomResident?->room?->code ?? '-';
        if ($activeRoomResident?->room?->number) {
            $roomCode .= ' (' . $activeRoomResident->room->number . ')';
        }
        
        $checkInDate = $activeRoomResident?->check_in_date ? \Carbon\Carbon::parse($activeRoomResident->check_in_date)->format('Y-m-d') : '-';

        return [
            $user->id,
            $profile?->full_name ?? $user->name,
            $user->name, // Nickname
            $user->email,
            $profile?->phone_number ?? '-',
            $user->is_active ? 'Aktif' : 'Nonaktif',
            $profile?->gender === 'M' ? 'Laki-laki' : ($profile?->gender === 'F' ? 'Perempuan' : '-'),
            $profile?->residentCategory?->name ?? '-',
            $profile?->university_school ?? '-',
            $profile?->student_id ?? '-',
            $profile?->national_id ?? '-',
            $profile?->citizenship_status ?? '-',
            $profile?->country?->name ?? '-',
            $profile?->address ?? '-',
            $roomCode,
            $checkInDate,
            $user->created_at->format('Y-m-d H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style Judul (A1)
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Style Header (Baris 2)
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2c3e50'], // Warna Dark Blue
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $lastRow = $this->dataCount + 2; // Data + Header + Title

                // 1. Setup Page: Landscape & Fit to Width
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                
                // Opsi Fit to Width (penting utk PDF agar tidak kepotong)
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0); // 0 = automatic height

                // 2. Merge Title
                $sheet->mergeCells('A1:' . $highestColumn . '1');

                // 3. Tambahkan Border ke seluruh data
                if ($this->dataCount > 0) {
                    $sheet->getStyle('A2:' . $highestColumn . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => '000000'],
                            ],
                        ],
                    ]);
                }

                // 4. Wrap Text untuk kolom Alamat (biasanya M atau N, tapi lebih aman by heading index if known, or just global)
                // Kita coba wrap text untuk header agar tidak terlalu lebar jika teks panjang
                $sheet->getStyle('A2:' . $highestColumn . '2')->getAlignment()->setWrapText(true);
                
                // Wrap text untuk data (misal alamat) biar column tidak super lebar
                $sheet->getStyle('A3:' . $highestColumn . $lastRow)->getAlignment()->setWrapText(false); 
                // Set WrapText true khusus kolom Alamat (Kolom N = index 14)
                $sheet->getStyle('N3:N' . $lastRow)->getAlignment()->setWrapText(true);

                // 5. Vertical Alignment Center untuk semua sel
                $sheet->getStyle('A1:' . $highestColumn . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
