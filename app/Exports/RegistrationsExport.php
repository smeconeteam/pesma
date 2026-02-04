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
            'Full Name',
            'Nickname',
            'Email',
            'Phone Number',
            'Status',
            'Gender',
            'Category',
            'University/School',
            'Student ID (NIM/NIS)',
            'National ID (NIK)',
            'Citizenship',
            'Country',
            'Address',
            'Planned Check In',
            'Created At',
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
