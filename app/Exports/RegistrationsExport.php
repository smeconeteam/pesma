<?php

namespace App\Exports;

use App\Models\Registration;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RegistrationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Registration::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Lengkap',
            'Nama Panggilan',
            'Email',
            'Nomor Telepon',
            'Status',
            'Jenis Kelamin',
            'Kategori',
            'Universitas/Sekolah',
            'NIM/NIS',
            'NIK',
            'Kewarganegaraan',
            'Negara',
            'Alamat',
            'Rencana Check In',
            'Dibuat Pada',
        ];
    }

    public function map($registration): array
    {
        return [
            $registration->id,
            $registration->full_name,
            $registration->name,
            $registration->email,
            $registration->phone_number,
            $registration->status,
            $registration->gender,
            $registration->residentCategory?->name,
            $registration->university_school,
            $registration->student_id,
            $registration->national_id,
            $registration->citizenship_status,
            $registration->country?->name,
            $registration->address,
            $registration->planned_check_in_date,
            $registration->created_at,
        ];
    }
}
