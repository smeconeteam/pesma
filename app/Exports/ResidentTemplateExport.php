<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ResidentTemplateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    public function collection()
    {
        return collect([]);
        // Bisa tambahkan contoh data dummy di baris ke-3 jika mau
    }

    public function headings(): array
    {
        return [
            ['TEMPLATE IMPORT DATA PENGHUNI'], // Baris 1: Judul
            [ // Baris 2: Header Kolom
                'email',
                'nama_lengkap',
                'nama_panggilan',
                'jenis_kelamin (L/P)',
                'nomor_telepon',
                'kategori_penghuni',
                'kewarganegaraan (WNI/WNA)',
                'negara',
                'nik',
                'nim_nis', // Changed from nim
                'universitas_sekolah', // Changed from universitas
                'alamat',
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style Judul (A1)
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Style Header (Baris 2)
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4caf50'], // Green
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestColumn = $sheet->getHighestColumn();
                
                // Merge Title Cells (A1 sampai kolom terakhir)
                $sheet->mergeCells('A1:' . $highestColumn . '1');

                // Tambahkan Border ke seluruh area header (Baris 2)
                // Karena belum ada data, kita border baris 2 saja sebagai indikator
                $sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
