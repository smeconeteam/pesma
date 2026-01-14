<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ResidentTemplateExport implements 
    FromCollection, 
    WithHeadings, 
    WithStyles, 
    WithColumnWidths,
    WithEvents
{
    public function collection()
    {
        // Baris contoh data
        return collect([
            [
                'ahmad.fauzi@example.com',
                'password123',
                'Ahmad Fauzi Rahman',
                'Pondok',
                'WNI',
                'Indonesia',
                '3302121506050001',
                '2024010001',
                'M',
                'Purwokerto',
                '15/06/2005',
                'Universitas Jenderal Soedirman',
                '081234567890',
                'Bapak Rahman',
                '081298765432',
            ],
            [
                'siti.nurhaliza@example.com',
                'password456',
                'Siti Nurhaliza Azzahra',
                'Wisma',
                'WNI',
                'Indonesia',
                '3302124508050002',
                '2024010002',
                'F',
                'Banyumas',
                '25/08/2005',
                'IAIN Purwokerto',
                '081234567891',
                'Ibu Siti Aisyah',
                '081298765433',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'email',
            'password',
            'nama_lengkap',
            'kategori',
            'kewarganegaraan',
            'asal_negara',
            'nik',
            'nim',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'universitas',
            'no_hp',
            'nama_wali',
            'no_hp_wali',
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
            'A' => 25, // email
            'B' => 15, // password
            'C' => 25, // nama_lengkap
            'D' => 15, // kategori
            'E' => 18, // kewarganegaraan
            'F' => 15, // asal_negara
            'G' => 18, // nik
            'H' => 15, // nim
            'I' => 15, // jenis_kelamin
            'J' => 15, // tempat_lahir
            'K' => 15, // tanggal_lahir
            'L' => 25, // universitas
            'M' => 15, // no_hp
            'N' => 20, // nama_wali
            'O' => 15, // no_hp_wali
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Tinggi baris header
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                // Border untuk semua cell yang ada data
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Freeze header row
                $sheet->freezePane('A2');
                
                // Auto filter
                $sheet->setAutoFilter('A1:' . $highestColumn . '1');
                
                // Add instruction sheet (sheet kedua)
                $workbook = $sheet->getParent();
                $instructionSheet = $workbook->createSheet();
                $instructionSheet->setTitle('Petunjuk');
                
                // Styling untuk petunjuk
                $instructionSheet->setCellValue('A1', 'PETUNJUK PENGGUNAAN TEMPLATE IMPORT DATA PENGHUNI');
                $instructionSheet->mergeCells('A1:D1');
                $instructionSheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2E75B6'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $instructionSheet->getRowDimension(1)->setRowHeight(30);
                
                $row = 3;
                $instructions = [
                    ['KOLOM', 'KETERANGAN', 'WAJIB', 'CONTOH'],
                    ['email', 'Email unik penghuni (tidak boleh duplikat)', 'Ya', 'ahmad.fauzi@example.com'],
                    ['password', 'Password untuk login (minimal 8 karakter)', 'Ya', 'password123'],
                    ['nama_lengkap', 'Nama lengkap penghuni', 'Ya', 'Ahmad Fauzi Rahman'],
                    ['kategori', 'Kategori penghuni (Pondok/Wisma/Asrama/Kos)', 'Ya', 'Pondok'],
                    ['kewarganegaraan', 'Status kewarganegaraan: WNI atau WNA', 'Ya', 'WNI'],
                    ['asal_negara', 'Nama negara asal (otomatis Indonesia jika WNI)', 'Tidak', 'Indonesia'],
                    ['nik', 'Nomor Induk Kependudukan (16 digit)', 'Tidak', '3302121506050001'],
                    ['nim', 'Nomor Induk Mahasiswa/Pelajar', 'Tidak', '2024010001'],
                    ['jenis_kelamin', 'M/F atau L/P atau Laki-laki/Perempuan', 'Ya', 'M'],
                    ['tempat_lahir', 'Tempat lahir', 'Tidak', 'Purwokerto'],
                    ['tanggal_lahir', 'Format: dd/mm/yyyy', 'Tidak', '15/06/2005'],
                    ['universitas', 'Nama universitas/sekolah', 'Tidak', 'Universitas Jenderal Soedirman'],
                    ['no_hp', 'Nomor HP (hanya angka, tanpa +)', 'Tidak', '081234567890'],
                    ['nama_wali', 'Nama wali/orang tua', 'Tidak', 'Bapak Rahman'],
                    ['no_hp_wali', 'Nomor HP wali (hanya angka)', 'Tidak', '081298765432'],
                ];
                
                foreach ($instructions as $instruction) {
                    $instructionSheet->fromArray($instruction, null, 'A' . $row);
                    $row++;
                }
                
                // Style header instruksi
                $instructionSheet->getStyle('A3:D3')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E7E6E6'],
                    ],
                ]);
                
                // Border untuk tabel instruksi
                $instructionSheet->getStyle('A3:D' . ($row - 1))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Column widths untuk petunjuk
                $instructionSheet->getColumnDimension('A')->setWidth(20);
                $instructionSheet->getColumnDimension('B')->setWidth(50);
                $instructionSheet->getColumnDimension('C')->setWidth(10);
                $instructionSheet->getColumnDimension('D')->setWidth(30);
                
                // Catatan penting
                $row += 2;
                $instructionSheet->setCellValue('A' . $row, 'CATATAN PENTING:');
                $instructionSheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;
                
                $notes = [
                    '1. Jangan mengubah nama kolom di baris header (baris pertama)',
                    '2. Email harus unik, tidak boleh sama dengan penghuni yang sudah terdaftar',
                    '3. Password akan di-enkripsi secara otomatis saat import',
                    '4. Kategori yang belum ada akan dibuat otomatis',
                    '5. Untuk jenis kelamin, sistem menerima: M, F, L, P, Laki-laki, atau Perempuan',
                    '6. NIK harus tepat 16 digit angka',
                    '7. Nomor HP hanya boleh berisi angka (tanpa spasi, tanda +, atau tanda hubung)',
                    '8. Format tanggal lahir harus dd/mm/yyyy (contoh: 15/06/2005)',
                    '9. Hapus baris contoh sebelum mengisi data Anda',
                    '10. Import hanya menambahkan data baru, tidak mengupdate data yang sudah ada',
                ];
                
                foreach ($notes as $note) {
                    $instructionSheet->setCellValue('A' . $row, $note);
                    $row++;
                }
                
                // Set active sheet kembali ke sheet data
                $workbook->setActiveSheetIndex(0);
            },
        ];
    }
}