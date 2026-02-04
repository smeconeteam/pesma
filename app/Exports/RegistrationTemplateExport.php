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
            'full_name',
            'nickname',
            'gender (M/F)',
            'phone_number',
            'category_name',
            'citizenship (WNI/WNA)',
            'country_name',
            'nik',
            'nim',
            'university',
            'address',
        ];
    }
}
