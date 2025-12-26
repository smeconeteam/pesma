<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // AKUN
            'email' => ['required', 'email', 'max:255', 'unique:registrations,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],

            // PROFIL
            'resident_category_id' => ['required', 'exists:resident_categories,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:M,F'],
            'national_id' => ['required', 'regex:/^\d+$/'],
            'student_id' => ['required', 'string', 'max:255'],
            'birth_place' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:-6 years'],
            'university_school' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],

            // KEWARGANEGARAAN & KONTAK
            'citizenship_status' => ['required', 'in:WNI,WNA'],
            'country_id' => ['required', 'exists:countries,id'],
            'phone_number' => ['required', 'regex:/^\d+$/'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone_number' => ['nullable', 'regex:/^\d+$/'],

            // PREFERENSI
            'preferred_dorm_id' => ['required', 'exists:dorms,id'],
            'preferred_room_type_id' => ['required', 'exists:room_types,id'],
            'planned_check_in_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi',
            'email.unique' => 'Email sudah terdaftar',
            'name.required' => 'Nama wajib diisi',
            'resident_category_id.required' => 'Kategori penghuni wajib dipilih',
            'full_name.required' => 'Nama lengkap wajib diisi',
            'gender.required' => 'Jenis kelamin wajib dipilih',
            'national_id.required' => 'NIK wajib diisi',
            'national_id.regex' => 'NIK hanya boleh angka',
            'student_id.required' => 'NIM wajib diisi',
            'birth_place.required' => 'Tempat lahir wajib diisi',
            'birth_date.required' => 'Tanggal lahir wajib diisi',
            'birth_date.before' => 'Minimal usia 6 tahun',
            'university_school.required' => 'Universitas/Sekolah wajib diisi',
            'citizenship_status.required' => 'Kewarganegaraan wajib dipilih',
            'country_id.required' => 'Asal negara wajib dipilih',
            'phone_number.required' => 'No. HP wajib diisi',
            'phone_number.regex' => 'No. HP hanya boleh angka',
            'guardian_phone_number.regex' => 'No. HP Wali hanya boleh angka',
            'preferred_dorm_id.required' => 'Cabang yang diinginkan wajib dipilih',
            'preferred_room_type_id.required' => 'Tipe kamar yang diinginkan wajib dipilih',
            'planned_check_in_date.required' => 'Rencana tanggal masuk wajib diisi',
            'planned_check_in_date.after_or_equal' => 'Tanggal masuk minimal hari ini',
        ];
    }
}
