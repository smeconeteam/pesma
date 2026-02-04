<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RegistrationTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([]); // Empty collection, we just need headers
    }

    public function headings(): array
    {
        return [
            'email',
            'nama_lengkap',
            'nama_panggilan',
            'jenis_kelamin (L/P)',
            'nomor_telepon',
            'kategori_penghuni',
            'kewarganegaraan (WNI/WNA)',
            'negara',
            'nik',
            'nim',
            'universitas',
            'alamat',
        ];
    }
}
